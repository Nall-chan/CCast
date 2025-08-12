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
 * @property string $actualApp //actual App
 * @property string $TransportId //actual App
 * @property string $screenId // mdxSessionStatus
 * @property string $deviceId // mdxSessionStatus
 * @property int $MediaSessionId //actual MediaSession
 * @property string $MediaUrl
 * @property string $AppIconUrl
 * @property float $PositionRAW
 * @property bool $isSeekable
 * @property float $DurationRAW
 * @property int $supportedMediaCommands
 * @property bool $StatusIsChanging
 * @property bool $isIdleScreen
 * @method bool lock(string $ident)
 * @method void unlock(string $ident)
 * @method void SetValueBoolean(string $Ident, bool $value)
 * @method void SetValueFloat(string $Ident, float $value)
 * @method void SetValueInteger(string $Ident, int $value)
 * @method void SetValueString(string $Ident, string $value)
 * @method void RegisterProfileStringEx(string $Name, string $Icon, string $Prefix, string $Suffix, array $Associations)
 * @method void UnregisterProfile(string $Name)
 * @method bool SendDebug(string $Message, mixed $Data, int $Format)
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
    public function Create(): void
    {
        //Never delete this line!
        parent::Create();
        $this->ParentID = 0;
        $this->Host = '';
        $this->Buffer = '';
        $this->RequestId = 1;
        $this->ReplyCMsgPayload = [];
        $this->actualApp = '';
        $this->TransportId = '';
        $this->screenId = '';
        $this->deviceId = '';
        $this->MediaSessionId = 0;
        $this->MediaUrl = '';
        $this->AppIconUrl = '';
        $this->PositionRAW = 0;
        $this->isSeekable = false;
        $this->DurationRAW = 0;
        $this->supportedMediaCommands = 0;
        $this->StatusIsChanging = false;
        $this->isIdleScreen = true;
        $this->RegisterPropertyBoolean(\Cast\Device\Property::Open, false);
        $this->RegisterPropertyInteger(\Cast\Device\Property::Port, 8009);
        $this->RegisterPropertyBoolean(\Cast\Device\Property::Watchdog, true);
        $this->RegisterPropertyInteger(\Cast\Device\Property::Interval, 5);
        $this->RegisterPropertyInteger(\Cast\Device\Property::ConditionType, 0);
        $this->RegisterPropertyString(\Cast\Device\Property::WatchdogCondition, '');
        $this->RegisterPropertyInteger(\Cast\Device\Property::MediaSizeWidth, 512);
        $this->RegisterPropertyInteger(\Cast\Device\Property::AppIconSizeWidth, 90);
        $this->RegisterPropertyBoolean(\Cast\Device\Property::EnableRawDuration, true);
        $this->RegisterPropertyBoolean(\Cast\Device\Property::EnableRawPosition, true);
        $this->RegisterTimer(\Cast\Device\Timer::ProgressState, 0, 'IPS_RequestAction(' . $this->InstanceID . ',"' . \Cast\Device\Timer::ProgressState . '",true);');
        $this->RegisterTimer(\Cast\Device\Timer::KeepAlive, 0, 'IPS_RequestAction(' . $this->InstanceID . ',"' . \Cast\Device\Timer::KeepAlive . '",true);');
        $this->RegisterTimer(\Cast\Device\Timer::Watchdog, 0, 'IPS_RequestAction(' . $this->InstanceID . ',"' . \Cast\Device\Timer::Watchdog . '",true);');

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

        $this->supportedMediaCommands = 0;
        $this->Buffer = '';
        $this->RequestId = 1;
        $this->ReplyCMsgPayload = [];
        $this->actualApp = '';
        $this->TransportId = '';
        $this->screenId = '';
        $this->deviceId = '';
        $this->MediaUrl = '';
        $this->AppIconUrl = '';
        $this->PositionRAW = 0;
        $this->isSeekable = false;
        $this->DurationRAW = 0;
        $this->isIdleScreen = true;

        $this->SetWatchdogTimer(false);

        parent::ApplyChanges();
        $i = 0;
        $this->RegisterProfileStringEx('CCast.AppId.' . (string) $this->InstanceID, '', '', '', \Cast\Apps::getAllAppsAsProfileAssoziation());
        $this->RegisterVariableString(\Cast\Device\VariableIdent::AppId, $this->Translate('Active app'), 'CCast.AppId.' . (string) $this->InstanceID, ++$i);
        $this->EnableAction(\Cast\Device\VariableIdent::AppId);

        $this->RegisterVariableInteger(\Cast\Device\VariableIdent::Volume, $this->Translate('Volume'), '~Volume', ++$i);
        $this->EnableAction(\Cast\Device\VariableIdent::Volume);
        $this->RegisterVariableBoolean(\Cast\Device\VariableIdent::Muted, $this->Translate('Muted'), '~Mute', ++$i);
        $this->EnableAction(\Cast\Device\VariableIdent::Muted);

        $this->RegisterVariableInteger(\Cast\Device\VariableIdent::PlayerState, $this->Translate('Player State'), '~PlaybackPreviousNextNoStop', ++$i);
        $this->EnableAction(\Cast\Device\VariableIdent::PlayerState);

        //$this->RegisterVariableString(\Cast\Device\VariableIdent::RepeatMode, $this->Translate('Repeat'), '', ++$i);

        if ($this->ReadPropertyBoolean(\Cast\Device\Property::EnableRawDuration)) {
            $this->RegisterVariableInteger(\Cast\Device\VariableIdent::DurationRaw, $this->Translate('Duration in seconds'), '', ++$i);
        } else {
            $this->UnregisterVariable(\Cast\Device\VariableIdent::DurationRaw);
        }

        if ($this->ReadPropertyBoolean(\Cast\Device\Property::EnableRawDuration)) {
            $this->RegisterVariableInteger(\Cast\Device\VariableIdent::PositionRaw, $this->Translate('Position in seconds'), '', ++$i);
        } else {
            $this->UnregisterVariable(\Cast\Device\VariableIdent::PositionRaw);
        }

        $this->RegisterVariableString(\Cast\Device\VariableIdent::Duration, $this->Translate('Duration'), '', ++$i);
        $this->RegisterVariableString(\Cast\Device\VariableIdent::Position, $this->Translate('Position'), '', ++$i);

        $this->RegisterVariableFloat(\Cast\Device\VariableIdent::CurrentTime, $this->Translate('Progress'), '~Progress', ++$i);

        $this->RegisterVariableString(\Cast\Device\VariableIdent::Title, $this->Translate('Title'), '~Song', ++$i);
        $this->RegisterVariableString(\Cast\Device\VariableIdent::Artist, $this->Translate('Artist'), '~Artist', ++$i);
        $this->RegisterVariableString(\Cast\Device\VariableIdent::Collection, $this->Translate('Collection'), '', ++$i);

        if (IPS_GetKernelRunlevel() != KR_READY) {
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->SetStatus(IS_INACTIVE);
            return;
        }

        $ParentID = $this->RegisterParent();

        // Open auf false konfiguriert -> CS nie öffnen bzw. zwangsweise schließen
        $Open = $this->ReadPropertyBoolean(\Cast\Device\Property::Open);
        if (!$Open) {
            if ($ParentID > 0) {
                //$this->StatusIsChanging = false;
                // Jetzt den CS schließen
                IPS_SetProperty($ParentID, \Cast\IO\Property::Open, false);
                @IPS_ApplyChanges($ParentID); // Diese Instanz reagiert auf die Änderung des CS über die MessageSink
            } else {
                $this->IOChangeState(IS_INACTIVE);
            }
            return;
        }

        // Kein Parent
        if ($ParentID == 0) {
            $this->IOChangeState(IS_INACTIVE);
            return;
        }
        // Oder Parent ohne konfigurierten Host
        if ($this->Host == '') {
            $this->IOChangeState(IS_INACTIVE);
            return;
        }
        // Prüfe ob Watchdog konfiguriert & Condition gegeben ist
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::Watchdog)) {
            $Open = $this->CheckCondition();
            if ($Open) {
                if (!$this->CheckPort()) { // Keine Verbindung über CS erzwingen wenn Host offline ist
                    echo $this->Translate('Could not connect to TCP-Server');
                    $Open = false;
                }
            }
        }
        // Jetzt den CS passend konfigurieren bzw. öffnen/schließen
        if (!$Open) {
            IPS_SetProperty($ParentID, \Cast\IO\Property::Open, false);
            @IPS_ApplyChanges($ParentID); // Diese Instanz reagiert auf die Änderung des CS über die MessageSink
            $this->SetWatchdogTimer(true);
            return;
        }

        if (IPS_GetProperty($ParentID, \Cast\IO\Property::Port) != $this->ReadPropertyInteger(\Cast\Device\Property::Port)) {
            IPS_SetProperty($ParentID, \Cast\IO\Property::Port, $this->ReadPropertyInteger(\Cast\Device\Property::Port));
        }

        if (IPS_GetProperty($ParentID, \Cast\IO\Property::Open) != true) {
            IPS_SetProperty($ParentID, \Cast\IO\Property::Open, true);
        }

        @IPS_ApplyChanges($ParentID); // Diese Instanz reagiert auf die Änderung des CS über die MessageSink
        return;
    }

    /**
     * Interne Funktion des SDK.
     *
     * @access public
     */
    public function MessageSink(int $TimeStamp, int $SenderID, int $Message, array $Data): void
    {
        $this->IOMessageSink($TimeStamp, $SenderID, $Message, $Data);
        switch ($Message) {
            case IPS_KERNELSTARTED:
                $this->KernelReady();
                break;
            case IM_CHANGESTATUS:
                if ($SenderID == $this->InstanceID) {
                    if ($this->StatusIsChanging) {
                        $this->SendDebug('MessageSink', 'StatusIsChanging already locked', 0);
                        return;
                    }
                    $this->StatusIsChanging = true;
                    $this->SendDebug('MessageSink', 'StatusIsChanging now locked', 0);
                    switch ($Data[0]) {
                        case IS_ACTIVE:
                            $this->SendDebug('IM_CHANGESTATUS', 'active', 0);
                            $this->LogMessage('Connected to ChromeCast', KL_NOTIFY);
                            $this->SetWatchdogTimer(false);
                            $this->SetTimerInterval(\Cast\Device\Timer::KeepAlive, 60000);
                            $this->RequestState();
                            break;
                        case IS_EBASE + 1: //ERROR connection lost
                        case IS_INACTIVE:
                            $this->SetTimerInterval(\Cast\Device\Timer::KeepAlive, 0);
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
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        $Form['elements'][1]['visible'] = ($this->ParentID ? true : false);
        $Form['elements'][1]['items'][1]['objectID'] = $this->ParentID;
        $Form['elements'][2]['items'][0]['items'][1]['visible'] = $this->ReadPropertyBoolean(\Cast\Device\Property::Watchdog);
        $Form['elements'][2]['items'][1]['items'][0]['visible'] = $this->ReadPropertyBoolean(\Cast\Device\Property::Watchdog);
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::Watchdog)) {
            $Form['elements'][2]['items'][1]['items'][1]['visible'] = ($this->ReadPropertyInteger(\Cast\Device\Property::ConditionType) == 1);
        }
        $this->SendDebug('FORM', json_encode($Form), 0);
        $this->SendDebug('FORM', json_last_error_msg(), 0);
        return json_encode($Form);
    }

    /**
     * Interne Funktion des SDK.
     *
     * @access public
     */
    public function GetConfigurationForParent(): string
    {
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::Watchdog)) {
            $Config[\Cast\IO\Property::Open] = false;
            if ($this->ReadPropertyBoolean(\Cast\Device\Property::Open)) {
                $Config[\Cast\IO\Property::Open] = ($this->GetStatus() == IS_ACTIVE);
            }
        } else {
            $Config[\Cast\IO\Property::Open] = $this->ReadPropertyBoolean(\Cast\Device\Property::Open);
        }
        $Config[\Cast\IO\Property::Port] = $this->ReadPropertyInteger(\Cast\Device\Property::Port);
        $Config[\Cast\IO\Property::UseSSL] = true;
        $Config[\Cast\IO\Property::VerifyHost] = false;
        $Config[\Cast\IO\Property::VerifyPeer] = false;
        return json_encode($Config);
    }

    public function RequestAction(string $Ident, mixed $Value): void
    {
        if ($this->IORequestAction($Ident, $Value)) {
            return;
        }
        switch ($Ident) {
            case \Cast\Device\Timer::KeepAlive:
                $this->SendPing();
                break;
            case \Cast\Device\Timer::Watchdog:
                $this->Watchdog();
                break;
            case \Cast\Device\Property::Watchdog:
                $this->UpdateFormField('Watchdog', 'caption', (bool) $Value ? 'Check every' : 'Check never');
                $this->UpdateFormField('Interval', 'visible', (bool) $Value);
                $this->UpdateFormField('ConditionType', 'visible', (bool) $Value);
                $this->UpdateFormField('ConditionPopup', 'visible', $this->ReadPropertyInteger(\Cast\Device\Property::ConditionType) == 1);
                break;
            case \Cast\Device\Property::ConditionType:
                $this->UpdateFormField('ConditionPopup', 'visible', $Value == 1);
                break;

            case \Cast\Commands::Pong:
                $this->SendPong($Value);
                break;
            case 'RequestState':
                $this->RequestState();
                break;
            case \Cast\Commands::Connect:
                $this->SetMediaImage('');
                $this->Connect($Value);
                if ($this->isIdleScreen) {
                    $this->RequestIdleState();
                } else {
                    $this->RequestMediaState();
                }
                break;
            case \Cast\Device\VariableIdent::Volume:
                $this->SetVolumen($Value / 100);
                break;
            case \Cast\Device\VariableIdent::Muted:
                $this->SetMute((bool) $Value);
                break;
            case \Cast\Device\VariableIdent::AppId:
                $this->LaunchApp($Value);
                break;
            case \Cast\Device\VariableIdent::PlayerState:
                $this->SetPlayerState(\Cast\PlayerState::$IntToAction[(int) $Value]);
                break;
                /*
        case \Cast\Device\VariableIdent::RepeatMode:
                $this->SetRepeat($Value);
                break;
                 */
            case \Cast\Device\Timer::ProgressState:
                if ($this->DurationRAW) {
                    if ($this->PositionRAW < $this->DurationRAW) {
                        $this->PositionRAW++;
                        $Value = (100 / $this->DurationRAW) * $this->PositionRAW;
                        $this->SetValue(\Cast\Device\VariableIdent::CurrentTime, $Value);
                        $this->SetValue(\Cast\Device\VariableIdent::Position, \Cast\Device\TimeConvert::ConvertSeconds($this->PositionRAW));
                        if ($this->ReadPropertyBoolean(\Cast\Device\Property::EnableRawPosition)) {
                            $this->SetValue(\Cast\Device\VariableIdent::PositionRaw, $this->PositionRAW);
                        }
                    }
                } else {
                    $this->SetTimerInterval(\Cast\Device\Timer::ProgressState, 0);
                }
                break;
            case \Cast\Device\VariableIdent::PositionRaw:
                $this->Seek((float) $Value);
                break;
            case \Cast\Device\VariableIdent::CurrentTime:
                if (!$this->MediaSessionId) {
                    trigger_error($this->Translate('No media playback active'), E_USER_NOTICE);
                    return;
                }
                if ($this->DurationRAW) {
                    $Time = ($this->DurationRAW / 100) * $Value;
                    $this->Seek($Time);
                }
                break;
        }
    }

    public function SetVolumen(float $Level): bool
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::SetVolume, ['requestId' => $RequestId, 'volume' => ['level' => $Level]]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::ReceiverNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }

    public function SetMute(bool $Mute): bool
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::SetVolume, ['requestId' => $RequestId, 'volume' => ['muted' => $Mute]]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::ReceiverNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }

    public function LaunchApp(string $AppId): bool
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Launch, ['requestId'=>$RequestId, 'appId'=>$AppId]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::ReceiverNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }

    public function SetPlayerState(string $State): bool
    {
        if (!$this->MediaSessionId) {
            trigger_error($this->Translate('No media playback active'), E_USER_NOTICE);
            return false;
        }
        $RequestId = $this->RequestId++;
        $Payload = [];
        if ($State == \Cast\MediaCommands::Next) {
            $State = \Cast\MediaCommands::QueueUpdate;
            $Payload['jump'] = 1;
        }

        if ($State == \Cast\MediaCommands::Prev) {
            $State = \Cast\MediaCommands::QueueUpdate;
            $Payload['jump'] = -1;
        }
        $Urn = \Cast\Urn::MediaNamespace;
        $Payload = \Cast\Payload::makePayload($State, array_merge($Payload, ['requestId' => $RequestId, 'mediaSessionId' => $this->MediaSessionId]));
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, $Urn, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }

    public function Seek(float $Time): bool
    {
        if (!$this->MediaSessionId) {
            trigger_error($this->Translate('No media playback active'), E_USER_NOTICE);
            return false;
        }
        if (!$this->isSeekable) {
            trigger_error($this->Translate('Media playback not seekable'), E_USER_NOTICE);
            return false;
        }
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\MediaCommands::Seek, ['currentTime' => $Time, 'requestId' => $RequestId, 'mediaSessionId' => $this->MediaSessionId]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MediaNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }

    public function SeekRelative(float $Time): bool
    {
        if (!$this->MediaSessionId) {
            trigger_error($this->Translate('No media playback active'), E_USER_NOTICE);
            return false;
        }
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\MediaCommands::Seek, ['relativeTime' => $Time, 'requestId' => $RequestId, 'mediaSessionId' => $this->MediaSessionId]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MediaNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }
    /*
    public function SetRepeat(string $Mode): bool
    {
        if (!$this->MediaSessionId) {
            trigger_error($this->Translate('No media playback active'), E_USER_NOTICE);
            return false;
        }
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\MediaCommands::QueueUpdate, ['repeatMode'=>$Mode, 'requestId'=>$RequestId, 'mediaSessionId'=>$this->MediaSessionId]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MediaNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }
     */
    public function SetLike(bool $Liked): bool
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::UserAction, ['userAction' => 'LIKE', 'userActionContext' => 'TRACK', 'requestId' => $RequestId, 'mediaSessionId' => $this->MediaSessionId]);
        //$CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MediaNamespace, 0, $Payload]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::ReceiverNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }

    public function GetAppAvailability(): bool
    {
        $RequestId = $this->RequestId++;
        $Request = [
            'requestId' => $RequestId,
            'appId'     => array_merge(array_keys(\Cast\Apps::$Apps), array_values(\Cast\Apps::$Apps))
        ];
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::GetAppAvailability, $Request);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::ReceiverNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }

    public function LoadMediaURL(string $Url, string $contentType, bool $isLive): bool
    {
        if ($this->actualApp != \Cast\Apps::DefaultMediaReceiver || $this->TransportId == '') {
            if (!$this->LaunchApp(\Cast\Apps::DefaultMediaReceiver)) {
                return false;
            }
            IPS_Sleep(1000);
        }
        $RequestId = $this->RequestId++;
        $Payload =
            [
                'media' => [
                    'contentUrl'  => $Url,
                    'streamType'  => $isLive ? 'LIVE' : 'BUFFERED',
                    'contentType' => $contentType
                ],
                'requestId' => $RequestId
            ];

        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Load, $Payload);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MediaNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }
    public function LoadMediaId(string $Id, string $contentType, bool $isLive): bool
    {
        if ($this->actualApp != \Cast\Apps::DefaultMediaReceiver || $this->TransportId == '') {
            if (!$this->LaunchApp(\Cast\Apps::DefaultMediaReceiver)) {
                return false;
            }
            IPS_Sleep(1000);
        }
        $RequestId = $this->RequestId++;
        $Payload =
            [
                'media' => [
                    'contentId'   => $Id,
                    'streamType'  => $isLive ? 'LIVE' : 'BUFFERED',
                    'contentType' => $contentType
                ],
                'requestId' => $RequestId
            ];

        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Load, $Payload);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MediaNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;

    }
    public function LoadMediaQueue(array $Items, bool $Repeat, int $StartIndex, bool $Autoplay): bool
    {
        if ($this->actualApp != \Cast\Apps::DefaultMediaReceiver || $this->TransportId == '') {
            if (!$this->LaunchApp(\Cast\Apps::DefaultMediaReceiver)) {
                return false;
            }
            IPS_Sleep(1000);
        }
        $PayloadItems = [];
        foreach ($Items as $Item) {
            $PayloadItems[] = [
                'media'=> array_merge(\Cast\Queue::$MediaItemKeys, $Item)
            ];
        }
        $RequestId = $this->RequestId++;
        $Payload = [
            'queueData'=> [
                'startIndex'      => $StartIndex,
                'repeatMode'      => $Repeat ? \Cast\Queue::RepeatAll : \Cast\Queue::RepeatOff,
                'autoplay'        => $Autoplay ? 1 : 0,
                'items'           => $PayloadItems
            ],
            'requestId' => $RequestId
        ];
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Load, $Payload);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MediaNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }

    }
    public function CloseApp(): bool
    {
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Close);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::ConnectionNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg);
        $this->ClearMediaVariables();
        return $Payload;
    }
    public function RequestState(): bool
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $this->Connect();
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::GetStatus, ['requestId' => $RequestId]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::ReceiverNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }

    public function RequestMediaState(): bool
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $RequestId = $this->RequestId++;
        $Urn = \Cast\Urn::MediaNamespace;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::GetStatus, ['requestId' => $RequestId]);

        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, $Urn, 0, $Payload]);

        /*
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::GetStatus, ['requestId'=>$RequestId]);
        $Urn = 'urn:x-cast:com.google.cast.remotecontrol';
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'system-0', $Urn, 0, $Payload]);
         */
        $Payload = $this->Send($CMsg, $RequestId);
        /*
            $this->SendDebug(__FUNCTION__,'Clear TransportId & MediaSessionId',0);
            $this->ClearMediaVariables();
         */
        return $Payload ? true : false;
    }

    public function RequestIdleState(): void
    {
        $this->SendDebug(__FUNCTION__, '', 0);
        $Urn = \Cast\Urn::SSE;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::GetStatus);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, $Urn, 0, $Payload]);
        $Payload = $this->Send($CMsg);
    }

    public function SendCommand(string $URN, string $Command, array $Payload = []): bool
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload($Command, array_merge($Payload, ['requestId' => $RequestId]));
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', 'urn:x-cast:com.google.cast.' . $URN, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }

    public function SendCommandToApp(string $URN, string $Command, array $Payload = []): bool
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload($Command, array_merge($Payload, ['requestId' => $RequestId]));
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, 'urn:x-cast:com.google.' . $URN, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        return $Payload ? true : false;
    }

    public function SendPing(): bool
    {
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Ping);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::HeartbeatNamespace, 0, $Payload]);
        $Result = $this->Send($CMsg);
        return $Result;
    }

    public function ReceiveData($JSONString): string
    {
        $Data = $this->Buffer . hex2bin((json_decode($JSONString))->Buffer);
        $this->DecodePacket($Data);
        return '';
    }

    protected function RegisterParent(): int
    {
        $ParentID = $this->IORegisterParent();

        if ($ParentID > 0) {
            $this->Host = IPS_GetProperty($ParentID, \Cast\IO\Property::Host);
        } else {
            $this->Host = '';
        }
        $this->SetSummary($this->Host);
        return $ParentID;
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
    protected function IOChangeState(int $State): void
    {
        if ($this->StatusIsChanging) {
            $this->SendDebug('IOChangeState', 'StatusIsChanging already locked', 0);
            return;
        }
        $this->StatusIsChanging = true;
        $this->SendDebug('IOChangeState', 'StatusIsChanging now locked', 0);
        if (!$this->ReadPropertyBoolean(\Cast\Device\Property::Open)) {
            if ($this->GetStatus() != IS_INACTIVE) {
                $this->SetStatus(IS_INACTIVE);
            }
            $this->SendDebug('IOChangeState', 'StatusIsChanging now unlocked', 0);
            $this->StatusIsChanging = false;
            return;
        }
        switch ($State) {
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
                    if ($this->ReadPropertyBoolean(\Cast\Device\Property::Watchdog)) {
                        IPS_SetProperty($this->ParentID, \Cast\IO\Property::Open, false);
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
     * IPS-Instanz-Funktion 'CCAST_Watchdog'.
     * Sendet einen TCP-Ping an das Gerät und prüft die Erreichbarkeit.
     * Wird erkannt, dass das Gerät erreichbar ist, wird versucht eine TCP-Verbindung aufzubauen.
     *
     * @access public
     */
    private function Watchdog(): void
    {
        $this->SendDebug(__FUNCTION__, 'run', 0);
        if (!$this->ReadPropertyBoolean(\Cast\Device\Property::Open)) {
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
            IPS_SetProperty($this->ParentID, \Cast\Device\Property::Open, true);
            @IPS_ApplyChanges($this->ParentID);
        }
    }

    private function CheckCondition(): bool
    {
        switch ($this->ReadPropertyInteger(\Cast\Device\Property::ConditionType)) {
            case 0:
                $Result = @Sys_Ping($this->Host, 500);
                $this->SendDebug('Pinging', $Result, 0);
                return $Result;
            case 1:
                $Result = IPS_IsConditionPassing($this->ReadPropertyString(\Cast\Device\Property::WatchdogCondition));
                $this->SendDebug('CheckCondition', $Result, 0);
                return $Result;
        }
        return false;
    }

    private function CheckPort(): bool
    {
        $context = stream_context_create();
        stream_context_set_option($context, 'ssl', 'verify_host', false);
        stream_context_set_option($context, 'ssl', 'verify_peer', false);
        $Socket = @stream_socket_client('tcp://' . $this->Host . ':' . $this->ReadPropertyInteger(\Cast\Device\Property::Port), $errno, $errstr, 2, STREAM_CLIENT_CONNECT, $context);
        if (!$Socket) {
            $this->SendDebug('CheckPort', false, 0);
            return false;
        }
        stream_socket_shutdown($Socket, STREAM_SHUT_RDWR);
        return true;
    }

    /**
     * Aktiviert / Deaktiviert den WatchdogTimer.
     *
     * @param bool $Active True für aktiv, false für inaktiv.
     */
    private function SetWatchdogTimer(bool $Active): void
    {
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::Open)) {
            if ($this->ReadPropertyBoolean(\Cast\Device\Property::Watchdog)) {
                if ($Active) {
                    $Interval = $this->ReadPropertyInteger(\Cast\Device\Property::Interval);
                    $Interval = ($Interval < 5) ? 0 : $Interval;
                    $this->SetTimerInterval(\Cast\Device\Timer::Watchdog, $Interval * 1000);
                    $this->SendDebug(\Cast\Device\Timer::Watchdog, 'active', 0);
                    return;
                }
            }
        }
        $this->SetTimerInterval(\Cast\Device\Timer::Watchdog, 0);
        $this->SendDebug(\Cast\Device\Timer::Watchdog, 'inactive', 0);
    }

    private function SetIcon(string $iconUrl): void
    {
        $MediaId = @IPS_GetObjectIDByIdent('iconUrl', $this->InstanceID);
        if ($MediaId < 10000) {
            $MediaId = IPS_CreateMedia(1);
            IPS_SetParent($MediaId, $this->InstanceID);
            IPS_SetIdent($MediaId, 'iconUrl');
            IPS_SetName($MediaId, 'App Icon');
            IPS_SetPosition($MediaId, 1);
            IPS_SetMediaCached($MediaId, true);
            $filename = 'media' . DIRECTORY_SEPARATOR . 'CCast_iconUrl_' . $this->InstanceID . '.png';
            IPS_SetMediaFile($MediaId, $filename, false);
            $this->SendDebug('Create Media', $filename, 0);
        }
        $Size = $this->ReadPropertyInteger(\Cast\Device\Property::AppIconSizeWidth);
        if ($iconUrl === '') {
            $MediaRAW = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'imgs' . DIRECTORY_SEPARATOR . 'no_app_image.png');
            $MediaRAW = $this->ResizeImage($MediaRAW, $Size, 'png');
        } else {
            $Found = strrpos($iconUrl, '=');
            if ($Found !== false) {
                $iconUrl = substr($iconUrl, 0, $Found);
            }
            $iconUrl .= '=w' . $Size;
            $MediaRAW = @Sys_GetURLContentEx($iconUrl, ['Timeout'=>5000, 'VerifyPeer'=>false, 'VerifyHost'=>false]);
        }
        $this->SendDebug('Refresh IconUrl', $iconUrl, 0);
        IPS_SetMediaContent($MediaId, base64_encode($MediaRAW));
    }

    private function SetMediaImage(string $MediaUrl): void
    {
        $MediaId = @IPS_GetObjectIDByIdent('mediaUrl', $this->InstanceID);
        $this->SendDebug('Refresh MediaUrl', $MediaId, 0);
        if ($MediaId < 10000) {
            $MediaId = IPS_CreateMedia(1);
            IPS_SetParent($MediaId, $this->InstanceID);
            IPS_SetIdent($MediaId, 'mediaUrl');
            IPS_SetName($MediaId, 'Media');
            IPS_SetPosition($MediaId, 13);
            IPS_SetMediaCached($MediaId, true);
            $filename = 'media' . DIRECTORY_SEPARATOR . 'CCast_mediaUrl_' . $this->InstanceID . '.jpg';
            IPS_SetMediaFile($MediaId, $filename, false);
            $this->SendDebug('Create Media', $filename, 0);
        }
        $Size = $this->ReadPropertyInteger(\Cast\Device\Property::MediaSizeWidth);
        if ($MediaUrl === '') {
            $iconUrl = $this->AppIconUrl;
            if ($iconUrl == '') {
                $MediaRAW = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'imgs' . DIRECTORY_SEPARATOR . 'nocover.png');
                $MediaRAW = $this->ResizeImage($MediaRAW, $Size, 'png');
            } else {
                $Found = strrpos($iconUrl, '=');
                if ($Found !== false) {
                    $iconUrl = substr($iconUrl, 0, $Found);
                }
                $iconUrl .= '=w' . $Size;
                $MediaRAW = @Sys_GetURLContentEx($MediaUrl, ['Timeout'=>5000, 'VerifyPeer'=>false, 'VerifyHost'=>false]);
            }
        } else {
            if (strpos($MediaUrl, 'googleusercontent')) {
                $Found = strrpos($MediaUrl, '=');
                if ($Found !== false) {
                    $MediaUrl = substr($MediaUrl, 0, $Found);
                    $MediaUrl .= '=w' . $Size;
                }
                $MediaRAW = @file_get_contents($MediaUrl);
            } else {
                $MediaRAW = @Sys_GetURLContentEx($MediaUrl, ['Timeout'=>5000, 'VerifyPeer'=>false, 'VerifyHost'=>false]);
                $MediaRAW = $this->ResizeImage($MediaRAW, $Size, substr($MediaUrl, -3));
            }
        }

        $this->SendDebug('Refresh mediaUrl', $MediaUrl, 0);
        if ($MediaRAW) {
            IPS_SetMediaContent($MediaId, base64_encode($MediaRAW));
        } else {
            //todo Fehler ausgeben.
        }

    }
    private function ResizeImage(string $ImageRaw, int $SizeWidth, string $Format = 'jpg'): string
    {
        $image = @imagecreatefromstring($ImageRaw);
        if ($image !== false) {
            $width = imagesx($image);
            $height = imagesy($image);
            $factor = 1;
            if ($SizeWidth > 0) {
                if ($width > $SizeWidth) {
                    $factor = $width / $SizeWidth;
                }
            }
            if ($factor != 1) {
                $image = imagescale($image, (int) ($width / $factor), (int) ($height / $factor));
            }
            if ($Format == 'png') {
                imagealphablending($image, false);
                imagesavealpha($image, true);
                ob_start();
                @imagepng($image);
                $ThumbRAW = ob_get_contents(); // read from buffer
                ob_end_clean(); // delete buffer
            } else {
                ob_start();
                @imagejpeg($image);
                $ThumbRAW = ob_get_contents(); // read from buffer
                ob_end_clean(); // delete buffer

            }
        }

        return $ThumbRAW;
    }
    private function Send(\Cast\CastMessage $CMsg, int $RequestId = 0): bool|array
    {
        if ($CMsg->getUrn() != \Cast\Urn::HeartbeatNamespace) {
            $this->SendDebug('Send (' . $RequestId . ')', $CMsg->__debug(), 0);
        }
        if ($RequestId) {
            //$this->SendDebug('Send (' . $RequestId . ')', $CMsg->__debug(), 0);
            $this->SendQueuePush($RequestId);
        }
        $SendResult = $this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', 'Buffer' => bin2hex($CMsg->getMessage())]));
        if ($SendResult === false) {
            $this->SetStatus(IS_EBASE + 1);
            return false;
        }
        if ($RequestId == 0) {
            return true;
        }
        $Result = $this->WaitForResponse($RequestId);
        $this->SendDebug('Result (' . $RequestId . ')', $Result, 0);
        if ($Result) {
            $this->DecodeEvent($CMsg, $Result);
        }
        return $Result;
    }

    private function SendPong(string $ReceiverId): void
    {
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Pong);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $ReceiverId, \Cast\Urn::HeartbeatNamespace, 0, $Payload]);
        $this->Send($CMsg);
    }

    private function Connect(string $ReceiverId = 'receiver-0'): void
    {
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Connect);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $ReceiverId, \Cast\Urn::ConnectionNamespace, 0, $Payload]);
        $this->Send($CMsg);
    }

    /**
     * DecodeEvent
     *
     * @todo decodieren weiter ausbauen
     * @param  mixed $CMsg
     * @param  array $Payload
     * @return void
     */
    private function DecodeEvent(\Cast\CastMessage $CMsg, array $Payload): void
    {
        switch ($CMsg->getUrn()) {
            case \Cast\Urn::HeartbeatNamespace:
                switch ($Payload['type']) {
                    case \Cast\Commands::Ping:
                        IPS_RunscriptText('IPS_Sleep(5);IPS_RequestAction(' . $this->InstanceID . ',\'' . \Cast\Commands::Pong . '\',\'' . $CMsg->getReceiverId() . '\');');
                        break;
                }
                break;
            case \Cast\Urn::ConnectionNamespace:
                switch ($Payload['type']) {
                    case \Cast\Commands::Close:
                        if ($CMsg->getSourceId() == $this->TransportId) {
                            $this->SendDebug(__FUNCTION__, 'Clear TransportId & MediaSessionId', 0);
                            $this->ClearMediaVariables();
                        }
                        break;
                }
                break;
            case \Cast\Urn::SSE:
                if (array_key_exists('backendData', $Payload)) {
                    $BackendData = json_decode($Payload['backendData'], true);
                    $this->SetMediaImage($BackendData[0]);
                    $this->SetValue(\Cast\Device\VariableIdent::Collection, $BackendData[12]);
                }
                break;
            case \Cast\Urn::MediaNamespace:
                switch ($Payload['type']) {
                    case \Cast\Commands::MediaStatus:
                        if (!count($Payload['status'])) {
                            break;
                        }
                        $Status = array_shift($Payload['status']);
                        if (isset($Status['mediaSessionId'])) {
                            if ($this->MediaSessionId != $Status['mediaSessionId']) {
                                $this->MediaSessionId = $Status['mediaSessionId'];
                                if (!array_key_exists('media', $Status)) {
                                    IPS_RunscriptText('IPS_Sleep(1);CCAST_RequestMediaState(' . $this->InstanceID . ');');
                                }
                            }
                        } else {
                            $this->MediaSessionId = 0;
                        }
                        //todo doch auch customData:playerState ?! 5 = stop, 3 = buffer, 1 = play, 2= pause
                        if (isset($Status['playerState'])) {
                            if ($Status['playerState'] != \Cast\PlayerState::Buffering) {
                                if (isset($Status['supportedMediaCommands'])) {
                                    if (isset($Status['queueData'])) {
                                        $Status['supportedMediaCommands'] += 64;
                                    }
                                    $this->updateControlsByMediaCommand($Status['supportedMediaCommands']);
                                }
                                $this->SetValue(\Cast\Device\VariableIdent::PlayerState, \Cast\PlayerState::$StateToInt[$Status['playerState']]);
                            }
                            if ($Status['playerState'] == \Cast\PlayerState::Play) {
                                $this->SetTimerInterval(\Cast\Device\Timer::ProgressState, 1000);
                            } else {
                                $this->SetTimerInterval(\Cast\Device\Timer::ProgressState, 0);
                            }

                        }
                        if (isset($Status[\Cast\Device\VariableIdent::CurrentTime])) {
                            if ($this->DurationRAW) {
                                $Value = (100 / $this->DurationRAW) * $Status[\Cast\Device\VariableIdent::CurrentTime];
                                $this->SetValue(\Cast\Device\VariableIdent::CurrentTime, $Value);
                            } else {
                                $this->SetValue(\Cast\Device\VariableIdent::CurrentTime, 0);
                            }
                            $this->PositionRAW = $Status[\Cast\Device\VariableIdent::CurrentTime];
                            $this->SetValue(\Cast\Device\VariableIdent::Position, \Cast\Device\TimeConvert::ConvertSeconds($Status[\Cast\Device\VariableIdent::CurrentTime]));
                            if ($this->ReadPropertyBoolean(\Cast\Device\Property::EnableRawPosition)) {
                                $this->SetValue(\Cast\Device\VariableIdent::PositionRaw, $Status[\Cast\Device\VariableIdent::CurrentTime]);
                            }
                        } else {
                            $this->PositionRAW = 0;
                            $this->SetValue(\Cast\Device\VariableIdent::Position, '');
                            if ($this->ReadPropertyBoolean(\Cast\Device\Property::EnableRawPosition)) {
                                $this->SetValue(\Cast\Device\VariableIdent::PositionRaw, 0);
                            }
                        }
                        if (array_key_exists('media', $Status)) {
                            $Media = $Status['media'];
                            if (isset($Media['metadata']['metadataType'])) {
                                $MetaData = $Media['metadata'];
                                switch ($MetaData['metadataType']) {
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

                            if (isset($Media['metadata'][\Cast\Device\VariableIdent::Title])) {
                                $this->SetValue(\Cast\Device\VariableIdent::Title, $Media['metadata'][\Cast\Device\VariableIdent::Title]);
                            } else {
                                $this->SetValue(\Cast\Device\VariableIdent::Title, '');
                            }
                            if (isset($Media['metadata'][\Cast\Device\VariableIdent::Artist])) {
                                $this->SetValue(\Cast\Device\VariableIdent::Artist, $Media['metadata'][\Cast\Device\VariableIdent::Artist]);
                            } else {
                                $this->SetValue(\Cast\Device\VariableIdent::Artist, '');
                            }
                            if (isset($Media['metadata']['albumName'])) {
                                $this->SetValue(\Cast\Device\VariableIdent::Collection, $Media['metadata']['albumName']);
                            } else {
                                if (isset($Media['customData']['mediaItem'][\Cast\Device\VariableIdent::Title])) {
                                    $this->SetValue(\Cast\Device\VariableIdent::Collection, $Media['customData']['mediaItem'][\Cast\Device\VariableIdent::Title]);
                                } else {
                                    $this->SetValue(\Cast\Device\VariableIdent::Collection, '');
                                }
                            }
                            if (isset($Media['metadata']['images'])) {
                                $Key = 0;
                                if (isset($Media['metadata']['images'][0]['width'])) {
                                    $Width = array_column($Media['metadata']['images'], 'width');
                                    array_multisort($Width, SORT_ASC, SORT_NUMERIC, $Media['metadata']['images']);
                                    $Size = $this->ReadPropertyInteger(\Cast\Device\Property::MediaSizeWidth);
                                    foreach ($Width as $Key => $Value) {
                                        if ($Value >= $Size) {
                                            break;
                                        }
                                    }
                                }
                                if ($this->MediaUrl != $Media['metadata']['images'][$Key]['url']) {
                                    $this->MediaUrl = $Media['metadata']['images'][$Key]['url'];
                                    $this->SetMediaImage($Media['metadata']['images'][$Key]['url']);
                                }
                            } else {
                                if (isset($Media['contentType'])) {
                                    if (str_starts_with($Media['contentType'], 'image/')) {
                                        if ($this->MediaUrl != $Media['contentUrl']) {
                                            $this->MediaUrl = $Media['contentUrl'];
                                            $this->SetMediaImage($Media['contentUrl']);
                                        }
                                    }
                                }
                            }
                            //customData:artists
                            //customData:title

                            if (isset($Media[\Cast\Device\VariableIdent::Duration])) {
                                $this->DurationRAW = $Media[\Cast\Device\VariableIdent::Duration];
                                $this->SetValue(\Cast\Device\VariableIdent::Duration, \Cast\Device\TimeConvert::ConvertSeconds($Media[\Cast\Device\VariableIdent::Duration]));
                                if ($this->ReadPropertyBoolean(\Cast\Device\Property::EnableRawDuration)) {
                                    $this->SetValue(\Cast\Device\VariableIdent::DurationRaw, $Media[\Cast\Device\VariableIdent::Duration]);
                                }
                            } else {
                                $this->DurationRAW = 0;
                                $this->SetValue(\Cast\Device\VariableIdent::Duration, '');
                                if ($this->ReadPropertyBoolean(\Cast\Device\Property::EnableRawDuration)) {
                                    $this->SetValue(\Cast\Device\VariableIdent::DurationRaw, 0);
                                }
                            }
                        }

                        //queueData
                        /*
                        if (isset($Status['queueData']['repeatMode'])) {
                            $this->SetValue(\Cast\Device\VariableIdent::RepeatMode, $Status['queueData']['repeatMode']);
                        } elseif (isset($Status['repeatMode'])) {
                            $this->SetValue(\Cast\Device\VariableIdent::RepeatMode, $Status['repeatMode']);
                        } else {
                            $this->SetValue(\Cast\Device\VariableIdent::RepeatMode,'');
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
            case \Cast\Urn::MultiZoneNamespace:

                break;
            case \Cast\Urn::ReceiverNamespace:
                $Payload['type'] = $Payload['type'] ?? $Payload['responseType'];
                switch ($Payload['type']) {
                    case \Cast\Commands::GetAppAvailability:
                        // @todo auswerten
                        break;
                    case \Cast\Commands::LaunchStatus:
                        break;
                    case \Cast\Commands::LaunchError:
                        break;
                    case \Cast\Commands::ReceiverStatus:
                        $Status = $Payload['status'];

                        if (isset($Status['volume']['level'])) {
                            $this->SetValue(\Cast\Device\VariableIdent::Volume, (int) ($Status['volume']['level'] * 100));
                        }
                        if (isset($Status['volume']['muted'])) {
                            $this->SetValue(\Cast\Device\VariableIdent::Muted, $Status['volume']['muted']);
                        }

                        if (array_key_exists('applications', $Status)) {
                            $ActualApp = array_shift($Status['applications']);
                            if (array_key_exists('iconUrl', $ActualApp)) {
                                if ($this->AppIconUrl != $ActualApp['iconUrl']) {
                                    $this->AppIconUrl = $ActualApp['iconUrl'];
                                    $this->SetIcon($ActualApp['iconUrl']);
                                }
                            }
                            $found = array_search($ActualApp['displayName'], \Cast\Apps::$Apps);
                            if ($found !== false) {
                                $ActualApp[\Cast\Device\VariableIdent::AppId] = $found;
                            }
                            $this->SetValue(\Cast\Device\VariableIdent::AppId, $ActualApp[\Cast\Device\VariableIdent::AppId]);
                            $this->isIdleScreen = $ActualApp['isIdleScreen'];
                            if ($this->actualApp != $ActualApp[\Cast\Device\VariableIdent::AppId]) {
                                $this->actualApp = $ActualApp[\Cast\Device\VariableIdent::AppId];

                            }
                            $NewTransportId = ($ActualApp['transportId'] ?? $ActualApp['sessionId']);
                            if ($this->TransportId != $NewTransportId) {
                                $this->TransportId = $NewTransportId;
                                IPS_RunScriptText('IPS_Sleep(5);IPS_RequestAction(' . $this->InstanceID . ',"' . \Cast\Commands::Connect . '","' . $this->TransportId . '");');
                            }
                        }
                        break;
                }
                break;
            case \Cast\Urn::YouTube:
                $this->screenId = $Payload['data']['screenId'] ?? '';
                $this->deviceId = $Payload['data']['deviceId'] ?? '';
                break;
            default:
                break;
        }
    }

    private function ClearMediaVariables(): void
    {
        $this->SetTimerInterval(\Cast\Device\Timer::ProgressState, 0);
        $this->supportedMediaCommands = 0;
        $this->PositionRAW = 0;
        $this->isSeekable = false;
        $this->DurationRAW = 0;
        $this->MediaSessionId = 0;
        $this->TransportId = '';
        $this->SetValue(\Cast\Device\VariableIdent::Title, '');
        $this->SetValue(\Cast\Device\VariableIdent::Artist, '');
        $this->SetValue(\Cast\Device\VariableIdent::Collection, '');
        $this->SetValue(\Cast\Device\VariableIdent::Duration, '');
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::EnableRawDuration)) {
            $this->SetValue(\Cast\Device\VariableIdent::DurationRaw, 0);
        }
        $this->SetValue(\Cast\Device\VariableIdent::CurrentTime, 0);
        $this->SetValue(\Cast\Device\VariableIdent::Position, '');
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::EnableRawPosition)) {
            $this->SetValue(\Cast\Device\VariableIdent::PositionRaw, 0);
        }
        //$this->SetIcon('');
        //$this->SetMediaImage('');
        $this->updateControlsByMediaCommand(0);
        $this->SetValue(\Cast\Device\VariableIdent::PlayerState, 1);
    }

    private function updateControlsByMediaCommand(int $MediaCommand): void
    {
        if ($this->supportedMediaCommands == $MediaCommand) {
            return;
        }
        $this->supportedMediaCommands = $MediaCommand;
        $Commands = \Cast\MediaCommands::ListAvailableCommands($MediaCommand);
        $Profile = '~PlaybackPreviousNextNoStop';
        if (!in_array(\Cast\MediaCommands::Pause, $Commands)) {
            if (in_array(\Cast\MediaCommands::Next, $Commands)) {
                $Profile = '~PlaybackPreviousNextNoStop';
            } else {
                $Profile = '~PlaybackNoStop';
            }
        } else {
            if (in_array(\Cast\MediaCommands::Next, $Commands)) {
                $Profile = '~PlaybackPreviousNext';
            } else {
                $Profile = '~Playback';
            }
        }
        $this->RegisterVariableInteger(\Cast\Device\VariableIdent::PlayerState, 'playerState', $Profile);

        /*
        if (in_array(\Cast\MediaCommands::RepeatAll, $Commands) || in_array(\Cast\MediaCommands::RepeatOne, $Commands)) {
            $this->EnableAction(\Cast\Device\VariableIdent::RepeatMode);
        } else {
            $this->DisableAction(\Cast\Device\VariableIdent::RepeatMode);
        }
         */

        /*
        if (in_array(\Cast\MediaCommands::Shuffle, $Commands)) {
            $this->EnableAction(\Cast\Device\VariableIdent::Shuffle);
        } else {
            $this->DisableAction(\Cast\Device\VariableIdent::Shuffle);
        }
         */
        if (in_array(\Cast\MediaCommands::Seek, $Commands)) {
            $this->isSeekable = true;
            if ($this->ReadPropertyBoolean(\Cast\Device\Property::EnableRawPosition)) {
                $this->EnableAction(\Cast\Device\VariableIdent::PositionRaw);
            }
            $this->EnableAction(\Cast\Device\VariableIdent::CurrentTime);
        } else {
            $this->isSeekable = false;
            if ($this->ReadPropertyBoolean(\Cast\Device\Property::EnableRawPosition)) {
                $this->DisableAction(\Cast\Device\VariableIdent::PositionRaw);
            }
            $this->DisableAction(\Cast\Device\VariableIdent::CurrentTime);
        }
        $this->SendDebug('COMMANDS', $Commands, 0);
    }

    private function DecodePacket(string $Data): void
    {
        $len = unpack('N', substr($Data, 0, 4))[1];
        if (strlen($Data) < $len + 4) {
            $this->Buffer = $Data;
            return;
        }
        $Part = substr($Data, 4, $len);
        $Tail = substr($Data, 4 + $len, $len);
        $CMsg = new \Cast\CastMessage($Part);

        $Payload = $CMsg->getPayload();
        if ($Payload) {
            $isEvent = true;
            if (array_key_exists('requestId', $Payload)) {
                if ($Payload['requestId'] != 0) {
                    $isEvent = false;
                    $this->SendQueueUpdate($Payload);
                }
            }
            if ($isEvent) {
                if ($CMsg->getUrn() != \Cast\Urn::HeartbeatNamespace) {
                    $this->SendDebug('EVENT', $CMsg->__debug(), 0);
                }
                $this->DecodeEvent($CMsg, $Payload);
            }
        }
        if (strlen($Tail) > 4) {
            $this->DecodePacket($Tail);
        }
        $this->Buffer = $Tail;
    }

    /**
     * Wartet auf eine Antwort einer Anfrage
     *
     * @param int $RequestId
     * @return array|false Enthält ein Array mit den Daten der Antwort. False bei einem Timeout
     */
    private function WaitForResponse(int $RequestId): false|array
    {
        $millis = microtime(true) + 10;
        do {
            $Buffer = $this->ReplyCMsgPayload;
            if (!array_key_exists($RequestId, $Buffer)) {
                return false;
            }
            if (count($Buffer[$RequestId])) {
                $this->SendQueueRemove($RequestId);
                return $Buffer[$RequestId];
            }
            IPS_Sleep(5);
        } while ($millis > microtime(true));
        $this->SendQueueRemove($RequestId);
        return false;
    }

    //################# SENDQUEUE

    /**
     * Fügt eine Anfrage in die SendQueue ein.
     *
     * @param int $RequestId
     */
    private function SendQueuePush(int $RequestId): void
    {
        if (!$this->lock('ReplyCMsg')) {
            throw new Exception($this->Translate('ReplyCMsgPayload is locked'), E_USER_NOTICE);
        }
        $data = $this->ReplyCMsgPayload;
        $data[$RequestId] = [];
        $this->ReplyCMsgPayload = $data;
        $this->unlock('ReplyCMsg');
    }

    /**
     * Fügt eine Antwort in die SendQueue ein.
     *
     * @param array Payload
     *
     * @return bool True wenn Anfrage zur Antwort gefunden wurde, sonst false.
     */
    private function SendQueueUpdate(array $Payload): bool
    {
        if (!$this->lock('ReplyCMsgPayload')) {
            throw new Exception($this->Translate('ReplyCMsgPayload is locked'), E_USER_NOTICE);
        }
        $data = $this->ReplyCMsgPayload;
        if (array_key_exists($Payload['requestId'], $data)) {
            $data[$Payload['requestId']] = $Payload;
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
     * @param int $RequestId Der Index des zu löschenden Eintrags.
     */
    private function SendQueueRemove(int $RequestId): void
    {
        if (!$this->lock('ReplyCMsgPayload')) {
            throw new Exception($this->Translate('ReplyCMsgPayload is locked'), E_USER_NOTICE);
        }
        $data = $this->ReplyCMsgPayload;
        unset($data[$RequestId]);
        $this->ReplyCMsgPayload = $data;
        $this->unlock('ReplyCMsgPayload');
    }
}