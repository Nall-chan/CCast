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
 * @property string $Buffer EmpfangsBuffer
 * @property int $RequestId
 * @property array $ReplyCMsgPayload
 * @property string $TransportId //actual App
 * @property string $ActualUrn //actual Urn
 * @property string $MediaSessionId //actual MediaSession
 * @property float $PositionRAW
 * @property bool $isSeekable
 * @property float $DurationRAW
 * @property int $ParentID
 * @property bool $StatusIsChanging
 * @property string $Host
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
class ChromeCast extends IPSModule
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
    public function Create()
    {
        //Never delete this line!
        parent::Create();

        $this->RequireParent(\Cast\IO\GUID);
        $this->ParentID = 0;
        $this->Host = '';
        $this->Buffer = '';
        $this->RequestId = 1;
        $this->ReplyCMsgPayload = [];
        $this->TransportId = '';
        $this->StatusIsChanging = false;

        $this->RegisterPropertyBoolean(\Cast\Device\Property::Open, false);
        $this->RegisterPropertyInteger(\Cast\Device\Property::Port, 8009);
        $this->RegisterPropertyBoolean(\Cast\Device\Property::Watchdog, false);
        $this->RegisterPropertyInteger(\Cast\Device\Property::Interval, 5);
        $this->RegisterPropertyInteger(\Cast\Device\Property::ConditionType, 0);
        $this->RegisterPropertyString(\Cast\Device\Property::WatchdogCondition, '');
        $this->RegisterPropertyInteger(\Cast\Device\Property::MediaSizeWidth, 512);
        $this->RegisterPropertyInteger(\Cast\Device\Property::AppIconSizeWidth, 90);
        $this->RegisterPropertyBoolean('enableRawDuration', true);
        $this->RegisterPropertyBoolean('enableRawPosition', true);
        $this->RegisterTimer(\Cast\Device\Timer::ProgressState, 0, 'IPS_RequestAction(' . $this->InstanceID . ',"' . \Cast\Device\Timer::ProgressState . '",true);');
        $this->RegisterTimer(\Cast\Device\Timer::Watchdog, 0, 'IPS_RequestAction(' . $this->InstanceID . ',"' . \Cast\Device\Timer::Watchdog . '",true);');
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!

        $this->RegisterMessage($this->InstanceID, FM_CONNECT);
        $this->RegisterMessage($this->InstanceID, FM_DISCONNECT);
        $this->RegisterMessage($this->InstanceID, IM_CHANGESTATUS);

        $this->ParentID = 0;

        $this->Buffer = '';
        $this->RequestId = 1;
        $this->ReplyCMsgPayload = [];
        $this->TransportId = '';
        $this->SetWatchdogTimer(false);

        parent::ApplyChanges();
        $i = 0;
        $this->RegisterProfileStringEx('CCast.AppId.' . (string) $this->InstanceID, '', '', '', \Cast\Apps::getAllAppsAsProfileAssoziation());
        $this->RegisterVariableString('appId', 'appId', 'CCast.AppId.' . (string) $this->InstanceID, ++$i);
        $this->EnableAction('appId');

        $this->RegisterVariableBoolean('isIdleScreen', 'isIdleScreen', '', ++$i);
        $this->RegisterVariableBoolean('senderConnected', 'senderConnected', '', ++$i);
        $this->RegisterVariableString('controlType', 'controlType', '', ++$i);

        $this->RegisterVariableInteger(\Cast\Device\VariableIdents::Volume, 'level', '~Volume', ++$i);
        $this->EnableAction(\Cast\Device\VariableIdents::Volume);
        $this->RegisterVariableBoolean(\Cast\Device\VariableIdents::Muted, 'muted', '~Mute', ++$i);
        $this->EnableAction(\Cast\Device\VariableIdents::Muted);

        $this->RegisterVariableInteger(\Cast\Device\VariableIdents::PlayerState, 'playerState', '~PlaybackPreviousNext', ++$i);
        $this->RegisterVariableString(\Cast\Device\VariableIdents::RepeatMode, 'repeatMode', '', ++$i);
        $this->RegisterVariableString(\Cast\Device\VariableIdents::Shuffle, 'shuffleMode', '', ++$i);

        if ($this->ReadPropertyBoolean('enableRawDuration')) {
            $this->RegisterVariableInteger('durationRaw', $this->Translate('Duration in seconds'), '', ++$i);
        } else {
            $this->UnregisterVariable('durationRaw');
        }

        if ($this->ReadPropertyBoolean('enableRawPosition')) {
            $this->RegisterVariableInteger('positionRaw', $this->Translate('Position in seconds'), '', ++$i);
        } else {
            $this->UnregisterVariable('positionRaw');
        }

        $this->RegisterVariableString('duration', $this->Translate('Duration'), '', ++$i);
        $this->RegisterVariableString('position', $this->Translate('Position'), '', ++$i);

        $this->RegisterVariableFloat('currentTime', 'currentTime', '~Progress', ++$i);

        $this->RegisterVariableString('title', 'title', '~Song', ++$i);
        $this->RegisterVariableString('artist', 'artist', '~Artist', ++$i);
        $this->RegisterVariableString('collection', 'collection', '', ++$i);

        if (IPS_GetKernelRunlevel() != KR_READY) {
            $this->RegisterMessage(0, IPS_KERNELSTARTED);
            $this->SetStatus(IS_INACTIVE);
            return;
        }

        $ParentID = $this->RegisterParent();

        // Nie öffnen
        if (!$this->ReadPropertyBoolean(\cast\Device\Property::Open)) {
            $this->StatusIsChanging = false;
            if ($ParentID > 0) {
                IPS_SetProperty($ParentID, \cast\IO\Property::Open, false);
                @IPS_ApplyChanges($ParentID);
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

        // Keine Verbindung erzwingen wenn Host offline ist
        $Open = $this->CheckCondition();
        if ($Open) {
            if (!$this->CheckPort()) {
                echo $this->Translate('Could not connect to TCP-Server.');
                $Open = false;
            }
        }
        if (!$Open) {
            IPS_SetProperty($ParentID, \cast\IO\Property::Open, false);
            @IPS_ApplyChanges($ParentID);
            $this->SetWatchdogTimer(true);
            return;
        }

        if (IPS_GetProperty($ParentID, \cast\IO\Property::Port) != $this->ReadPropertyInteger(\cast\Device\Property::Port)) {
            IPS_SetProperty($ParentID, \cast\IO\Property::Port, $this->ReadPropertyInteger(\cast\Device\Property::Port));
        }

        if (IPS_GetProperty($ParentID, \cast\IO\Property::Open) != true) {
            IPS_SetProperty($ParentID, \cast\IO\Property::Open, true);
        }

        @IPS_ApplyChanges($ParentID);
        return;
    }
    /**
     * Interne Funktion des SDK.
     *
     * @access public
     */
    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
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
                            $this->RequestState();
                            break;
                        case IS_EBASE + 3: //ERROR RCP-Server
                        case IS_INACTIVE:
                            $this->SendDebug('IM_CHANGESTATUS', 'not active', 0);
                            $this->SetWatchdogTimer(true);
                            $this->SetTimerInterval(\cast\Device\Timer::ProgressState, 0);
                            break;
                    }
                    $this->SendDebug('MessageSink', 'StatusIsChanging now unlocked', 0);
                    $this->StatusIsChanging = false;
                }
                break;
        }
    }
    public function GetConfigurationForm()
    {
        $Form = json_decode(file_get_contents(__DIR__ . '/form.json'), true);

        $Form['elements'][1]['visible'] = ($this->ParentID ? true : false);
        $Form['elements'][1]['items'][1]['objectID'] = $this->ParentID;
        $Form['elements'][2]['items'][0]['items'][1]['visible'] = $this->ReadPropertyBoolean(\cast\Device\Property::Watchdog);
        $Form['elements'][2]['items'][1]['items'][0]['visible'] = $this->ReadPropertyBoolean(\cast\Device\Property::Watchdog);
        if ($this->ReadPropertyBoolean(\cast\Device\Property::Watchdog)) {
            $Form['elements'][2]['items'][1]['items'][1]['visible'] = ($this->ReadPropertyInteger(\cast\Device\Property::ConditionType) == 1);
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
        $Config[\Cast\IO\Property::Open] = false;
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::Open)) {
            $Config[\Cast\IO\Property::Open] = ($this->GetStatus() == IS_ACTIVE);
        }
        $Config[\Cast\IO\Property::Port] = $this->ReadPropertyInteger(\Cast\Device\Property::Port);
        $Config[\Cast\IO\Property::UseSSL] = true;
        $Config[\Cast\IO\Property::VerifyHost] = false;
        $Config[\Cast\IO\Property::VerifyPeer] = false;
        return json_encode($Config);
    }

    public function RequestAction($Ident, $Value)
    {
        if ($this->IORequestAction($Ident, $Value)) {
            return;
        }
        switch ($Ident) {
            case \Cast\Device\Property::Watchdog:
                $this->UpdateFormField('Watchdog', 'caption', (bool) $Value ? 'Check every' : 'Check never');
                $this->UpdateFormField('Interval', 'visible', (bool) $Value);
                $this->UpdateFormField('ConditionType', 'visible', (bool) $Value);
                $this->UpdateFormField('ConditionPopup', 'visible', $this->ReadPropertyInteger(\Cast\Device\Property::ConditionType) == 1);
                break;
            case \cast\Device\Property::ConditionType:
                $this->UpdateFormField('ConditionPopup', 'visible', $Value == 1);
                break;

            case \Cast\Commands::Pong:
                $this->SendPong($Value);
                break;
            case \Cast\Commands::Connect:
                $this->Connect($Value);
                if ($this->TransportId != $Value) {
                    $this->TransportId = $Value;
                    $this->RequestMediaState();
                }
                break;
            case \Cast\Device\VariableIdents::Volume:
                $this->SetVolumen($Value / 100);
                break;
            case \Cast\Device\VariableIdents::Muted:
                $this->SetMute((bool) $Value);
                break;
            case \Cast\Device\VariableIdents::AppId:
                $this->LaunchApp($Value);
                break;
            case \Cast\Device\VariableIdents::PlayerState:
                $this->SetPlayerState(\Cast\PlayerState::$IntToAction[(int) $Value]);
                break;
            case \Cast\Device\VariableIdents::RepeatMode:
                $this->SetRepeat($Value);
                break;
            case 'ProgressState':
                if ($this->DurationRAW) {
                    if ($this->PositionRAW < $this->DurationRAW) {
                        $this->PositionRAW++;
                        $Value = (100 / $this->DurationRAW) * $this->PositionRAW;
                        $this->SetValue('currentTime', $Value);
                        $this->SetValue('position', \Cast\Device\TimeConvert::ConvertSeconds($this->PositionRAW));
                        if ($this->ReadPropertyBoolean('enableRawPosition')) {
                            $this->SetValue('positionRaw', $this->PositionRAW);
                        }
                    }
                } else {
                    $this->SetTimerInterval('ProgressState', 0);
                }
                break;
            case 'positionRaw':
                if ($this->isSeekable) {
                    $this->Seek((float) $Value);
                }
                break;
            case 'currentTime':
                if ($this->isSeekable) {
                    if ($this->DurationRAW) {
                        $Time = ($this->DurationRAW / 100) * $Value;
                        $this->Seek($Time);
                    }
                }
                break;
        }
    }

    public function SetVolumen(float $Level)
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::SetVolume, ['requestId'=>$RequestId, 'volume'=>['level'=>$Level]]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::ReceiverNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        if ($Payload) {
            $this->DecodeEvent($CMsg, $Payload);
        }
    }

    public function SetMute(bool $Mute)
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::SetVolume, ['requestId'=>$RequestId, 'volume'=>['muted'=>$Mute]]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::ReceiverNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        if ($Payload) {
            $this->DecodeEvent($CMsg, $Payload);
        }
    }

    public function LaunchApp(string $AppId)
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Launch, ['requestId'=>$RequestId, 'appId'=>$AppId]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::ReceiverNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        if ($Payload) {
            $this->DecodeEvent($CMsg, $Payload);
        }
    }
    public function SetPlayerState(string $State)
    {
        $RequestId = $this->RequestId++;
        $Payload = [];
        if ($State == \Cast\Commands::Next) {
            $State = \Cast\Commands::QueueUpdate;
            $Payload['jump'] = 1;
        }

        if ($State == \Cast\Commands::Prev) {
            $State = \Cast\Commands::QueueUpdate;
            $Payload['jump'] = -1;
        }
        //$Urn = ($this->ActualUrn ? $this->ActualUrn : \Cast\Urn::MediaNamespace);
        $Urn = \Cast\Urn::MediaNamespace;
        $Payload = \Cast\Payload::makePayload($State, array_merge($Payload, ['requestId'=>$RequestId, 'mediaSessionId'=>$this->MediaSessionId]));
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, $Urn, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        if ($Payload) {
            $this->DecodeEvent($CMsg, $Payload);
        }
    }
    public function Seek(float $Time)
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Seek, ['currentTime'=>$Time, 'requestId'=>$RequestId, 'mediaSessionId'=>$this->MediaSessionId]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MediaNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        if ($Payload) {
            $this->DecodeEvent($CMsg, $Payload);
        }
    }

    public function SeekRelative(float $Time)
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Seek, ['relativeTime'=>$Time, 'requestId'=>$RequestId, 'mediaSessionId'=>$this->MediaSessionId]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MediaNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        if ($Payload) {
            $this->DecodeEvent($CMsg, $Payload);
        }
    }

    public function SetRepeat(string $Mode)
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::QueueUpdate, ['repeatMode'=>$Mode, 'requestId'=>$RequestId, 'mediaSessionId'=>$this->MediaSessionId]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, \Cast\Urn::MediaNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        if ($Payload) {
            $this->DecodeEvent($CMsg, $Payload);
        }
    }
    public function GetAppAvailability()
    {
        $RequestId = $this->RequestId++;
        $Request = [
            'requestId'=> $RequestId,
            'appId'    => array_keys(\Cast\Apps::$Apps)
        ];
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::GetAppAvailability, $Request);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::ReceiverNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        if ($Payload) {
//            $this->DecodeEvent($CMsg, $Payload);
        }
    }
    // LAUNCH {"appId":"233637DE"} an receiver

    //{ "contentId": "http://192.168.201.253:3777/user/squeezebox/micha.png", }
    // {"media":{"contentId":"http://192.168.201.253:3777/user/squeezebox/micha.png","streamType":"NONE","contentType":"image/PNG"}}
    //{"media":{"contentId":"http://192.168.201.253:3777/user/squeezebox/micha.png","streamType":"LIVE","contentType":"image/png"},"autoplay":true,"repeat":true}
    //{"mediaSessionId":12}

    // CLOSE an tp.connection und mit transportid => schließt die app
//
    public function RequestState()
    {
        $this->Connect();
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::GetStatus, ['requestId'=>$RequestId]);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::ReceiverNamespace, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        if ($Payload) {
            $this->DecodeEvent($CMsg, $Payload);
        }
    }

    public function RequestMediaState()
    {
        $RequestId = $this->RequestId++;
        //$Urn = ($this->ActualUrn ? $this->ActualUrn : \Cast\Urn::MediaNamespace);
        $Urn = \Cast\Urn::MediaNamespace;
        //if ($Urn == \Cast\Urn::DefaultMediaRender) {
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::GetStatus, ['requestId'=>$RequestId]);
        //}

        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, $Urn, 0, $Payload]);

        /*
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::GetStatus, ['requestId'=>$RequestId]);
        $Urn = 'urn:x-cast:com.google.cast.remotecontrol';
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'system-0', $Urn, 0, $Payload]);
         */
        $Payload = $this->Send($CMsg, $RequestId);
        if ($Payload) {
            $this->DecodeEvent($CMsg, $Payload);
        } else {
            $this->TransportId = '';
            $this->MediaSessionId = 0;
            if ($this->ReadPropertyBoolean('enableRawPosition')) {
                $this->DisableAction('positionRaw');
            }
            $this->DisableAction('currentTime');
            $this->DisableAction(\Cast\Device\VariableIdents::RepeatMode);
            $this->DisableAction(\Cast\Device\VariableIdents::PlayerState);
        }
    }

    public function SendCommand(string $URN, string $Command, array $Payload = [])
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload($Command, array_merge($Payload, ['requestId'=>$RequestId]));
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', 'urn:x-cast:com.google.cast.' . $URN, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        if ($Payload) {
            $this->DecodeEvent($CMsg, $Payload);
        }
    }

    public function SendCommandToApp(string $URN, string $Command, array $Payload = [])
    {
        $RequestId = $this->RequestId++;
        $Payload = \Cast\Payload::makePayload($Command, array_merge($Payload, ['requestId'=>$RequestId]));
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $this->TransportId, 'urn:x-cast:com.google.cast.' . $URN, 0, $Payload]);
        $Payload = $this->Send($CMsg, $RequestId);
        if ($Payload) {
            $this->DecodeEvent($CMsg, $Payload);
        }
    }

    public function SendPing()
    {
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Ping);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, 'receiver-0', \Cast\Urn::HeartbeatNamespace, 0, $Payload]);
        $this->Send($CMsg);
    }

    public function ReceiveData($JSONString)
    {
        $Data = $this->Buffer . utf8_decode((json_decode($JSONString))->Buffer);
        $this->DecodePacket($Data);
    }

    /**
     * IPS-Instanz-Funktion 'CCAST_Watchdog'.
     * Sendet einen TCP-Ping an das Gerät und prüft die Erreichbarkeit.
     * Wird erkannt, dass das Gerät erreichbar ist, wird versucht eine TCP-Verbindung aufzubauen.
     *
     * @access public
     */
    public function Watchdog()
    {
        $this->SendDebug(__FUNCTION__, 'run', 0);
        if (!$this->ReadPropertyBoolean(\cast\Device\Property::Open)) {
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
            IPS_SetProperty($this->ParentID, \cast\Device\Property::Open, true);
            @IPS_ApplyChanges($this->ParentID);
        }
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
                if ($this->GetStatus() != IS_ACTIVE) {
                    $this->SetStatus(IS_ACTIVE);
                }
                break;
            case IS_INACTIVE:
                if ($this->GetStatus() != IS_INACTIVE) {
                    $this->SetStatus(IS_INACTIVE);
                } break;
            default:
                if ($this->ParentID > 0) {
                    IPS_SetProperty($this->ParentID, \cast\IO\Property::Open, false);
                    @IPS_ApplyChanges($this->ParentID);
                }
                break;
        }
        $this->SendDebug('IOChangeState', 'StatusIsChanging now unlocked', 0);
        $this->StatusIsChanging = false;
    }
    
    private function CheckCondition(): bool
    {
        if (!$this->ReadPropertyBoolean(\cast\Device\Property::Watchdog)) {
            return true;
        }
        switch ($this->ReadPropertyInteger(\cast\Device\Property::ConditionType)) {
            case 0:
                $Result = @Sys_Ping($this->Host, 500);
                $this->SendDebug('Pinging', $Result, 0);
                return $Result;
            case 1:
                $Result = IPS_IsConditionPassing($this->ReadPropertyString(\cast\Device\Property::WatchdogCondition));
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
     * @param bool $Active True für aktiv, false für desaktiv.
     */
    private function SetWatchdogTimer(bool $Active): void
    {
        if ($this->ReadPropertyBoolean(\Cast\Device\Property::Open)) {
            if ($this->ReadPropertyBoolean(\Cast\Device\Property::Watchdog)) {
                $Interval = $this->ReadPropertyInteger(\Cast\Device\Property::Interval);
                $Interval = ($Interval < 5) ? 0 : $Interval;
                if ($Active) {
                    $this->SetTimerInterval(\Cast\Device\Timer::Watchdog, $Interval * 1000);
                    $this->SendDebug(\Cast\Device\Timer::Watchdog, 'active', 0);
                    return;
                }
            }
        }
        $this->SetTimerInterval(\Cast\Device\Timer::Watchdog, 0);
        $this->SendDebug(\Cast\Device\Timer::Watchdog, 'inactive', 0);
    }

    private function SetIcon(string $iconUrl)
    {
        $MediaId = @IPS_GetObjectIDByIdent('iconUrl', $this->InstanceID);
        if ($MediaId < 10000) {
            $MediaId = IPS_CreateMedia(1);
            IPS_SetParent($MediaId, $this->InstanceID);
            IPS_SetIdent($MediaId, 'iconUrl');
            IPS_SetName($MediaId, 'App Icon');
            IPS_SetPosition($MediaId, 3);
            IPS_SetMediaCached($MediaId, true);
            $filename = 'media' . DIRECTORY_SEPARATOR . 'CCast_iconUrl_' . $this->InstanceID . '.png';
            IPS_SetMediaFile($MediaId, $filename, false);
            $this->SendDebug('Create Media', $filename, 0);
        }
        if ($iconUrl === '') {
            //todo umrechnung fehlt
            $MediaRAW = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'imgs' . DIRECTORY_SEPARATOR . 'no_image.png');
        } else {
            $Size = $this->ReadPropertyInteger(\Cast\Device\Property::AppIconSizeWidth);
            $Found = strrpos($iconUrl, '=');
            if ($Found !== false) {
                $iconUrl = substr($iconUrl, 0, $Found);
            }
            $iconUrl .= '=w' . $Size;
            $MediaRAW = @file_get_contents($iconUrl);
        }
        $this->SendDebug('Refresh IconUrl', $iconUrl, 0);
        IPS_SetMediaContent($MediaId, base64_encode($MediaRAW));
    }
    private function SetMediaImage(string $MediaUrl)
    {
        $MediaId = @IPS_GetObjectIDByIdent('mediaUrl', $this->InstanceID);
        $this->SendDebug('Refresh MediaUrl', $MediaId, 0);
        if ($MediaId < 10000) {
            $MediaId = IPS_CreateMedia(1);
            IPS_SetParent($MediaId, $this->InstanceID);
            IPS_SetIdent($MediaId, 'mediaUrl');
            IPS_SetName($MediaId, 'Media');
            IPS_SetPosition($MediaId, 3);
            IPS_SetMediaCached($MediaId, true);
            $filename = 'media' . DIRECTORY_SEPARATOR . 'CCast_mediaUrl_' . $this->InstanceID . '.jpg';
            IPS_SetMediaFile($MediaId, $filename, false);
            $this->SendDebug('Create Media', $filename, 0);
        }
        if ($MediaUrl === '') {
            //todo umrechnung fehlt
            $IconMediaId = @IPS_GetObjectIDByIdent('iconUrl', $this->InstanceID);
            if (IPS_MediaExists($IconMediaId)) {
                $MediaRAW = base64_decode(IPS_GetMediaContent($IconMediaId));
            } else {
                $MediaRAW = file_get_contents(__DIR__ . DIRECTORY_SEPARATOR . 'imgs' . DIRECTORY_SEPARATOR . 'no_image.png');
            }
        } else {
            $Size = $this->ReadPropertyInteger(\Cast\Device\Property::MediaSizeWidth);
            if (strpos($MediaUrl, 'googleusercontent')) {
                $Found = strrpos($MediaUrl, '=');
                if ($Found !== false) {
                    $MediaUrl = substr($MediaUrl, 0, $Found);
                    $MediaUrl .= '=w' . $Size;
                }
                $MediaRAW = @file_get_contents($MediaUrl);
            } else { // todo Bild laden und umrechnen
                $MediaRAW = @file_get_contents($MediaUrl);
            }
        }

        $this->SendDebug('Refresh mediaUrl', $MediaUrl, 0);

        IPS_SetMediaContent($MediaId, base64_encode($MediaRAW));
    }
    private function ConnectToApp(string $TransportId): void
    {
        //if ($this->TransportId != $TransportId) {

        IPS_RunScriptText('IPS_Sleep(500);IPS_RequestAction(' . $this->InstanceID . ',"' . \Cast\Commands::Connect . '","' . $TransportId . '");');
        //}
    }
    private function Send(\Cast\CastMessage $CMsg, int $RequestId = 0): bool|array
    {
        if ($RequestId) {
            $this->SendDebug('Send (' . $RequestId . ')', $CMsg->__debug(), 0);
            $this->SendQueuePush($RequestId);
        }
        $this->SendDataToParent(json_encode(['DataID' => '{79827379-F36E-4ADA-8A95-5F8D1DC92FA9}', 'Buffer' => utf8_encode($CMsg->getMessage())]));
        if ($RequestId == 0) {
            return true;
        }
        $Result = $this->WaitForResponse($RequestId);
        $this->SendDebug('Result (' . $RequestId . ')', $Result, 0);
        return $Result;
    }
    private function SendPong(string $ReceiverId)
    {
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Pong);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $ReceiverId, \Cast\Urn::HeartbeatNamespace, 0, $Payload]);
        $this->Send($CMsg);
    }
    private function Connect(string $ReceiverId = 'receiver-0')
    {
        $Payload = \Cast\Payload::makePayload(\Cast\Commands::Connect);
        $CMsg = new \Cast\CastMessage([$this->InstanceID, $ReceiverId, \Cast\Urn::ConnectionNamespace, 0, $Payload]);
        $this->Send($CMsg);
    }
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
                        if ($CMsg->getReceiverId() == $this->TransportId) {
                            $this->TransportId = '';
                            $this->MediaSessionId = 0;
                            if ($this->ReadPropertyBoolean('enableRawPosition')) {
                                $this->DisableAction('positionRaw');
                            }
                            $this->DisableAction('currentTime');
                            $this->DisableAction(\Cast\Device\VariableIdents::RepeatMode);
                            $this->DisableAction(\Cast\Device\VariableIdents::PlayerState);
                        }
                        break;
                }
                break;
            case \Cast\Urn::SSE:
                if (array_key_exists('backendData', $Payload)) {
                    $this->SetMediaImage(json_decode($Payload['backendData'], true)[0]);
                }
                break;
            case \Cast\Urn::MediaNamespace:
                switch ($Payload['type']) {
                    case \Cast\Commands::MediaStatus:
                        $Status = array_shift($Payload['status']);
                        if (isset($Status['mediaSessionId'])) {
                            $this->MediaSessionId = $Status['mediaSessionId'];
                        } else {
                            $this->MediaSessionId = 0;
                        }
                        if (isset($Status['supportedMediaCommands'])) {
                            $Commands = \Cast\Commands::ListAvailableCommands($Status['supportedMediaCommands']);
                            if (in_array(\Cast\Commands::Next, $Commands)) {
                                $this->RegisterVariableInteger(\Cast\Device\VariableIdents::PlayerState, 'playerState', '~PlaybackPreviousNext');
                            } else {
                                $this->RegisterVariableInteger(\Cast\Device\VariableIdents::PlayerState, 'playerState', '~Playback');
                            }
                            if (in_array(\Cast\Commands::Pause, $Commands)) {
                                $this->EnableAction(\Cast\Device\VariableIdents::PlayerState);
                            } else {
                                $this->DisableAction(\Cast\Device\VariableIdents::PlayerState);
                            }
                            if (in_array(\Cast\Commands::RepeatOne, $Commands)) {
                                $this->EnableAction(\Cast\Device\VariableIdents::RepeatMode);
                            } else {
                                $this->DisableAction(\Cast\Device\VariableIdents::RepeatMode);
                            }

                            if (in_array(\Cast\Commands::Shuffle, $Commands)) {
                                $this->EnableAction(\Cast\Device\VariableIdents::Shuffle);
                            } else {
                                $this->DisableAction(\Cast\Device\VariableIdents::Shuffle);
                            }
                            if (in_array(\Cast\Commands::Seek, $Commands)) {
                                $this->isSeekable = true;
                                if ($this->ReadPropertyBoolean('enableRawPosition')) {
                                    $this->EnableAction('positionRaw');
                                }
                                $this->EnableAction('currentTime');
                            } else {
                                $this->isSeekable = false;
                                if ($this->ReadPropertyBoolean('enableRawPosition')) {
                                    $this->DisableAction('positionRaw');
                                }
                                $this->DisableAction('currentTime');
                            }
                            if (in_array(\Cast\Commands::RepeatAll, $Commands) || in_array(\Cast\Commands::RepeatOne, $Commands)) {
                                $this->EnableAction(\Cast\Device\VariableIdents::RepeatMode);
                            } else {
                                $this->DisableAction(\Cast\Device\VariableIdents::RepeatMode);
                            }
                            $this->SendDebug('COMMANDS', $Commands, 0);
                        }
                        if ($Status['playerState'] != \Cast\PlayerState::Buffering) {
                            $this->SetValue(\Cast\Device\VariableIdents::PlayerState, \Cast\PlayerState::$StateToInt[$Status['playerState']]);
                        }
                        //unset($Status['playerState']);
                        /*foreach ($Status as $AppVariableIdent => $AppValue) {
                            if (@$this->GetIDForIdent($AppVariableIdent)) {
                                $this->SetValue($AppVariableIdent, $AppValue);
                            }
                        }*/

                        /*                      Medienstream volumen ! nicht Device Volume
                           if (isset($Status['volume']['level'])) {
                                                    $this->SetValue(\cast\Device\VariableIdents::Volume, (int) ($Status['volume']['level'] * 100));
                                                }
                                                if (isset($Status['volume']['muted'])) {
                                                    $this->SetValue(\cast\Device\VariableIdents::Muted, $Status['volume']['muted']);
                                                }
                         */                        if (array_key_exists('media', $Status)) {
                            $Media = $Status['media'];
                            if (isset($Media['metadata']['title'])) {
                                $this->SetValue('title', $Media['metadata']['title']);
                            } else {
                                $this->SetValue('title', '');
                            }
                            if (isset($Media['metadata']['artist'])) {
                                $this->SetValue('artist', $Media['metadata']['artist']);
                            } else {
                                $this->SetValue('artist', '');
                            }
                            if (isset($Media['metadata']['albumName'])) {
                                $this->SetValue('collection', $Media['metadata']['albumName']);
                            } else {
                                if (isset($Media['customData']['mediaItem']['title'])) {
                                    $this->SetValue('collection', $Media['customData']['mediaItem']['title']);
                                } else {
                                    $this->SetValue('collection', '');
                                }
                            }
                            //metadata:title
                            //metadata:artist
                            //customData:mediaItem:title

                            //metadata:images:0:url
                            if (isset($Media['metadata']['images'][0]['url'])) {
                                $this->SetMediaImage($Media['metadata']['images'][0]['url']);
                            }

                            //customData:artists
                            //customData:title

                            if (isset($Media['duration'])) {
                                //$this->SetTimerInterval('ProgressState', 1000);
                                $this->DurationRAW = $Media['duration'];
                                $this->SetValue('duration', \Cast\Device\TimeConvert::ConvertSeconds($Media['duration']));
                                if ($this->ReadPropertyBoolean('enableRawDuration')) {
                                    $this->SetValue('durationRaw', $Media['duration']);
                                }
                            } else {
                                //$this->SetTimerInterval('ProgressState', 0);
                                $this->DurationRAW = 0;
                                $this->SetValue('duration', '');
                                if ($this->ReadPropertyBoolean('enableRawDuration')) {
                                    $this->SetValue('durationRaw', 0);
                                }
                            }
                        }

                        if ($Status['playerState'] == 'PLAYING') {
                            $this->SetTimerInterval('ProgressState', 1000);
                        } else {
                            $this->SetTimerInterval('ProgressState', 0);
                        }
                        if (isset($Status['currentTime'])) {
                            if ($this->DurationRAW) {
                                $Value = (100 / $this->DurationRAW) * $Status['currentTime'];
                                $this->SetValue('currentTime', $Value);
                            } else {
                                $this->SetValue('currentTime', 0);
                            }
                            $this->PositionRAW = $Status['currentTime'];
                            $this->SetValue('position', \Cast\Device\TimeConvert::ConvertSeconds($Status['currentTime']));
                            if ($this->ReadPropertyBoolean('enableRawPosition')) {
                                $this->SetValue('positionRaw', $Status['currentTime']);
                            }
                        } else {
                            $this->PositionRAW = 0;
                            $this->SetValue('position', '');
                            if ($this->ReadPropertyBoolean('enableRawPosition')) {
                                $this->SetValue('positionRaw', 0);
                            }
                        }
                        //queueData
                        if (isset($Status['repeatMode'])) {
                            $this->SetValue(\Cast\Device\VariableIdents::RepeatMode, $Status['repeatMode']);
                        }
                        //queueData:repeatMode | REPEAT_OFF
                        //queueData:shuffle | FALSE

                        //items
                        //items:0:media:duration
                        //items:0:media:metadata:title
                        //items:0:media:customData:mediaItem:title
                        //items:0:media:customData:artists
                        //items:0:media:customData:title
                        break;
                }
                break;
            case \Cast\Urn::ReceiverNamespace:
                switch ($Payload['type']) {
                    case \Cast\Commands::LaunchStatus:
                        break;
                    case \Cast\Commands::LaunchError:
                        break;
                    case \Cast\Commands::ReceiverStatus:

                        $Status = $Payload['status'];

                        if (isset($Status['volume']['level'])) {
                            $this->SetValue(\cast\Device\VariableIdents::Volume, (int) ($Status['volume']['level'] * 100));
                        }
                        if (isset($Status['volume']['muted'])) {
                            $this->SetValue(\cast\Device\VariableIdents::Muted, $Status['volume']['muted']);
                        }

                        if (array_key_exists('applications', $Status)) {
                            $ActualApp = array_shift($Status['applications']);

                            /*if (isset($ActualApp['sessionId'])) {
                                $this->MediaSessionId = $ActualApp['sessionId'];
                            } else {
                                $this->MediaSessionId = 0;
                            }*/
                            /*if (isset($ActualApp['namespaces'])) {
                                $Ns = array_pop($ActualApp['namespaces']);
                                if (isset($Ns['name'])) {
                                    $this->ActualUrn = $Ns['name'];
                                }
                            }*/
                            if (array_key_exists('iconUrl', $ActualApp)) {
                                $this->SetIcon($ActualApp['iconUrl']);
                                unset($ActualApp['iconUrl']);
                            }
                            foreach ($ActualApp as $AppVariableIdent => $AppValue) {
                                if (@$this->GetIDForIdent($AppVariableIdent)) {
                                    $this->SetValue($AppVariableIdent, $AppValue);
                                }
                            }

                            $this->ConnectToApp($ActualApp['transportId']);
                        }

                        break;
                }
                break;
            default:
                break;
        }
    }
    private function DecodePacket(string $Data)
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
        $this->Buffer = $Tail;
        if (strlen($Tail) > 4) {
            $this->DecodePacket('');
        }
    }

    /**
     * Wartet auf eine Antwort einer Anfrage an den LMS.
     *
     * @param int $RequestId
     * @return array|false Enthält ein Array mit den Daten der Antwort. False bei einem Timeout
     */
    private function WaitForResponse(int $RequestId): false|array
    {
        for ($i = 0; $i < 1000; $i++) {
            $Buffer = $this->ReplyCMsgPayload;
            if (!array_key_exists($RequestId, $Buffer)) {
                return false;
            }
            if (count($Buffer[$RequestId])) {
                $this->SendQueueRemove($RequestId);
                return $Buffer[$RequestId];
            }
            IPS_Sleep(2);
        }
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