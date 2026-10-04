<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/libs/Cast.php';

eval('declare(strict_types=1);namespace Cast {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('declare(strict_types=1);namespace Cast {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('declare(strict_types=1);namespace Cast {?>' . file_get_contents(__DIR__ . '/../libs/helper/ParentIOHelper.php') . '}');
eval('declare(strict_types=1);namespace Cast {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');
eval('declare(strict_types=1);namespace Cast {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableHelper.php') . '}');
eval('declare(strict_types=1);namespace Cast {?>' . file_get_contents(__DIR__ . '/../libs/helper/VariableProfileHelper.php') . '}');

/**
 * ChromeCast
 *
 * @property int $ParentID
 * @property string $Host
 * @property string $Buffer EmpfangsBuffer
 * @property int $RequestId
 * @property array $ReplyCMsgPayload
 * @property string $ActualApp //actual App
 * @property string $TransportId //actual App
 * @property string $SessionId //actual App session
 * @property array $AppNamespaces //Namespaces der aktuellen App
 * @property float $MediaStateFollowUp //Zeitpunkt, bis zu dem eine Medienstatus-Nachfrage aussteht
 * @property string $ScreenId // mdxSessionStatus
 * @property string $DeviceId // mdxSessionStatus
 * @property int $MediaSessionId //actual MediaSession
 * @property string $MediaUrl
 * @property string $AppIconUrl
 * @property float $PositionRAW
 * @property bool $IsSeekable
 * @property float $DurationRAW
 * @property int $SupportedMediaCommands
 * @property bool $StatusIsChanging
 * @property bool $IsIdleScreen
 * @property bool $PlaybackIsFinished
 * @method bool lock(string $ident)
 * @method void unlock(string $ident)
 * @method void SetValueBoolean(string $ident, bool $value)
 * @method void SetValueFloat(string $ident, float $value)
 * @method void SetValueInteger(string $ident, int $value)
 * @method void SetValueString(string $ident, string $value)
 * @method void RegisterProfileStringEx(string $Name, string $Icon, string $Prefix, string $Suffix, array $Associations)
 * @method void UnregisterProfile(string $Name)
 * @method bool SendDebug(string $message, mixed $data, int $format)
 */
class ChromeCast extends IPSModuleStrict
{
    use \Cast\DebugHelper;
    use \Cast\BufferHelper;
    use \Cast\Semaphore;
    use \Cast\VariableProfileHelper;
    use \Cast\VariableHelper;
    use \Cast\InstanceStatus {
        \Cast\InstanceStatus::MessageSink as IOMessageSink;
        \Cast\InstanceStatus::RegisterParent as IORegisterParent;
        \Cast\InstanceStatus::RequestAction as IORequestAction;
    }
    /**
     * Position der Statusvariable Wiedergabestatus
     */
    private const POSITION_PLAYER_STATE = 4;

    /**
     * Interne Aktion für eine verzögerte Medienstatus-Abfrage
     */
    private const ACTION_MEDIA_STATE_FOLLOW_UP = 'MediaStateFollowUp';

    /**
     * Idents, deren Fehler als Bedienaktion per echo zurückgemeldet werden
     */
    private const USER_ACTIONS = [
        \Cast\Device\VariableIdent::VOLUME,
        \Cast\Device\VariableIdent::MUTED,
        \Cast\Device\VariableIdent::APP_ID,
        \Cast\Device\VariableIdent::PLAYER_STATE,
        \Cast\Device\VariableIdent::POSITION_RAW,
        \Cast\Device\VariableIdent::CURRENT_TIME,
        'PlayYouTube',
        'PlayYouTubeMusic'
    ];

    /**
     * Antworttypen des Gerätes, welche einen Fehler darstellen
     */
    private const ERROR_RESPONSES = [
        \Cast\Commands::INVALID_REQUEST,
        \Cast\Commands::ERROR,
        \Cast\Commands::LAUNCH_ERROR,
        \Cast\Commands::LOAD_FAILED,
        \Cast\Commands::LOAD_CANCELLED,
        \Cast\Commands::INVALID_PLAYER_STATE
    ];

    /**
     * Profile vom Wiedergabestatus ohne Stop (Wert 1)
     */
    private const PLAYBACK_PROFILES_WITHOUT_STOP = [
        '~PlaybackNoStop',
        '~PlaybackPreviousNextNoStop'
    ];

    /**
     * True, solange eine Bedienaktion über RequestAction ausgeführt wird
     */
    private bool $isUserAction = false;

    /**
     * Letzte Fehlermeldung der aktuellen Bedienaktion
     */
    private string $lastError = '';

    public function Create(): void
    {
        //Never delete this line!
        parent::Create();
        $this->ParentID = 0;
        $this->Host = '';
        $this->Buffer = '';
        $this->RequestId = 1;
        $this->ReplyCMsgPayload = [];
        $this->ActualApp = '';
        $this->TransportId = '';
        $this->SessionId = '';
        $this->AppNamespaces = [];
        $this->MediaStateFollowUp = 0.0;
        $this->ScreenId = '';
        $this->DeviceId = '';
        $this->MediaSessionId = 0;
        $this->MediaUrl = '';
        $this->AppIconUrl = '';
        $this->PositionRAW = 0;
        $this->IsSeekable = false;
        $this->DurationRAW = 0;
        $this->SupportedMediaCommands = -1;
        $this->StatusIsChanging = false;
        $this->IsIdleScreen = true;
        $this->PlaybackIsFinished = true;
        $this->RegisterPropertyBoolean(\Cast\Device\Property::OPEN, false);
        $this->RegisterPropertyBoolean(\Cast\Device\Property::WATCHDOG, true);
        $this->RegisterPropertyInteger(\Cast\Device\Property::INTERVAL, 5);
        $this->RegisterPropertyInteger(\Cast\Device\Property::CONDITION_TYPE, 0);
        $this->RegisterPropertyString(\Cast\Device\Property::WATCHDOG_CONDITION, '');
        $this->RegisterPropertyInteger(\Cast\Device\Property::MEDIA_SIZE_WIDTH, 512);
        $this->RegisterPropertyInteger(\Cast\Device\Property::APP_ICON_SIZE_WIDTH, 90);
        $this->RegisterPropertyBoolean(\Cast\Device\Property::ENABLE_RAW_DURATION, true);
        $this->RegisterPropertyBoolean(\Cast\Device\Property::ENABLE_RAW_POSITION, true);
        $this->RegisterAttributeString(\Cast\Device\Attribute::KNOWN_APPS, '[]');
        $this->RegisterTimer(\Cast\Device\Timer::PROGRESS_STATE, 0, 'IPS_RequestAction(' . $this->InstanceID . ',"' . \Cast\Device\Timer::PROGRESS_STATE . '",true);');
        $this->RegisterTimer(\Cast\Device\Timer::KEEP_ALIVE, 0, 'IPS_RequestAction(' . $this->InstanceID . ',"' . \Cast\Device\Timer::KEEP_ALIVE . '",true);');
        $this->RegisterTimer(\Cast\Device\Timer::WATCHDOG, 0, 'IPS_RequestAction(' . $this->InstanceID . ',"' . \Cast\Device\Timer::WATCHDOG . '",true);');

        if (IPS_GetObject($this->InstanceID)['ObjectIcon'] == '') {
            IPS_SetIcon($this->InstanceID, 'screencast');
        }
    }

    public function Destroy(): void
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function GetCompatibleParents(): string
    {
        return '{"type": "require", "moduleIDs": ["' . \Cast\IO\GUID . '"]}';
    }

    public function ApplyChanges(): void
    {
        //Never delete this line!

        $this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);
        $this->RegisterMessage($this->InstanceID, IM_CHANGESTATUS);

        $this->SupportedMediaCommands = -1;
        $this->Buffer = '';
        $this->RequestId = 1;
        $this->ReplyCMsgPayload = [];
        $this->ActualApp = '';
        $this->TransportId = '';
        $this->SessionId = '';
        $this->AppNamespaces = [];
        $this->MediaStateFollowUp = 0.0;
        $this->ScreenId = '';
        $this->DeviceId = '';
        $this->MediaUrl = '';
        $this->AppIconUrl = '';
        $this->PositionRAW = 0;
        $this->IsSeekable = false;
        $this->DurationRAW = 0;
        $this->IsIdleScreen = true;

        $this->SetWatchdogTimer(false);

        parent::ApplyChanges();
        $i = 0;
        $this->RegisterProfileStringEx($this->GetAppProfileName(), '', '', '', \Cast\Apps::GetAllAppsAsProfileAssociation($this->GetKnownApps()));
        $this->RegisterVariableString(\Cast\Device\VariableIdent::APP_ID, $this->Translate('Active app'), $this->GetAppProfileName(), ++$i);
        $this->EnableAction(\Cast\Device\VariableIdent::APP_ID);

        $this->RegisterVariableInteger(\Cast\Device\VariableIdent::VOLUME, $this->Translate('Volume'), '~Volume', ++$i);
        $this->EnableAction(\Cast\Device\VariableIdent::VOLUME);
        $this->RegisterVariableBoolean(\Cast\Device\VariableIdent::MUTED, $this->Translate('Muted'), '~Mute', ++$i);
        $this->EnableAction(\Cast\Device\VariableIdent::MUTED);

        $this->RegisterVariableInteger(\Cast\Device\VariableIdent::PLAYER_STATE, $this->Translate('Player State'), '~PlaybackPreviousNextNoStop', ++$i);
        $this->EnableAction(\Cast\Device\VariableIdent::PLAYER_STATE);

        //$this->RegisterVariableString(\Cast\Device\VariableIdent::REPEAT_MODE, $this->Translate('Repeat'), '', ++$i);

        if ($this->ReadPropertyBoolean(\Cast\Device\Property::ENABLE_RAW_DURATION)) {
            $this->RegisterVariableInteger(\Cast\Device\VariableIdent::DURATION_RAW, $this->Translate('Duration in seconds'), '', ++$i);
        } else {
            $this->UnregisterVariable(\Cast\Device\VariableIdent::DURATION_RAW);
        }

        if ($this->ReadPropertyBoolean(\Cast\Device\Property::ENABLE_RAW_POSITION)) {
            $this->RegisterVariableInteger(\Cast\Device\VariableIdent::POSITION_RAW, $this->Translate('Position in seconds'), '', ++$i);
        } else {
            $this->UnregisterVariable(\Cast\Device\VariableIdent::POSITION_RAW);
        }

        $this->RegisterVariableString(\Cast\Device\VariableIdent::DURATION, $this->Translate('Duration'), '', ++$i);
        $this->RegisterVariableString(\Cast\Device\VariableIdent::POSITION, $this->Translate('Position'), '', ++$i);

        $this->RegisterVariableFloat(\Cast\Device\VariableIdent::CURRENT_TIME, $this->Translate('Progress'), '~Progress', ++$i);

        $this->RegisterVariableString(\Cast\Device\VariableIdent::TITLE, $this->Translate('Title'), '~Song', ++$i);
        $this->RegisterVariableString(\Cast\Device\VariableIdent::ARTIST, $this->Translate('Artist'), '~Artist', ++$i);
        $this->RegisterVariableString(\Cast\Device\VariableIdent::COLLECTION, $this->Translate('Collection'), '', ++$i);

        if (IPS_GetKernelRunlevel() != KR_READY) {
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->SetStatus(IS_INACTIVE);
            return;
        }

        $parentID = $this->RegisterParent();

        // Open auf false konfiguriert -> CS nie öffnen bzw. zwangsweise schließen
        $open = $this->ReadPropertyBoolean(\Cast\Device\Property::OPEN);
        if (!$open) {
            if ($parentID > 0) {
                //$this->StatusIsChanging = false;
                // Jetzt den CS schließen
                IPS_SetProperty($parentID, \Cast\IO\Property::OPEN, false);
                @IPS_ApplyChanges($parentID); // Diese Instanz reagiert auf die Änderung des CS über die MessageSink
            } else {
                $this->IOChangeState(IS_INACTIVE);
            }
            return;
        }

        // Kein Parent
        if ($parentID == 0) {
            $this->IOChangeState(IS_INACTIVE);
            $this->SetWatchdogTimer(true);
            return;
        }
        // Oder Parent ohne konfigurierten Host
        if ($this->Host == '') {
            $this->IOChangeState(IS_INACTIVE);
            $this->SetWatchdogTimer(true);
            return;
        }
        // Prüfe ob Watchdog konfiguriert & Condition gegeben ist
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::WATCHDOG)) {
            $open = $this->CheckCondition();
            if ($open) {
                if (!$this->CheckPort()) { // Keine Verbindung über CS erzwingen wenn Host offline ist
                    echo $this->Translate('Could not connect to TCP-Server');
                    $open = false;
                }
            }
        }
        // Jetzt den CS passend konfigurieren bzw. öffnen/schließen
        if (!$open) {
            IPS_SetProperty($parentID, \Cast\IO\Property::OPEN, false);
            @IPS_ApplyChanges($parentID); // Diese Instanz reagiert auf die Änderung des CS über die MessageSink
            $this->SetWatchdogTimer(true);
            return;
        }

        if (IPS_GetProperty($parentID, \Cast\IO\Property::OPEN) != true) {
            IPS_SetProperty($parentID, \Cast\IO\Property::OPEN, true);
        }

        @IPS_ApplyChanges($parentID); // Diese Instanz reagiert auf die Änderung des CS über die MessageSink
        return;
    }

    /**
     * Interne Funktion des SDK.
     *
     * @access public
     */
    public function MessageSink(int $timeStamp, int $senderID, int $message, array $data): void
    {
        $this->IOMessageSink($timeStamp, $senderID, $message, $data);
        switch ($message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;
            case IM_CHANGESETTINGS:
                if ($senderID != $this->ParentID) {
                    return;
                }
                $this->RegisterParent();
                if ($this->HasActiveParent()) {
                    $state = IS_ACTIVE;
                } else {
                    $state = IS_INACTIVE;
                }
                IPS_RunScriptText('IPS_RequestAction(' . $this->InstanceID . ',"IOChangeState",' . $state . ');');
                break;
            case IM_CHANGESTATUS:
                if ($senderID == $this->InstanceID) {
                    if ($this->StatusIsChanging) {
                        $this->SendDebug('MessageSink', 'StatusIsChanging already locked', 0);
                        return;
                    }
                    $this->StatusIsChanging = true;
                    $this->SendDebug('MessageSink', 'StatusIsChanging now locked', 0);
                    switch ($data[0]) {
                        case IS_ACTIVE:
                            // Nach dem Laden der Instanz setzt Symcon den Status vorab auf aktiv,
                            // ohne aktive Verbindung darf hier nichts gesendet werden. ApplyChanges setzt den korrekten Status.
                            if (!$this->HasActiveParent()) {
                                $this->SendDebug('IM_CHANGESTATUS', 'active without active parent, ignored', 0);
                                break;
                            }
                            $this->SendDebug('IM_CHANGESTATUS', 'active', 0);
                            $this->LogMessage('Connected to ChromeCast', KL_NOTIFY);
                            $this->SetWatchdogTimer(false);
                            $this->SetTimerInterval(\Cast\Device\Timer::KEEP_ALIVE, 60000);
                            $this->RequestState();
                            break;
                        case IS_EBASE + 1: //ERROR connection lost
                        case IS_INACTIVE:
                            $this->SetTimerInterval(\Cast\Device\Timer::KEEP_ALIVE, 0);
                            $this->SendDebug('IM_CHANGESTATUS', 'not active', 0);
                            $this->Buffer = '';
                            $this->RequestId = 1;
                            $this->ReplyCMsgPayload = [];
                            $this->SetIcon('');
                            $this->SetMediaImage('');
                            $this->ClearMediaVariables();
                            $this->SetWatchdogTimer(true);
                            break;
                    }
                    $this->SendDebug('MessageSink', 'StatusIsChanging now unlocked', 0);
                    $this->StatusIsChanging = false;
                }
                break;
        }
    }

    public function GetConfigurationForm(): string
    {
        $form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        $form['elements'][1]['visible'] = ($this->ParentID ? true : false);
        $form['elements'][1]['items'][1]['objectID'] = $this->ParentID;
        $form['elements'][2]['items'][0]['items'][1]['visible'] = $this->ReadPropertyBoolean(\Cast\Device\Property::WATCHDOG);
        $form['elements'][2]['items'][1]['items'][0]['visible'] = $this->ReadPropertyBoolean(\Cast\Device\Property::WATCHDOG);
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::WATCHDOG)) {
            $form['elements'][2]['items'][1]['items'][1]['visible'] = ($this->ReadPropertyInteger(\Cast\Device\Property::CONDITION_TYPE) == 1);
        }
        $this->SendDebug('FORM', json_encode($form), 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);
        return json_encode($form);
    }

    /**
     * Interne Funktion des SDK.
     *
     * @access public
     */
    public function GetConfigurationForParent(): string
    {
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::WATCHDOG)) {
            $config[\Cast\IO\Property::OPEN] = false;
            if ($this->ReadPropertyBoolean(\Cast\Device\Property::OPEN)) {
                $config[\Cast\IO\Property::OPEN] = ($this->GetStatus() == IS_ACTIVE);
            }
        } else {
            $config[\Cast\IO\Property::OPEN] = $this->ReadPropertyBoolean(\Cast\Device\Property::OPEN);
        }
        $config[\Cast\IO\Property::USE_SSL] = true;
        $config[\Cast\IO\Property::VERIFY_HOST] = false;
        $config[\Cast\IO\Property::VERIFY_PEER] = false;
        return json_encode($config);
    }

    public function RequestAction(string $ident, mixed $value): void
    {
        if ($this->IORequestAction($ident, $value)) {
            return;
        }
        // Bedienaktionen: Fehler per echo an den Aufrufer (Frontend) zurückmelden
        if (in_array($ident, self::USER_ACTIONS, true)) {
            $this->isUserAction = true;
            $this->lastError = '';
            $result = $this->HandleUserAction($ident, $value);
            $this->isUserAction = false;
            if (!$result) {
                echo ($this->lastError !== '') ? $this->lastError : $this->Translate('Action failed');
            }
            return;
        }
        switch ($ident) {
            case \Cast\Device\Timer::KEEP_ALIVE:
                $this->SendPing();
                break;
            case \Cast\Device\Timer::WATCHDOG:
                $this->Watchdog();
                break;
            case \Cast\Device\Property::WATCHDOG:
                $this->UpdateFormField('Watchdog', 'caption', (bool) $value ? 'Check every' : 'Check never');
                $this->UpdateFormField('Interval', 'visible', (bool) $value);
                $this->UpdateFormField('ConditionType', 'visible', (bool) $value);
                $this->UpdateFormField('ConditionPopup', 'visible', $this->ReadPropertyInteger(\Cast\Device\Property::CONDITION_TYPE) == 1);
                break;
            case \Cast\Device\Property::CONDITION_TYPE:
                $this->UpdateFormField('ConditionPopup', 'visible', $value == 1);
                break;

            case \Cast\Commands::PONG:
                $this->SendPong($value);
                break;
            case self::ACTION_MEDIA_STATE_FOLLOW_UP:
                // Veraltete Nachfrage (App gewechselt) still verwerfen
                $this->MediaStateFollowUp = 0.0;
                if (((string) $value === $this->TransportId) && $this->AppSupportsMedia()) {
                    $this->RequestMediaState();
                }
                break;
            case 'ResetKnownApps':
                $this->ResetKnownApps();
                break;
            case 'RequestState':
                $this->RequestState();
                break;
            case \Cast\Commands::CONNECT:
                if ($this->Connect((string) $value)) {
                    // Nur Apps mit Media-Namespace (bzw. der Ruhemodus) liefern einen Medienstatus
                    if ($this->IsIdleScreen || $this->AppSupportsMedia()) {
                        $this->RequestMediaState();
                    }
                }
                break;
                /*
    case \Cast\Device\VariableIdent::REPEAT_MODE:
                $this->SetRepeat($Value);
                break;
                 */
            case \Cast\Device\Timer::PROGRESS_STATE:
                if ($this->DurationRAW) {
                    if ($this->PositionRAW < $this->DurationRAW) {
                        $this->PositionRAW++;
                        $value = (100 / $this->DurationRAW) * $this->PositionRAW;
                        $this->SetValue(\Cast\Device\VariableIdent::CURRENT_TIME, $value);
                        $this->SetValue(\Cast\Device\VariableIdent::POSITION, \Cast\Device\TimeConvert::ConvertSeconds($this->PositionRAW));
                        if ($this->ReadPropertyBoolean(\Cast\Device\Property::ENABLE_RAW_POSITION)) {
                            $this->SetValue(\Cast\Device\VariableIdent::POSITION_RAW, (int) $this->PositionRAW);
                        }
                    }
                } else {
                    $this->SetTimerInterval(\Cast\Device\Timer::PROGRESS_STATE, 0);
                }
                break;
        }
    }

    /**
     * Veraltet: Alias für SetVolume, bleibt aus Kompatibilitätsgründen erhalten.
     *
     * @deprecated Nutze CCAST_SetVolume
     * @param float $level Lautstärke von 0.0 bis 1.0
     * @return bool True bei Erfolg
     */
    public function SetVolumen(float $level): bool
    {
        return $this->SetVolume($level);
    }

    /**
     * Setzt die Lautstärke des Gerätes.
     *
     * @param float $level Lautstärke von 0.0 bis 1.0
     * @return bool True bei Erfolg
     */
    public function SetVolume(float $level): bool
    {
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::SET_VOLUME, ['requestId' => $requestId, 'volume' => ['level' => $level]]);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::RECEIVER_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }

    public function SetMute(bool $mute): bool
    {
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::SET_VOLUME, ['requestId' => $requestId, 'volume' => ['muted' => $mute]]);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::RECEIVER_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }

    public function LaunchApp(string $appId): bool
    {
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::LAUNCH, ['requestId' => $requestId, 'appId' => $appId]);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::RECEIVER_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }

    public function SetPlayerState(string $state): bool
    {
        if (!$this->MediaSessionId) {
            $this->ReportError($this->Translate('No media playback active'));
            return false;
        }
        $requestId = $this->RequestId++;
        $payload = [];
        if ($state == \Cast\MediaCommands::NEXT) {
            $state = \Cast\MediaCommands::QUEUE_UPDATE;
            $payload['jump'] = 1;
        }

        if ($state == \Cast\MediaCommands::PREV) {
            $state = \Cast\MediaCommands::QUEUE_UPDATE;
            $payload['jump'] = -1;
        }
        $urn = \Cast\Urn::MEDIA_NAMESPACE;
        $payload = \Cast\Payload::MakePayload($state, array_merge($payload, ['requestId' => $requestId, 'mediaSessionId' => $this->MediaSessionId]));
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, $urn, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }

    public function Seek(float $time): bool
    {
        if (!$this->MediaSessionId) {
            $this->ReportError($this->Translate('No media playback active'));
            return false;
        }
        if (!$this->IsSeekable) {
            $this->ReportError($this->Translate('Media playback not seekable'));
            return false;
        }
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload(\Cast\MediaCommands::SEEK, ['currentTime' => $time, 'requestId' => $requestId, 'mediaSessionId' => $this->MediaSessionId]);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MEDIA_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }

    public function SeekRelative(float $time): bool
    {
        if (!$this->MediaSessionId) {
            $this->ReportError($this->Translate('No media playback active'));
            return false;
        }
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload(\Cast\MediaCommands::SEEK, ['relativeTime' => $time, 'requestId' => $requestId, 'mediaSessionId' => $this->MediaSessionId]);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MEDIA_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }

    public function SetRepeat(string $mode): bool
    {
        if (!$this->MediaSessionId) {
            $this->ReportError($this->Translate('No media playback active'));
            return false;
        }
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload(\Cast\MediaCommands::QUEUE_UPDATE, ['repeatMode' => $mode, 'requestId' => $requestId, 'mediaSessionId' => $this->MediaSessionId]);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MEDIA_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }

    public function Shuffle(): bool
    {
        if (!$this->MediaSessionId) {
            $this->ReportError($this->Translate('No media playback active'));
            return false;
        }
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload(\Cast\MediaCommands::SHUFFLE, ['requestId' => $requestId, 'mediaSessionId' => $this->MediaSessionId]);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MEDIA_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }
    public function DisplayLyrics(bool $showing): bool
    {
        if (!$this->MediaSessionId) {
            $this->ReportError($this->Translate('No media playback active'));
            return false;
        }
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::USER_ACTION, ['userAction' => \Cast\MediaCommands::LYRICS, 'clear' => !$showing, 'requestId' => $requestId, 'mediaSessionId' => $this->MediaSessionId]);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MEDIA_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }
    public function SetLike(bool $liked): bool
    {
        if (!$this->MediaSessionId) {
            $this->ReportError($this->Translate('No media playback active'));
            return false;
        }
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::USER_ACTION, ['userAction' => \Cast\MediaCommands::LIKE, 'clear' => !$liked, 'requestId' => $requestId, 'mediaSessionId' => $this->MediaSessionId]);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MEDIA_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }
    public function SetDislike(bool $disliked): bool
    {
        if (!$this->MediaSessionId) {
            $this->ReportError($this->Translate('No media playback active'));
            return false;
        }
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::USER_ACTION, ['userAction' => \Cast\MediaCommands::DISLIKE, 'clear' => !$disliked, 'requestId' => $requestId, 'mediaSessionId' => $this->MediaSessionId]);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MEDIA_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }

    public function GetAppAvailability(): bool
    {
        $requestId = $this->RequestId++;
        $request = [
            'requestId' => $requestId,
            'appId'     => array_merge(array_keys(\Cast\Apps::APPS), array_values(\Cast\Apps::APPS))
        ];
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::GET_APP_AVAILABILITY, $request);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::RECEIVER_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }
    public function DisplayWebsite(string $url, bool $disableInput, bool $autoReload): bool
    {
        if ($this->ActualApp != \Cast\Apps::DASH_CAST || $this->TransportId == '') {
            if (!$this->LaunchApp(\Cast\Apps::DASH_CAST)) {
                return false;
            }
            IPS_Sleep(1500);
        }
        $payload =
            [
                'url'       => $url,
                'reload'    => $autoReload,
                'force'     => !$disableInput
            ];
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::LOAD, $payload);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::DASH_CAST, 0, $payload]);
        // DashCast bestätigt LOAD nicht, daher ohne requestId (keine Antwortwartung)
        return (bool) $this->Send($cMsg);
    }
    public function PlayText(string $text, bool $closeApp): bool
    {
        if (strlen($text) > 200) {
            $this->ReportError($this->Translate('Text length should be less than 200 characters'));
            return false;
        }
        $url = 'http://translate.google.com/translate_tts?' .
            http_build_query(
                [
                    'ie'     => 'UTF-8',
                    'client' => 'tw-ob',
                    'q'      => $text,
                    'tl'     => IPS_GetSystemLanguage()
                ]
            );
        $this->PlaybackIsFinished = false;
        if (!$this->LoadMediaURL($url, 'audio/mpeg', false)) {
            return false;
        }
        $millis = microtime(true) + 30;
        do {
            if ($this->PlaybackIsFinished) {
                if ($closeApp) {
                    $this->CloseApp();
                }
                return true;
            }
            IPS_Sleep(5);
        } while ($millis > microtime(true));
        return false;
    }
    public function PlayYouTube(string $videoId, string $listId = ''): bool
    {
        if ($this->ActualApp != \Cast\Apps::YOUTUBE || $this->TransportId == '') {
            if (!$this->LaunchApp(\Cast\Apps::YOUTUBE)) {
                return false;
            }
            IPS_Sleep(1500);
        }
        return $this->PlayYouTubeLounge($videoId, $listId, \Cast\Youtube\BASE_URL, \Cast\Youtube\LOUNGE_TOKEN_URL, \Cast\Youtube\THEME);
    }

    public function PlayYouTubeMusic(string $videoId, string $listId = ''): bool
    {
        if ($this->ActualApp != \Cast\Apps::YOUTUBE_MUSIC || $this->TransportId == '') {
            if (!$this->LaunchApp(\Cast\Apps::YOUTUBE_MUSIC)) {
                return false;
            }
            IPS_Sleep(1500);
        }
        return $this->PlayYouTubeLounge($videoId, $listId, \Cast\YoutubeMusic\BASE_URL, \Cast\YoutubeMusic\LOUNGE_TOKEN_URL, \Cast\YoutubeMusic\THEME);
    }

    public function LoadMediaURL(string $url, string $contentType, bool $isLive): bool
    {
        if ($this->ActualApp != \Cast\Apps::DEFAULT_MEDIA_RECEIVER || $this->TransportId == '') {
            if (!$this->LaunchApp(\Cast\Apps::DEFAULT_MEDIA_RECEIVER)) {
                return false;
            }
            IPS_Sleep(1000);
        }
        $requestId = $this->RequestId++;
        $payload =
            [
                'media' => [
                    'contentUrl'  => $url,
                    'streamType'  => $isLive ? 'LIVE' : 'BUFFERED',
                    'contentType' => $contentType == '' ? self::GetMimeType(pathinfo((string) parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION)) : $contentType
                ],
                'requestId' => $requestId
            ];

        $payload = \Cast\Payload::MakePayload(\Cast\Commands::LOAD, $payload);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MEDIA_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }
    public function LoadMediaId(string $id, string $contentType, bool $isLive): bool
    {
        if ($this->ActualApp != \Cast\Apps::DEFAULT_MEDIA_RECEIVER || $this->TransportId == '') {
            if (!$this->LaunchApp(\Cast\Apps::DEFAULT_MEDIA_RECEIVER)) {
                return false;
            }
            IPS_Sleep(1000);
        }
        $requestId = $this->RequestId++;
        $payload =
            [
                'media' => [
                    'contentId'   => $id,
                    'streamType'  => $isLive ? 'LIVE' : 'BUFFERED',
                    'contentType' => $contentType
                ],
                'requestId' => $requestId
            ];

        $payload = \Cast\Payload::MakePayload(\Cast\Commands::LOAD, $payload);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MEDIA_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;

    }
    public function LoadMediaQueue(array $items, bool $repeat, int $startIndex, bool $autoplay): bool
    {
        if ($this->ActualApp != \Cast\Apps::DEFAULT_MEDIA_RECEIVER || $this->TransportId == '') {
            if (!$this->LaunchApp(\Cast\Apps::DEFAULT_MEDIA_RECEIVER)) {
                return false;
            }
            IPS_Sleep(1000);
        }
        $payloadItems = [];
        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['contentUrl'])) {
                $this->ReportError($this->Translate('Every queue item needs a contentUrl'));
                return false;
            }
            $item['contentType'] = $item['contentType'] ?? self::GetMimeType(pathinfo((string) parse_url($item['contentUrl'], PHP_URL_PATH), PATHINFO_EXTENSION));
            $payloadItems[] = [
                'media' => array_merge(\Cast\Queue::MEDIA_ITEM_KEYS, $item)
            ];
        }
        $requestId = $this->RequestId++;
        $payload = [
            'queueData' => [
                'startIndex' => $startIndex,
                'repeatMode' => $repeat ? \Cast\Queue::REPEAT_ALL : \Cast\Queue::REPEAT_OFF,
                'autoplay'   => $autoplay ? 1 : 0,
                'items'      => $payloadItems
            ],
            'requestId' => $requestId
        ];
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::LOAD, $payload);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MEDIA_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }
    /**
     * Beendet die aktuell laufende Cast App (STOP an den Receiver).
     *
     * @return bool True bei Erfolg
     */
    public function CloseApp(): bool
    {
        $sessionId = $this->SessionId;
        if ($sessionId === '') {
            $this->ReportError($this->Translate('No app running'));
            return false;
        }
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::STOP, ['requestId' => $requestId, 'sessionId' => $sessionId]);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::RECEIVER_NAMESPACE, 0, $payload]);
        $result = $this->Send($cMsg, $requestId);
        if ($result === false) {
            return false;
        }
        $this->ClearMediaVariables();
        return true;
    }

    public function RequestState(): bool
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        if (!$this->Connect()) {
            return false;
        }
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::GET_STATUS, ['requestId' => $requestId]);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::RECEIVER_NAMESPACE, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }

    public function RequestMediaState(): bool
    {
        if ($this->IsIdleScreen) {
            return $this->RequestIdleState();
        }
        if (!$this->AppSupportsMedia()) {
            $this->ReportError($this->Translate('Active app does not support media status'));
            return false;
        }
        $this->SendDebug(__FUNCTION__, '', 0);
        $requestId = $this->RequestId++;
        $urn = \Cast\Urn::MEDIA_NAMESPACE;
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::GET_STATUS, ['requestId' => $requestId]);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, $urn, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        if ($payload === false) {
            $this->SendDebug(__FUNCTION__, 'Clear MediaSessionId', 0);
            //$this->ClearMediaVariables();
        }
        return $payload ? true : false;
    }

    public function RequestIdleState(): bool
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $urn = \Cast\Urn::SSE;
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::GET_STATUS);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, $urn, 0, $payload]);
        return $this->Send($cMsg);
    }

    public function SendCommand(string $urn, string $command, array $payload = []): bool
    {
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload($command, array_merge($payload, ['requestId' => $requestId]));
        $cMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', $urn, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }

    public function SendCommandToApp(string $urn, string $command, array $payload = []): bool
    {
        $requestId = $this->RequestId++;
        $payload = \Cast\Payload::MakePayload($command, array_merge($payload, ['requestId' => $requestId]));
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, $urn, 0, $payload]);
        $payload = $this->Send($cMsg, $requestId);
        return $payload ? true : false;
    }

    public function ReceiveData(string $jsonString): string
    {
        $data = hex2bin((json_decode($jsonString))->Buffer);
        $this->DecodePacket($data);
        return '';
    }

    protected function RegisterParent(): int
    {
        $oldParentId = $this->ParentID;
        $parentId = $this->IORegisterParent();
        if ($parentId != $oldParentId) {
            if ($oldParentId > 0) {
                $this->UnregisterMessage($oldParentId, IM_CHANGESETTINGS);
            }
            if ($parentId > 0) {
                $this->RegisterMessage($parentId, IM_CHANGESETTINGS);
            }
        }
        if ($parentId > 0) {
            $this->Host = IPS_GetProperty($parentId, \Cast\IO\Property::HOST);
        } else {
            $this->Host = '';
        }
        $this->SetSummary($this->Host);
        return $parentId;
    }

    /**
     * Wird ausgeführt wenn der Kernel hochgefahren wurde.
     */
    protected function KernelReady(): void
    {
        $this->UnregisterMessage(0, IPS_KERNELSTARTED);
        $this->ApplyChanges();
    }

    /**
     * Wird ausgeführt wenn sich der Status vom Parent ändert.
     * @access protected
     */
    protected function IOChangeState(int $state): void
    {
        if ($this->StatusIsChanging) {
            $this->SendDebug('IOChangeState', 'StatusIsChanging already locked', 0);
            return;
        }
        $this->StatusIsChanging = true;
        $this->SendDebug('IOChangeState', 'StatusIsChanging now locked', 0);
        if (!$this->ReadPropertyBoolean(\Cast\Device\Property::OPEN) || ($this->Host == '')) {
            if ($this->GetStatus() != IS_INACTIVE) {
                $this->SetStatus(IS_INACTIVE);
            }
            $this->SendDebug('IOChangeState', 'StatusIsChanging now unlocked', 0);
            $this->StatusIsChanging = false;
            return;
        }
        switch ($state) {
            case IS_ACTIVE:
                $this->SetStatus(IS_ACTIVE);
                break;
            case IS_INACTIVE:
                if ($this->GetStatus() != IS_INACTIVE) {
                    $this->SetStatus(IS_INACTIVE);
                }
                break;
            default:
                if ($this->ParentID > 0) {
                    if ($this->ReadPropertyBoolean(\Cast\Device\Property::WATCHDOG)) {
                        IPS_SetProperty($this->ParentID, \Cast\IO\Property::OPEN, false);
                        @IPS_ApplyChanges($this->ParentID);
                    } else {
                        $this->SetStatus(IS_EBASE + 1);
                    }
                }
                break;
        }
        $this->SendDebug('IOChangeState', 'StatusIsChanging now unlocked', 0);
        $this->StatusIsChanging = false;
    }

    /**
     * Führt eine Bedienaktion aus.
     *
     * @param string $ident Ident der Statusvariable bzw. Aktion
     * @param mixed $value Neuer Wert
     * @return bool True bei Erfolg
     */
    private function HandleUserAction(string $ident, mixed $value): bool
    {
        switch ($ident) {
            case \Cast\Device\VariableIdent::VOLUME:
                return $this->SetVolume($value / 100);
            case \Cast\Device\VariableIdent::MUTED:
                return $this->SetMute((bool) $value);
            case \Cast\Device\VariableIdent::APP_ID:
                return $this->LaunchApp((string) $value);
            case \Cast\Device\VariableIdent::PLAYER_STATE:
                if (!isset(\Cast\PlayerState::INT_TO_ACTION[(int) $value])) {
                    $this->ReportError($this->Translate('Invalid value'));
                    return false;
                }
                return $this->SetPlayerState(\Cast\PlayerState::INT_TO_ACTION[(int) $value]);
            case \Cast\Device\VariableIdent::POSITION_RAW:
                return $this->Seek((float) $value);
            case \Cast\Device\VariableIdent::CURRENT_TIME:
                if (!$this->MediaSessionId) {
                    $this->ReportError($this->Translate('No media playback active'));
                    return false;
                }
                if (!$this->DurationRAW) {
                    $this->ReportError($this->Translate('Media playback not seekable'));
                    return false;
                }
                return $this->Seek(($this->DurationRAW / 100) * $value);
            case 'PlayYouTube':
                if (is_array($value)) {
                    return $this->PlayYouTube($value['videoId'] ?? '', $value['listId'] ?? '');
                }
                return $this->PlayYouTube((string) $value);
            case 'PlayYouTubeMusic':
                if (is_array($value)) {
                    return $this->PlayYouTubeMusic($value['videoId'] ?? '', $value['listId'] ?? '');
                }
                return $this->PlayYouTubeMusic((string) $value);
        }
        return false;
    }

    /**
     * Meldet einen Fehler an den Aufrufer.
     * Bei Bedienaktionen wird die Meldung gespeichert und von RequestAction per echo ausgegeben,
     * in allen anderen Fällen (Skript, Timer, interne Abläufe) per trigger_error gemeldet,
     * so dass interne Fehler automatisch im Symcon-Log landen.
     *
     * @param string $message Bereits übersetzte Fehlermeldung
     */
    private function ReportError(string $message): void
    {
        $this->SendDebug('Error', $message, 0);
        if ($this->isUserAction) {
            // erste Meldung behalten, sie beschreibt die Ursache
            if ($this->lastError === '') {
                $this->lastError = $message;
            }
            return;
        }
        trigger_error($message, E_USER_NOTICE);
    }

    private function PlayYouTubeLounge(string $videoId, string $listId, string $baseUrl, string $loungeTokenUrl, string $theme): bool
    {
        // Warten bis screenId empfangen wurde (maximal 5 Sekunden)
        $millis = microtime(true) + 5;
        do {
            if ($this->ScreenId !== '') {
                break;
            }
            IPS_Sleep(100);
        } while ($millis > microtime(true));

        if ($this->ScreenId === '') {
            $this->ReportError($this->Translate('No screenId received from YouTube Receiver'));
            return false;
        }

        $loungeToken = $this->GetLoungeToken($loungeTokenUrl, $baseUrl);
        if (!$loungeToken) {
            $this->ReportError($this->Translate('Could not retrieve YouTube Lounge Token'));
            return false;
        }

        return $this->SendLoungePlaybackCommand($baseUrl, $loungeToken, $videoId, $listId, $theme);
    }

    private function GetLoungeToken(string $loungeTokenUrl, string $baseUrl): ?string
    {
        $headers = [
            'Connection: keep-alive',
            'Origin: ' . $baseUrl,
            'User-Agent: Symcon CCast-Lib by Nall-chan',
            'Content-Type: application/x-www-form-urlencoded'
        ];
        $payload = [
            'screen_ids' => $this->ScreenId
        ];

        $ch = curl_init($loungeTokenUrl);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 5000);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->SendDebug('GetLoungeToken HTTP-Code', $httpCode, 0);
        $this->SendDebug('GetLoungeToken Response', $response, 0);

        if ($httpCode == 200 && is_string($response)) {
            $data = json_decode($response, true);
            if (isset($data['screens'][0]['loungeToken'])) {
                return $data['screens'][0]['loungeToken'];
            }
        }
        return null;
    }

    private function SendLoungePlaybackCommand(string $baseUrl, string $loungeToken, string $videoId, string $listId, string $theme): bool
    {
        $bindUrl = $baseUrl . 'api/lounge/bc/bind?RID=1&VER=8&C=1' .
            '&loungeIdToken=' . $loungeToken .
            '&device=REMOTE_CONTROL' .
            '&id=' . $this->TransportId .
            '&name=Desktop' .
            '&app=web' .
            '&mdx-version=3' .
            '&theme=' . $theme;
        $headers = [
            'Connection: keep-alive',
            'Origin: ' . $baseUrl,
            'User-Agent: Symcon CCast-Lib by Nall-chan',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        $params = [
            'count'             => '1',
            'req0__sc'          => 'setPlaylist',
            'req0_action'       => 'setPlaylist'
        ];

        if ($videoId !== '') {
            $params['req0_videoId'] = $videoId;
        }
        if ($listId !== '') {
            $params['req0_listId'] = $listId;
        }

        $ch = curl_init($bindUrl);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_TIMEOUT_MS, 5000);
        $this->SendDebug('SendLoungeCommand Request URL', $bindUrl, 0);
        $this->SendDebug('SendLoungeCommand Requestquery', http_build_query($params), 0);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->SendDebug('SendLoungeCommand HTTP-Code', $httpCode, 0);
        $this->SendDebug('SendLoungeCommand Response', $response, 0);

        if ($httpCode != 200) {
            $this->ReportError(sprintf($this->Translate('YouTube Lounge request failed (HTTP %d)'), $httpCode));
            return false;
        }
        // Die Lounge antwortet auch bei Ablehnung mit HTTP 200
        if (is_string($response) && preg_match('/loungeScreenDisconnected",\{"reason":"([^"]*)"/', $response, $match)) {
            $this->ReportError(sprintf($this->Translate('YouTube Lounge rejected the request: %s'), $match[1]));
            return false;
        }
        return true;
    }

    private function SendPing(): bool
    {
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::PING);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::HEARTBEAT_NAMESPACE, 0, $payload]);
        $result = $this->Send($cMsg);
        return $result;
    }

    /**
     * Wird vom Watchdog-Timer aufgerufen.
     * Prüft die Erreichbarkeit des Gerätes (Ping oder Bedingung und TCP-Port).
     * Wird erkannt, dass das Gerät erreichbar ist, wird versucht eine TCP-Verbindung aufzubauen.
     */
    private function Watchdog(): void
    {
        $this->SendDebug(__FUNCTION__, 'run', 0);
        if (!$this->ReadPropertyBoolean(\Cast\Device\Property::OPEN)) {
            return;
        }
        if ($this->Host != '') {
            if ($this->HasActiveParent()) {
                return;
            }
            if (!$this->CheckCondition()) {
                return;
            }
            if (!$this->CheckPort()) {
                return;
            }
            IPS_SetProperty($this->ParentID, \Cast\IO\Property::OPEN, true);
            @IPS_ApplyChanges($this->ParentID);
        }
    }

    private function CheckCondition(): bool
    {
        switch ($this->ReadPropertyInteger(\Cast\Device\Property::CONDITION_TYPE)) {
            case 0:
                $result = @Sys_Ping($this->Host, 500);
                $this->SendDebug('Pinging', $result, 0);
                return $result;
            case 1:
                $result = IPS_IsConditionPassing($this->ReadPropertyString(\Cast\Device\Property::WATCHDOG_CONDITION));
                $this->SendDebug('CheckCondition', $result, 0);
                return $result;
        }
        return false;
    }

    private function CheckPort(): bool
    {
        $context = stream_context_create();
        stream_context_set_option($context, 'ssl', 'verify_host', false);
        stream_context_set_option($context, 'ssl', 'verify_peer', false);
        $port = IPS_GetProperty($this->ParentID, \Cast\IO\Property::PORT);
        $socket = @stream_socket_client('tcp://' . $this->Host . ':' . $port, $errno, $errstr, 2, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            $this->SendDebug('CheckPort', false, 0);
            return false;
        }
        stream_socket_shutdown($socket, STREAM_SHUT_RDWR);
        return true;
    }

    /**
     * Aktiviert / Deaktiviert den WatchdogTimer.
     *
     * @param bool $active True für aktiv, false für inaktiv.
     */
    private function SetWatchdogTimer(bool $active): void
    {
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::OPEN)) {
            if ($this->ReadPropertyBoolean(\Cast\Device\Property::WATCHDOG)) {
                if ($active) {
                    $interval = $this->ReadPropertyInteger(\Cast\Device\Property::INTERVAL);
                    $interval = ($interval < 5) ? 0 : $interval;
                    $this->SetTimerInterval(\Cast\Device\Timer::WATCHDOG, $interval * 1000);
                    $this->SendDebug(\Cast\Device\Timer::WATCHDOG, 'active', 0);
                    return;
                }
            }
        }
        $this->SetTimerInterval(\Cast\Device\Timer::WATCHDOG, 0);
        $this->SendDebug(\Cast\Device\Timer::WATCHDOG, 'inactive', 0);
    }

    private function SetIcon(string $iconUrl): void
    {
        $mediaId = @IPS_GetObjectIDByIdent('iconUrl', $this->InstanceID);
        if ($mediaId < 10000) {
            $mediaId = IPS_CreateMedia(1);
            IPS_SetParent($mediaId, $this->InstanceID);
            IPS_SetIdent($mediaId, 'iconUrl');
            IPS_SetName($mediaId, 'App Icon');
            IPS_SetPosition($mediaId, 1);
            IPS_SetMediaCached($mediaId, true);
            $filename = 'media' . DIRECTORY_SEPARATOR . 'CCast_iconUrl_' . $this->InstanceID . '.png';
            IPS_SetMediaFile($mediaId, $filename, false);
            $this->SendDebug('Create Media', $filename, 0);
        }
        $size = $this->ReadPropertyInteger(\Cast\Device\Property::APP_ICON_SIZE_WIDTH);
        if ($iconUrl === '') {
            $mediaRAW = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'imgs' . DIRECTORY_SEPARATOR . 'no_app_image.png');
            $mediaRAW = $this->ResizeImage($mediaRAW, $size, 'png');
        } else {
            $found = strrpos($iconUrl, '=');
            if ($found !== false) {
                $iconUrl = substr($iconUrl, 0, $found);
            }
            $iconUrl .= '=w' . $size;
            $mediaRAW = @Sys_GetURLContentEx($iconUrl, ['Timeout' => 5000, 'VerifyPeer' => false, 'VerifyHost' => false]);
        }
        $this->SendDebug('Refresh IconUrl', $iconUrl, 0);
        if (!is_string($mediaRAW) || ($mediaRAW === '')) {
            $this->SendDebug('Refresh IconUrl', 'download failed', 0);
            return;
        }
        IPS_SetMediaContent($mediaId, base64_encode($mediaRAW));
    }

    private function SetMediaImage(string $mediaUrl): void
    {
        $mediaId = @IPS_GetObjectIDByIdent('mediaUrl', $this->InstanceID);
        $this->SendDebug('Refresh MediaUrl', $mediaId, 0);
        if ($mediaId < 10000) {
            $mediaId = IPS_CreateMedia(1);
            IPS_SetParent($mediaId, $this->InstanceID);
            IPS_SetIdent($mediaId, 'mediaUrl');
            IPS_SetName($mediaId, 'Media');
            IPS_SetPosition($mediaId, 13);
            IPS_SetMediaCached($mediaId, true);
            $filename = 'media' . DIRECTORY_SEPARATOR . 'CCast_mediaUrl_' . $this->InstanceID . '.jpg';
            IPS_SetMediaFile($mediaId, $filename, false);
            $this->SendDebug('Create Media', $filename, 0);
        }
        $size = $this->ReadPropertyInteger(\Cast\Device\Property::MEDIA_SIZE_WIDTH);
        if ($mediaUrl === '') {
            $iconUrl = $this->AppIconUrl;
            if ($iconUrl == '') {
                $mediaRAW = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'imgs' . DIRECTORY_SEPARATOR . 'nocover.png');
                $mediaRAW = $this->ResizeImage($mediaRAW, $size, 'png');
            } else {
                $found = strrpos($iconUrl, '=');
                if ($found !== false) {
                    $iconUrl = substr($iconUrl, 0, $found);
                }
                $iconUrl .= '=w' . $size;
                $mediaRAW = @Sys_GetURLContentEx($iconUrl, ['Timeout' => 5000, 'VerifyPeer' => false, 'VerifyHost' => false]);
            }
        } else {
            if (str_contains($mediaUrl, 'googleusercontent')) {
                $found = strrpos($mediaUrl, '=');
                if ($found !== false) {
                    $mediaUrl = substr($mediaUrl, 0, $found);
                    $mediaUrl .= '=w' . $size;
                }
                $mediaRAW = @Sys_GetURLContentEx($mediaUrl, ['Timeout' => 5000, 'VerifyPeer' => false, 'VerifyHost' => false]);
            } else {
                $mediaRAW = @Sys_GetURLContentEx($mediaUrl, ['Timeout' => 5000, 'VerifyPeer' => false, 'VerifyHost' => false]);
                if (is_string($mediaRAW) && ($mediaRAW !== '')) {
                    $mediaRAW = $this->ResizeImage($mediaRAW, $size, substr($mediaUrl, -3));
                }
            }
        }

        $this->SendDebug('Refresh mediaUrl', $mediaUrl, 0);
        if (is_string($mediaRAW) && ($mediaRAW !== '')) {
            IPS_SetMediaContent($mediaId, base64_encode($mediaRAW));
        } else {
            $this->SendDebug('Refresh mediaUrl', 'download failed', 0);
        }
    }
    private function ResizeImage(string $imageRaw, int $sizeWidth, string $format = 'jpg'): string
    {
        // Kann das Bild nicht dekodiert werden, werden die Rohdaten unverändert zurückgegeben
        $thumbRAW = $imageRaw;
        $image = @imagecreatefromstring($imageRaw);
        if ($image !== false) {
            $width = imagesx($image);
            $height = imagesy($image);
            $factor = 1;
            if ($sizeWidth > 0) {
                if ($width > $sizeWidth) {
                    $factor = $width / $sizeWidth;
                }
            }
            if ($factor != 1) {
                $image = imagescale($image, (int) ($width / $factor), (int) ($height / $factor));
            }
            if ($format == 'png') {
                imagealphablending($image, false);
                imagesavealpha($image, true);
                ob_start();
                @imagepng($image);
                $thumbRAW = ob_get_contents(); // read from buffer
                ob_end_clean(); // delete buffer
            } else {
                ob_start();
                @imagejpeg($image);
                $thumbRAW = ob_get_contents(); // read from buffer
                ob_end_clean(); // delete buffer

            }
        }

        return $thumbRAW;
    }
    private function Send(\Cast\CastMessage $cMsg, int $requestId = 0): bool|array
    {
        if ($cMsg->GetUrn() != \Cast\Urn::HEARTBEAT_NAMESPACE) {
            $this->SendDebug('Send (' . $requestId . ')', $cMsg->GetDebugData(), 0);
        }
        // Ohne aktive Verbindung nicht senden, sonst erzeugt der Client-Socket eine Warnung
        if (!$this->HasActiveParent()) {
            $this->SendDebug('Send (' . $requestId . ')', 'no active parent', 0);
            $this->ReportError($this->Translate('Device not connected'));
            return false;
        }
        if ($requestId) {
            $this->SendQueuePush($requestId);
        }
        $sendResult = @$this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', 'Buffer' => bin2hex($cMsg->GetMessage())]));
        if ($sendResult === false) {
            if ($requestId) {
                $this->SendQueueRemove($requestId);
            }
            $this->ReportError($this->Translate('Error on sending data to device'));
            $this->SetStatus(IS_EBASE + 1);
            return false;
        }
        if ($requestId == 0) {
            return true;
        }
        $result = $this->WaitForResponse($requestId);
        $this->SendDebug('Result (' . $requestId . ')', $result, 0);
        if ($result) {
            $result['type'] = $result['type'] ?? $result['responseType'] ?? '';
            $this->DecodeEvent($cMsg, $result);
            if (in_array($result['type'], self::ERROR_RESPONSES, true)) {
                $reason = $result['reason'] ?? $result['detailedErrorCode'] ?? '';
                $this->ReportError(sprintf($this->Translate('Device reported an error: %s'), trim($result['type'] . ' ' . $reason)));
                return false;
            }
            return $result;
        }
        $this->ReportError($this->Translate('Device did not respond'));
        return false;
    }

    private function SendPong(string $receiverId): void
    {
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::PONG);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $receiverId, \Cast\Urn::HEARTBEAT_NAMESPACE, 0, $payload]);
        $this->Send($cMsg);
    }

    private function Connect(string $receiverId = 'receiver-0'): bool
    {
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::CONNECT);
        $cMsg = new \Cast\CastMessage([$this->InstanceID, $receiverId, \Cast\Urn::CONNECTION_NAMESPACE, 0, $payload]);
        return (bool) $this->Send($cMsg);
    }

    /**
     * DecodeEvent
     *
     * @todo decodieren weiter ausbauen
     * @param  mixed $cMsg
     * @param  array $payload
     * @return void
     */
    private function DecodeEvent(\Cast\CastMessage $cMsg, array $payload): void
    {
        $payload['type'] = $payload['type'] ?? $payload['responseType'] ?? '';
        switch ($cMsg->GetUrn()) {
            case \Cast\Urn::HEARTBEAT_NAMESPACE:
                switch ($payload['type']) {
                    case \Cast\Commands::PING:
                        IPS_RunscriptText('IPS_Sleep(5);IPS_RequestAction(' . $this->InstanceID . ',\'' . \Cast\Commands::PONG . '\',\'' . $cMsg->GetReceiverId() . '\');');
                        break;
                }
                break;
            case \Cast\Urn::CONNECTION_NAMESPACE:
                switch ($payload['type']) {
                    case \Cast\Commands::CLOSE:
                        if ($cMsg->GetSourceId() == $this->TransportId) {
                            $this->SendDebug(__FUNCTION__, 'Clear TransportId & MediaSessionId', 0);
                            // Virtuelle Verbindung zur App wurde vom Gerät geschlossen, beim nächsten Befehl neu verbinden
                            $this->TransportId = '';
                            $this->ClearMediaVariables();
                        }
                        break;
                }
                break;
            case \Cast\Urn::SSE:
                if (array_key_exists('backendData', $payload)) {
                    $backendData = json_decode((string) $payload['backendData'], true);
                    if (!is_array($backendData)) {
                        break;
                    }
                    if (isset($backendData[0]) && is_string($backendData[0])) {
                        $this->SetMediaImage($backendData[0]);
                    }
                    $this->SetValue(\Cast\Device\VariableIdent::COLLECTION, is_string($backendData[12] ?? null) ? $backendData[12] : '');
                }
                break;
            case \Cast\Urn::MEDIA_NAMESPACE:
                switch ($payload['type']) {
                    case \Cast\Commands::MEDIA_STATUS:
                        if (empty($payload['status']) || !is_array($payload['status'])) {
                            break;
                        }
                        $status = array_shift($payload['status']);
                        if (isset($status['mediaSessionId'])) {
                            if ($this->MediaSessionId != $status['mediaSessionId']) {
                                $this->MediaSessionId = $status['mediaSessionId'];
                                if (!array_key_exists('media', $status)) {
                                    $this->ScheduleMediaStateFollowUp(1);
                                }
                            }
                        } else {
                            $this->MediaSessionId = 0;
                        }
                        //todo doch auch customData:playerState ?! 5 = stop, 3 = buffer, 1 = play, 2= pause
                        if (isset($status['playerState'])) {
                            if ($status['playerState'] != \Cast\PlayerState::BUFFERING) {
                                if (isset($status['supportedMediaCommands'])) {
                                    if (isset($status['queueData'])) {
                                        $status['supportedMediaCommands'] += 64;
                                    }
                                    $this->UpdateControlsByMediaCommand($status['supportedMediaCommands']);
                                }
                                // Unbekannte Zustände (z.B. LOADING) nicht auf die Variable abbilden
                                if (isset(\Cast\PlayerState::STATE_TO_INT[$status['playerState']])) {
                                    $this->SetPlayerStateValue(\Cast\PlayerState::STATE_TO_INT[$status['playerState']]);
                                }
                            }
                            if ($status['playerState'] == \Cast\PlayerState::PLAY) {
                                $this->SetTimerInterval(\Cast\Device\Timer::PROGRESS_STATE, 1000);
                            } else {
                                $this->SetTimerInterval(\Cast\Device\Timer::PROGRESS_STATE, 0);
                            }
                            // Das Ende vom Laden/Puffern meldet das Gerät nicht immer per Event, daher Status nachfragen
                            if (($status['playerState'] == \Cast\PlayerState::BUFFERING) || (($status['extendedStatus']['playerState'] ?? '') == \Cast\PlayerState::LOADING)) {
                                $this->ScheduleMediaStateFollowUp(2000);
                            }

                        }
                        if (isset($status[\Cast\Device\VariableIdent::CURRENT_TIME])) {
                            if ($this->DurationRAW) {
                                $value = (100 / $this->DurationRAW) * $status[\Cast\Device\VariableIdent::CURRENT_TIME];
                                $this->SetValue(\Cast\Device\VariableIdent::CURRENT_TIME, $value);
                            } else {
                                $this->SetValue(\Cast\Device\VariableIdent::CURRENT_TIME, 0);
                            }
                            $this->PositionRAW = $status[\Cast\Device\VariableIdent::CURRENT_TIME];
                            $this->SetValue(\Cast\Device\VariableIdent::POSITION, \Cast\Device\TimeConvert::ConvertSeconds($status[\Cast\Device\VariableIdent::CURRENT_TIME]));
                            if ($this->ReadPropertyBoolean(\Cast\Device\Property::ENABLE_RAW_POSITION)) {
                                $this->SetValue(\Cast\Device\VariableIdent::POSITION_RAW, (int) $status[\Cast\Device\VariableIdent::CURRENT_TIME]);
                            }
                        } else {
                            $this->PositionRAW = 0;
                            $this->SetValue(\Cast\Device\VariableIdent::POSITION, '');
                            if ($this->ReadPropertyBoolean(\Cast\Device\Property::ENABLE_RAW_POSITION)) {
                                $this->SetValue(\Cast\Device\VariableIdent::POSITION_RAW, 0);
                            }
                        }
                        if (isset($status['idleReason']) && ($status['idleReason'] == 'FINISHED')) {
                            $this->PlaybackIsFinished = true;
                        }
                        if (array_key_exists('media', $status)) {
                            $media = $status['media'];
                            if (isset($media['metadata']['metadataType'])) {
                                $metaData = $media['metadata'];
                                switch ($metaData['metadataType']) {
                                    case 0: //GenericMediaMetadata
                                        // title string
                                        // subtitle string
                                        // images array url/width/height
                                        // releaseDate string iso 8601
                                        break;
                                    case 1: //MovieMediaMetadata
                                        // title string
                                        // subtitle string
                                        // studio string
                                        // images array url/width/height
                                        // releaseDate string iso 8601
                                        break;
                                    case 2: //TvShowMediaMetadata
                                        // seriesTitle string
                                        // subtitle string
                                        // season int
                                        // episode int
                                        // images array url/width/height
                                        // originalAirDate string iso 8601
                                        break;
                                    case 3: //MusicTrackMediaMetadata
                                        // albumName string
                                        // title string
                                        // albumArtist string
                                        // artist string
                                        // composer string
                                        // trackNumber int
                                        // discNumber int
                                        // images array url/width/height
                                        // releaseDate string iso 8601
                                        break;
                                    case 4: //PhotoMediaMetadata
                                        // title string
                                        // artist string
                                        // location string
                                        // latitude float
                                        // longitude float
                                        // width int
                                        // height int
                                        // creationDateTime string iso 8601
                                        break;
                                }
                            }

                            if (isset($media['metadata'][\Cast\Device\VariableIdent::TITLE])) {
                                $this->SetValue(\Cast\Device\VariableIdent::TITLE, $media['metadata'][\Cast\Device\VariableIdent::TITLE]);
                            } else {
                                $this->SetValue(\Cast\Device\VariableIdent::TITLE, '');
                            }
                            // Künstler: artist, bei Musik ersatzweise albumArtist,
                            // bei GenericMediaMetadata (Typ 0, z.B. Musikvideos bei YouTube Music) ersatzweise subtitle.
                            // Bei Filmen/Serien (Typ 1/2) enthält subtitle eine Beschreibung bzw. den Episodentitel und wird nicht genutzt.
                            $artist = $media['metadata'][\Cast\Device\VariableIdent::ARTIST] ?? $media['metadata']['albumArtist'] ?? '';
                            if (($artist === '') && ((int) ($media['metadata']['metadataType'] ?? 0) === 0)) {
                                $artist = $media['metadata']['subtitle'] ?? '';
                            }
                            $this->SetValue(\Cast\Device\VariableIdent::ARTIST, (string) $artist);
                            if (isset($media['metadata']['albumName'])) {
                                $this->SetValue(\Cast\Device\VariableIdent::COLLECTION, $media['metadata']['albumName']);
                            } else {
                                if (isset($media['customData']['mediaItem'][\Cast\Device\VariableIdent::TITLE])) {
                                    $this->SetValue(\Cast\Device\VariableIdent::COLLECTION, $media['customData']['mediaItem'][\Cast\Device\VariableIdent::TITLE]);
                                } else {
                                    $this->SetValue(\Cast\Device\VariableIdent::COLLECTION, '');
                                }
                            }
                            if (isset($media['metadata']['images'])) {
                                $key = 0;
                                if (isset($media['metadata']['images'][0]['width'])) {
                                    $width = array_column($media['metadata']['images'], 'width');
                                    array_multisort($width, SORT_ASC, SORT_NUMERIC, $media['metadata']['images']);
                                    $size = $this->ReadPropertyInteger(\Cast\Device\Property::MEDIA_SIZE_WIDTH);
                                    foreach ($width as $key => $value) {
                                        if ($value >= $size) {
                                            break;
                                        }
                                    }
                                }
                                if ($this->MediaUrl != $media['metadata']['images'][$key]['url']) {
                                    $this->MediaUrl = $media['metadata']['images'][$key]['url'];
                                    $this->SetMediaImage($media['metadata']['images'][$key]['url']);
                                }
                            } else {
                                if (isset($media['contentType'])) {
                                    if (str_starts_with($media['contentType'], 'image/')) {
                                        if ($this->MediaUrl != $media['contentUrl']) {
                                            $this->MediaUrl = $media['contentUrl'];
                                            $this->SetMediaImage($media['contentUrl']);
                                        }
                                    }
                                }
                            }
                            //customData:artists
                            //customData:title

                            if (isset($media[\Cast\Device\VariableIdent::DURATION])) {
                                $this->DurationRAW = $media[\Cast\Device\VariableIdent::DURATION];
                                $this->SetValue(\Cast\Device\VariableIdent::DURATION, \Cast\Device\TimeConvert::ConvertSeconds($media[\Cast\Device\VariableIdent::DURATION]));
                                if ($this->ReadPropertyBoolean(\Cast\Device\Property::ENABLE_RAW_DURATION)) {
                                    $this->SetValue(\Cast\Device\VariableIdent::DURATION_RAW, (int) $media[\Cast\Device\VariableIdent::DURATION]);
                                }
                            } else {
                                $this->DurationRAW = 0;
                                $this->SetValue(\Cast\Device\VariableIdent::DURATION, '');
                                if ($this->ReadPropertyBoolean(\Cast\Device\Property::ENABLE_RAW_DURATION)) {
                                    $this->SetValue(\Cast\Device\VariableIdent::DURATION_RAW, 0);
                                }
                            }
                            // Fortschritt erst hier berechnen, currentTime wird vor der Dauer ausgewertet
                            if ($this->DurationRAW) {
                                $this->SetValue(\Cast\Device\VariableIdent::CURRENT_TIME, (100 / $this->DurationRAW) * $this->PositionRAW);
                            }
                        }

                        //queueData
                        /*
                        if (isset($Status['queueData']['repeatMode'])) {
                            $this->SetValue(\Cast\Device\VariableIdent::REPEAT_MODE, $status['queueData']['repeatMode']);
                        } elseif (isset($Status['repeatMode'])) {
                            $this->SetValue(\Cast\Device\VariableIdent::REPEAT_MODE, $status['repeatMode']);
                        } else {
                            $this->SetValue(\Cast\Device\VariableIdent::REPEAT_MODE,'');
                        }
                        //queueData:repeatMode | REPEAT_OFF
                        //queueData:shuffle | FALSE
                         */
                        //items
                        //items:0:media:duration
                        //items:0:media:metadata:title
                        //items:0:media:customData:mediaItem:title
                        //items:0:media:customData:artists
                        //items:0:media:customData:title
                        break;
                }
                break;
            case \Cast\Urn::MULTI_ZONE_NAMESPACE:

                break;
            case \Cast\Urn::RECEIVER_NAMESPACE:
                switch ($payload['type']) {
                    case \Cast\Commands::GET_APP_AVAILABILITY:
                        // @todo auswerten
                        break;
                    case \Cast\Commands::LAUNCH_STATUS:
                        break;
                    case \Cast\Commands::LAUNCH_ERROR:
                        break;
                    case \Cast\Commands::RECEIVER_STATUS:
                        $status = $payload['status'] ?? [];

                        if (isset($status['volume']['level'])) {
                            $this->SetValue(\Cast\Device\VariableIdent::VOLUME, (int) ($status['volume']['level'] * 100));
                        }
                        if (isset($status['volume']['muted'])) {
                            $this->SetValue(\Cast\Device\VariableIdent::MUTED, $status['volume']['muted']);
                        }

                        if (!empty($status['applications']) && is_array($status['applications'])) {
                            $actualApp = array_shift($status['applications']);
                            if (array_key_exists('iconUrl', $actualApp)) {
                                if ($this->AppIconUrl != $actualApp['iconUrl']) {
                                    $this->AppIconUrl = $actualApp['iconUrl'];
                                    $this->SetIcon($actualApp['iconUrl']);
                                }
                            }
                            $found = array_search($actualApp['displayName'] ?? '', \Cast\Apps::APPS);
                            if ($found !== false) {
                                $actualApp[\Cast\Device\VariableIdent::APP_ID] = $found;
                            }
                            $actualApp[\Cast\Device\VariableIdent::APP_ID] = (string) ($actualApp[\Cast\Device\VariableIdent::APP_ID] ?? '');
                            if ($found === false) {
                                $this->LearnApp($actualApp[\Cast\Device\VariableIdent::APP_ID], (string) ($actualApp['displayName'] ?? ''));
                            }
                            $this->SetValue(\Cast\Device\VariableIdent::APP_ID, $actualApp[\Cast\Device\VariableIdent::APP_ID]);
                            $this->IsIdleScreen = (bool) ($actualApp['isIdleScreen'] ?? false);
                            if ($this->ActualApp != $actualApp[\Cast\Device\VariableIdent::APP_ID]) {
                                $this->ActualApp = $actualApp[\Cast\Device\VariableIdent::APP_ID];

                            }
                            $this->SessionId = (string) ($actualApp['sessionId'] ?? '');
                            $this->AppNamespaces = array_column($actualApp['namespaces'] ?? [], 'name');
                            $newTransportId = (string) ($actualApp['transportId'] ?? $actualApp['sessionId'] ?? '');
                            if ($this->TransportId != $newTransportId) {
                                // screenId/deviceId gehören zur vorherigen App und werden per mdxSessionStatus neu gemeldet
                                $this->ScreenId = '';
                                $this->DeviceId = '';
                                $this->ClearMediaVariables();
                                $this->TransportId = $newTransportId;
                                IPS_RunScriptText('IPS_Sleep(5);IPS_RequestAction(' . $this->InstanceID . ',"' . \Cast\Commands::CONNECT . '","' . $this->TransportId . '");');
                            }
                        }
                        break;
                }
                break;
            case \Cast\Urn::YOUTUBE:
                $this->ScreenId = $payload['data']['screenId'] ?? '';
                $this->DeviceId = $payload['data']['deviceId'] ?? '';
                break;
            default:
                break;
        }
    }

    /**
     * Plant eine verzögerte Medienstatus-Abfrage für die aktuelle App.
     * Es steht immer nur eine Abfrage aus; sie wird verworfen, wenn die App bis dahin gewechselt hat.
     *
     * @param int $delay Verzögerung in Millisekunden
     */
    private function ScheduleMediaStateFollowUp(int $delay): void
    {
        if ($this->MediaStateFollowUp > microtime(true)) {
            return;
        }
        // Sicherheitsablauf, falls die geplante Abfrage nicht ausgeführt wird
        $this->MediaStateFollowUp = microtime(true) + ($delay / 1000) + 5;
        IPS_RunScriptText('IPS_Sleep(' . $delay . ');IPS_RequestAction(' . $this->InstanceID . ',"' . self::ACTION_MEDIA_STATE_FOLLOW_UP . '","' . $this->TransportId . '");');
    }

    /**
     * Prüft, ob die aktuelle App den Media-Namespace unterstützt.
     */
    private function AppSupportsMedia(): bool
    {
        return in_array(\Cast\Urn::MEDIA_NAMESPACE, $this->AppNamespaces, true);
    }

    /**
     * Name des App-Profils dieser Instanz.
     */
    private function GetAppProfileName(): string
    {
        return 'CCast.AppId.' . (string) $this->InstanceID;
    }

    /**
     * Liefert die gelernten Apps (AppId => Name).
     */
    private function GetKnownApps(): array
    {
        $knownApps = json_decode($this->ReadAttributeString(\Cast\Device\Attribute::KNOWN_APPS), true);
        return is_array($knownApps) ? $knownApps : [];
    }

    /**
     * Nimmt eine vom Gerät gemeldete, unbekannte App in das App-Profil auf.
     * Als Name wird der displayName vom Gerät genutzt, fehlt dieser wird er bei Google ermittelt.
     *
     * @param string $appId AppId vom Gerät
     * @param string $displayName Name vom Gerät
     */
    private function LearnApp(string $appId, string $displayName): void
    {
        if (($appId === '') || isset(\Cast\Apps::APPS[$appId])) {
            return;
        }
        $knownApps = $this->GetKnownApps();
        if (isset($knownApps[$appId]) && (($displayName === '') || ($knownApps[$appId] === $displayName))) {
            return;
        }
        $name = ($displayName !== '') && ($displayName !== $appId) ? $displayName : \Cast\Apps::GetAppNameFromGoogle($appId);
        if ($name === '') {
            $name = $appId;
        }
        $knownApps[$appId] = $name;
        $this->WriteAttributeString(\Cast\Device\Attribute::KNOWN_APPS, json_encode($knownApps));
        $this->SendDebug('Learned App', $appId . ' => ' . $name, 0);
        IPS_SetVariableProfileAssociation($this->GetAppProfileName(), $appId, $name, '', -1);
    }

    /**
     * Löscht die gelernten Apps und setzt das App-Profil auf die eingebauten Apps zurück.
     */
    private function ResetKnownApps(): void
    {
        $this->WriteAttributeString(\Cast\Device\Attribute::KNOWN_APPS, '[]');
        $this->RegisterProfileStringEx($this->GetAppProfileName(), '', '', '', \Cast\Apps::GetAllAppsAsProfileAssociation());
        echo $this->Translate('Learned apps were reset');
    }

    private function ClearMediaVariables(): void
    {
        $this->SetTimerInterval(\Cast\Device\Timer::PROGRESS_STATE, 0);
        $this->PositionRAW = 0;
        $this->IsSeekable = false;
        $this->DurationRAW = 0;
        $this->MediaSessionId = 0;
        //$this->TransportId = '';
        $this->SetValue(\Cast\Device\VariableIdent::TITLE, '');
        $this->SetValue(\Cast\Device\VariableIdent::ARTIST, '');
        $this->SetValue(\Cast\Device\VariableIdent::COLLECTION, '');
        $this->SetValue(\Cast\Device\VariableIdent::DURATION, '');
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::ENABLE_RAW_DURATION)) {
            $this->SetValue(\Cast\Device\VariableIdent::DURATION_RAW, 0);
        }
        $this->SetValue(\Cast\Device\VariableIdent::CURRENT_TIME, 0);
        $this->SetValue(\Cast\Device\VariableIdent::POSITION, '');
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::ENABLE_RAW_POSITION)) {
            $this->SetValue(\Cast\Device\VariableIdent::POSITION_RAW, 0);
        }
        $this->UpdateControlsByMediaCommand(0);
        $this->SetPlayerStateValue(\Cast\PlayerState::STATE_TO_INT[\Cast\PlayerState::IDLE]);
    }

    /**
     * Setzt den Wiedergabestatus passend zum aktiven Profil.
     * Die Profile ~Playback*NoStop kennen keinen Stop (1), dort wird IDLE als Pause (3) dargestellt,
     * damit die Media-Kachel den Play-Knopf anzeigt.
     *
     * @param int $state Wert aus PlayerState::STATE_TO_INT
     */
    private function SetPlayerStateValue(int $state): void
    {
        if ($state == \Cast\PlayerState::STATE_TO_INT[\Cast\PlayerState::IDLE]) {
            $profile = IPS_GetVariable($this->GetIDForIdent(\Cast\Device\VariableIdent::PLAYER_STATE))['VariableProfile'];
            if (in_array($profile, self::PLAYBACK_PROFILES_WITHOUT_STOP, true)) {
                $state = \Cast\PlayerState::STATE_TO_INT[\Cast\PlayerState::PAUSE];
            }
        }
        $this->SetValue(\Cast\Device\VariableIdent::PLAYER_STATE, $state);
    }

    private function UpdateControlsByMediaCommand(int $mediaCommand): void
    {
        $commands = \Cast\MediaCommands::ListAvailableCommands($mediaCommand);
        $this->SendDebug('COMMANDS', $commands, 0);
        if ($this->SupportedMediaCommands == $mediaCommand) {
            return;
        }
        $this->SupportedMediaCommands = $mediaCommand;
        $profile = '~PlaybackPreviousNextNoStop';
        if (!in_array(\Cast\MediaCommands::PAUSE, $commands)) {
            if (in_array(\Cast\MediaCommands::NEXT, $commands)) {
                $profile = '~PlaybackPreviousNextNoStop';
            } else {
                $profile = '~PlaybackNoStop';
            }
        } else {
            if (in_array(\Cast\MediaCommands::NEXT, $commands)) {
                $profile = '~PlaybackPreviousNext';
            } else {
                $profile = '~Playback';
            }
        }
        $this->RegisterVariableInteger(\Cast\Device\VariableIdent::PLAYER_STATE, $this->Translate('Player State'), $profile, self::POSITION_PLAYER_STATE);

        /*
        if (in_array(\Cast\MediaCommands::REPEAT_ALL, $Commands) || in_array(\Cast\MediaCommands::REPEAT_ONE, $Commands)) {
            $this->EnableAction(\Cast\Device\VariableIdent::REPEAT_MODE);
        } else {
            $this->DisableAction(\Cast\Device\VariableIdent::REPEAT_MODE);
        }
         */

        /*
        if (in_array(\Cast\MediaCommands::SHUFFLE, $commands)) {
            $this->EnableAction(\Cast\Device\VariableIdent::SHUFFLE);
        } else {
            $this->DisableAction(\Cast\Device\VariableIdent::SHUFFLE);
        }
         */
        if (in_array(\Cast\MediaCommands::SEEK, $commands)) {
            $this->IsSeekable = true;
            if ($this->ReadPropertyBoolean(\Cast\Device\Property::ENABLE_RAW_POSITION)) {
                $this->EnableAction(\Cast\Device\VariableIdent::POSITION_RAW);
            }
            $this->EnableAction(\Cast\Device\VariableIdent::CURRENT_TIME);
        } else {
            $this->IsSeekable = false;
            if ($this->ReadPropertyBoolean(\Cast\Device\Property::ENABLE_RAW_POSITION)) {
                $this->DisableAction(\Cast\Device\VariableIdent::POSITION_RAW);
            }
            $this->DisableAction(\Cast\Device\VariableIdent::CURRENT_TIME);
        }
    }
    private static function GetMimeType(string $extension): string
    {
        $extension = strtolower($extension);
        $mimeFile = IPS_GetKernelDirEx() . 'mime.types';
        $lines = is_file($mimeFile) ? file($mimeFile) : false;
        if ($lines === false) {
            return 'text/plain';
        }
        foreach ($lines as $line) {
            $type = explode("\t", $line, 2);
            if (count($type) == 2) {
                $types = explode(' ', trim($type[1]));
                foreach ($types as $ext) {
                    if ($ext == $extension) {
                        return $type[0];
                    }
                }
            }
        }
        return 'text/plain';
    }

    private function DecodePacket(string $data): void
    {
        $data = $this->Buffer . $data;
        $len = unpack('N', substr($data, 0, 4))[1];
        if (strlen($data) < $len + 4) {
            $this->Buffer = $data;
            return;
        }
        $part = substr($data, 4, $len);
        $tail = substr($data, 4 + $len, $len);
        $this->Buffer = $tail;
        $cMsg = new \Cast\CastMessage($part);

        $payload = $cMsg->GetPayload();
        if ($payload) {
            $isEvent = true;
            if (array_key_exists('requestId', $payload)) {
                if ($payload['requestId'] != 0) {
                    $isEvent = false;
                    $this->SendQueueUpdate($payload);
                }
            }
            if ($isEvent) {
                if ($cMsg->GetUrn() != \Cast\Urn::HEARTBEAT_NAMESPACE) {
                    $this->SendDebug('EVENT', $cMsg->GetDebugData(), 0);
                }
                $this->DecodeEvent($cMsg, $payload);
            }
        }
        if (strlen($tail) > 4) {
            $this->DecodePacket('');
        }
    }

    /**
     * Wartet auf eine Antwort einer Anfrage
     *
     * @param int $requestId
     * @return array|false Enthält ein Array mit den Daten der Antwort. False bei einem Timeout
     */
    private function WaitForResponse(int $requestId): false|array
    {
        $millis = microtime(true) + 10;
        do {
            $buffer = $this->ReplyCMsgPayload;
            if (!array_key_exists($requestId, $buffer)) {
                return false;
            }
            if (count($buffer[$requestId])) {
                $this->SendQueueRemove($requestId);
                return $buffer[$requestId];
            }
            IPS_Sleep(5);
        } while ($millis > microtime(true));
        $this->SendQueueRemove($requestId);
        return false;
    }

    //################# SENDQUEUE

    /**
     * Fügt eine Anfrage in die SendQueue ein.
     *
     * @param int $requestId
     */
    private function SendQueuePush(int $requestId): void
    {
        if (!$this->lock('ReplyCMsgPayload')) {
            throw new Exception($this->Translate('ReplyCMsgPayload is locked'), E_USER_NOTICE);
        }
        $data = $this->ReplyCMsgPayload;
        $data[$requestId] = [];
        $this->ReplyCMsgPayload = $data;
        $this->unlock('ReplyCMsgPayload');
    }

    /**
     * Fügt eine Antwort in die SendQueue ein.
     *
     * @param array Payload
     *
     * @return bool True wenn Anfrage zur Antwort gefunden wurde, sonst false.
     */
    private function SendQueueUpdate(array $payload): bool
    {
        if (!$this->lock('ReplyCMsgPayload')) {
            throw new Exception($this->Translate('ReplyCMsgPayload is locked'), E_USER_NOTICE);
        }
        $data = $this->ReplyCMsgPayload;
        if (array_key_exists($payload['requestId'], $data)) {
            $data[$payload['requestId']] = $payload;
            $this->ReplyCMsgPayload = $data;
            $this->unlock('ReplyCMsgPayload');
            return true;
        }
        $this->unlock('ReplyCMsgPayload');
        return false;
    }

    /**
     * Löscht einen Eintrag aus der SendQueue.
     *
     * @param int $requestId Der Index des zu löschenden Eintrags.
     */
    private function SendQueueRemove(int $requestId): void
    {
        if (!$this->lock('ReplyCMsgPayload')) {
            throw new Exception($this->Translate('ReplyCMsgPayload is locked'), E_USER_NOTICE);
        }
        $data = $this->ReplyCMsgPayload;
        unset($data[$requestId]);
        $this->ReplyCMsgPayload = $data;
        $this->unlock('ReplyCMsgPayload');
    }
}