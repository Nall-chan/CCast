<?php

declare(strict_types=1);

namespace {
    $AutoLoader = new AutoLoaderCCast('Google\Protobuf');
    $AutoLoader->register();

    class AutoLoaderCCast
    {
        private $namespace;

        public function __construct($namespace = null)
        {
            $this->namespace = $namespace;
        }

        public function register()
        {
            spl_autoload_register([$this, 'loadClass']);
        }

        public function loadClass($className)
        {
            $LibPath = __DIR__ . DIRECTORY_SEPARATOR;
            $file = $LibPath . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
            if (file_exists($file)) {
                require_once $file;
            }
        }
    }

    require_once __DIR__ . '/CCastProto.php';
    require_once __DIR__ . '/CCastMessage.php';
}

namespace Cast\mDNS{
    const GUID = '{780B2D48-916C-4D59-AD35-5A429B2355A5}';
}

namespace Cast\IO{
    const GUID = '{3CFF0FD9-E306-41DB-9B5A-9D06D38576C3}';

    class Property
    {
        public const Open = 'Open';
        public const Host = 'Host';
        public const Port = 'Port';
        public const UseSSL = 'UseSSL';
        public const VerifyHost = 'VerifyHost';
        public const VerifyPeer = 'VerifyPeer';
    }
}

namespace Cast\Device
{
    const GUID = '{9034A9D8-F004-22EA-9391-BF2E5E1CAB31}';

    class Property
    {
        public const Open = 'Open';
        public const Port = 'Port';
        public const Watchdog = 'Watchdog';
        public const Interval = 'Interval';
        public const ConditionType = 'ConditionType';
        public const WatchdogCondition = 'WatchdogCondition';
        public const MediaSizeWidth = 'MediaSizeWidth';
        public const AppIconSizeWidth = 'AppIconSizeWidth';
        public const EnableRawDuration = 'enableRawDuration';
        public const EnableRawPosition = 'enableRawPosition';
    }

    class VariableIdent
    {
        public const AppId = 'appId';
        public const Volume = 'level';
        public const Muted = 'muted';
        public const PlayerState = 'playerState';
        public const RepeatMode = 'repeatMode';
        public const DurationRaw = 'durationRaw';
        public const PositionRaw = 'positionRaw';
        public const Duration = 'duration';
        public const Position = 'position';
        public const CurrentTime = 'currentTime';
        public const Title = 'title';
        public const Artist = 'artist';
        public const Collection = 'collection';

        /**
         * @todo Fehlt noch
         */
        public const Shuffle = 'shuffleMode';
    }

    class Timer
    {
        public const Watchdog = 'WatchdogTimer';
        public const ProgressState = 'ProgressState';
        public const KeepAlive = 'KeepAlive';
    }

    class TimeConvert
    {
        public static function ConvertSeconds(float $Time)
        {
            if ($Time > 3600) {
                return sprintf('%02d:%02d:%02d', ($Time / 3600), ($Time / 60 % 60), $Time % 60);
            } else {
                return sprintf('%02d:%02d', ($Time / 60 % 60), $Time % 60);
            }
        }
    }
}

namespace Cast\Youtube{
    const BASE_URL = 'https://www.youtube.com/';
    const LOUNGE_TOKEN_URL = BASE_URL . 'api/lounge/pairing/get_lounge_token_batch';

}

namespace Cast\YoutubeMusic{
    const BASE_URL = 'https://music.youtube.com/';
    const LOUNGE_TOKEN_URL = BASE_URL . 'api/lounge/pairing/get_lounge_token_batch';

}

namespace Cast
{
    class Urn
    {
        //public const AuthNamespace = 'urn:x-cast:com.google.cast.tp.deviceauth';
        public const ConnectionNamespace = 'urn:x-cast:com.google.cast.tp.connection';

        public const HeartbeatNamespace = 'urn:x-cast:com.google.cast.tp.heartbeat';
        public const ReceiverNamespace = 'urn:x-cast:com.google.cast.receiver';
        public const MediaNamespace = 'urn:x-cast:com.google.cast.media';
        public const MultiZoneNamespace = 'urn:x-cast:com.google.cast.multizone';
        //urn:x-cast:com.google.cast.remotecontrol
        //urn:x-cast:com.google.cast.system
        public const BroadcastNamespace = 'urn:x-cast:com.google.cast.broadcast';
        public const SSE = 'urn:x-cast:com.google.cast.sse'; //Backdrop
        public const DefaultMediaRender = 'urn:x-cast:com.google.cast.cac';
        // remoting
        // webrtc
        public const YouTube = 'urn:x-cast:com.google.youtube.mdx';
    }

    class Apps
    {
        public const AndroidNativeApp = 'AndroidNativeApp';
        public const Audible = '25456794'; //no response on GetAppAvailability
        public const Backdrop = 'E8C28D3C';
        public const CastBridge = '46C1A819';
        public const ChromeMirroring = '0F5096E8';
        public const DefaultMediaReceiver = 'CC1AD845'; //'85CDB22F' old
        public const DisneyPlus = 'C3DE6BC2';
        public const EurekaIdleScreen = 'EurekaIdleScreen'; //not connected to internet
        public const GooglePhotos = '96084372'; //no response on GetAppAvailability
        public const GooglePodcast = '3DFCDBD1';
        public const Netflix = 'CA5E8412';
        public const ScreenMirroring = '674A0243';
        public const Spotify = 'CC32E753';
        public const YouTube = '233637DE';
        public const YouTubeMusic = '2DB7CC49';

        public static $Apps =
            [
                self::Audible              => 'Audible',
                self::Backdrop             => 'Backdrop',
                self::CastBridge           => 'AirConnect & CastBridge',
                self::ChromeMirroring      => 'Chrome Mirroring',
                self::DefaultMediaReceiver => 'Default Media Receiver',
                self::DisneyPlus           => 'Disney+',
                self::EurekaIdleScreen     => 'Idle Screen',
                self::GooglePhotos         => 'Google Photos',
                self::GooglePodcast        => 'Google Podcast',
                self::Netflix              => 'Netflix',
                self::ScreenMirroring      => 'Screen Mirroring',
                self::Spotify              => 'Spotify',
                self::YouTube              => 'YouTube',
                self::YouTubeMusic         => 'YouTube Music',
            ];

        public static function getAllAppsAsProfileAssoziation(): array
        {
            return array_map(function ($k, $v)
            {
                return [$k, $v, '', -1];
            }, array_keys(self::$Apps), array_values(self::$Apps));
        }
    }

    class Commands
    {
        public const Ping = 'PING'; //heartbeat
        public const Pong = 'PONG'; //heartbeat

        public const Connect = 'CONNECT'; // connection
        public const Close = 'CLOSE'; // connection

        public const GetStatus = 'GET_STATUS'; //Receiver, multizone, Media (to transportid)
        public const ReceiverStatus = 'RECEIVER_STATUS'; // Receiver
        public const MediaStatus = 'MEDIA_STATUS';  // nur wenn aktiv
        public const MultiZoneStatus = 'MULTIZONE_STATUS'; // multizone

        public const GetAppAvailability = 'GET_APP_AVAILABILITY'; // Receiver appId as array
        public const AppUnavailable = 'APP_UNAVAILABLE';
        public const AppAvailable = 'APP_AVAILABLE';

        public const RPC = 'RPC';
        public const Broadcast = 'APPLICATION_BROADCAST';
        public const Launch = 'LAUNCH'; //Receiver
        public const Load = 'LOAD';
        public const LaunchStatus = 'LAUNCH_STATUS';
        public const LaunchError = 'LAUNCH_ERROR';
        public const Offer = 'OFFER';
        public const Answer = 'ANSWER';

        public const SetVolume = 'SET_VOLUME';
        public const UserAction = 'USER_ACTION';

        //??
        public const GetCapabilities = 'GET_CAPABILITIES';
        public const CapabilitiesResponse = 'CAPABILITIES_RESPONSE';

        public const StatusResponse = 'STATUS_RESPONSE';
        public const InvalidPlayerState = 'INVALID_PLAYER_STATE';
        public const LoadFailed = 'LOAD_FAILED';
        public const LoadCancelled = 'LOAD_CANCELLED';
        public const InvalidRequest = 'INVALID_REQUEST';
        public const Presentation = 'PRESENTATION';
        public const Other = 'OTHER';

        /*
            { TEXT: "TEXT", AUDIO: "AUDIO", VIDEO: "VIDEO" });

            QUEUE_CHANGE: "QUEUE_CHANGE",
            QUEUE_ITEMS: "QUEUE_ITEMS",
            QUEUE_ITEM_IDS: "QUEUE_ITEM_IDS",
            SHUTDOWN: "SHUTDOWN",
            PLAY_AGAIN: "PLAY_AGAIN",
            SEEK: "SEEK",
            SET_PLAYBACK_RATE: "SET_PLAYBACK_RATE",
            EDIT_TRACKS_INFO: "EDIT_TRACKS_INFO",
            EDIT_AUDIO_TRACKS: "EDIT_AUDIO_TRACKS"
            PRECACHE: "PRECACHE",
            PRELOAD: "PRELOAD",
            QUEUE_LOAD: "QUEUE_LOAD",
            QUEUE_INSERT: "QUEUE_INSERT",
            QUEUE_UPDATE: "QUEUE_UPDATE",
            QUEUE_REMOVE: "QUEUE_REMOVE",
            QUEUE_REORDER: "QUEUE_REORDER",
            QUEUE_GET_ITEM_RANGE: "QUEUE_GET_ITEM_RANGE",
            QUEUE_GET_ITEMS: "QUEUE_GET_ITEMS",
            QUEUE_GET_ITEM_IDS: "QUEUE_GET_ITEM_IDS",
            QUEUE_SHUFFLE: "QUEUE_SHUFFLE",

            REQUEST_SEEK: "REQUEST_SEEK",
            REQUEST_LOAD: "REQUEST_LOAD",
            REQUEST_STOP: "REQUEST_STOP",
            REQUEST_PAUSE: "REQUEST_PAUSE",
            REQUEST_PRECACHE: "REQUEST_PRECACHE",
            REQUEST_PLAY: "REQUEST_PLAY",
            REQUEST_PLAY_AGAIN: "REQUEST_PLAY_AGAIN",
            REQUEST_VOLUME_CHANGE: "REQUEST_VOLUME_CHANGE",
            REQUEST_QUEUE_LOAD: "REQUEST_QUEUE_LOAD",
            REQUEST_QUEUE_GET_ITEM_RANGE: "REQUEST_QUEUE_GET_ITEM_RANGE",
            REQUEST_QUEUE_GET_ITEMS: "REQUEST_QUEUE_GET_ITEMS",
            REQUEST_QUEUE_GET_ITEM_IDS: "REQUEST_QUEUE_GET_ITEM_IDS",
            INBAND_TRACK_ADDED: "INBAND_TRACK_ADDED",
            TRACKS_CHANGED: "TRACKS_CHANGED",
         */

        public static function GetType(string $Command): array
        {
            return ['type' => $Command];
        }
    }
    class MediaCommands
    {
        public const Play = 'PLAY';
        public const Pause = 'PAUSE';
        public const Stop = 'STOP';
        public const Seek = 'SEEK';
        public const QueueUpdate = 'QUEUE_UPDATE';
        public const StreamVolume = 'STREAM_VOLUME';  //check
        public const StreamMute = 'STREAM_MUTE';  //check
        public const Next = 'QUEUE_NEXT';
        public const Prev = 'QUEUE_PREV';
        public const Shuffle = 'QUEUE_SHUFFLE';
        public const SkipAd = 'SKIP_AD';  //check
        public const RepeatAll = 'QUEUE_REPEAT_ALL';
        public const RepeatOne = 'QUEUE_REPEAT_ONE';
        public const EditTracks = 'INBAND_TRACK_ADDED';  //check
        public const PlaybackRate = 'PLAYBACK_RATE';  //check SET_PLAYBACK_RATE
        public const Like = 'LIKE';
        public const Dislike = 'DISLIKE';
        public const Follow = 'FOLLOW';
        public const Unfollow = 'UNFOLLOW';
        public const StreamTransfer = 'STREAM_TRANSFER';
        public const Lyrics = 'LYRICS';

        //public const EditTracks = 'EDIT_TRACKS';

        public static $MediaCommands = [
            1       => self::Pause,
            2       => self::Seek,
            4       => self::StreamVolume,
            8       => self::StreamMute,
            64      => self::Next,
            128     => self::Prev,
            256     => self::Shuffle,
            512     => self::SkipAd,
            1024    => self::RepeatAll,
            2048    => self::RepeatOne,
            4096    => self::EditTracks,
            8192    => self::PlaybackRate,
            16384   => self::Like,
            32768   => self::Dislike,
            65536   => self::Follow,
            131072  => self::Unfollow,
            262144  => self::StreamTransfer,
            524288  => self::Lyrics,
        ];

        public static function ListAvailableCommands(int $Available): array
        {
            $Commands = [];
            foreach (self::$MediaCommands as $CommandInt => $CommandValue) {
                if (self::isCommandAvailable($Available, $CommandInt)) {
                    $Commands[] = $CommandValue;
                }
            }
            return $Commands;
        }

        public static function isCommandAvailable(int $Available, int $Command): bool
        {
            return ($Command & $Available) == $Command;
            //return self::$MediaCommands[]
        }
    }
    class PlayerState
    {
        public const Idle = 'IDLE';
        public const Play = 'PLAYING';
        public const Pause = 'PAUSED';
        public const Buffering = 'BUFFERING';

        /*
            +        'IDLE': 'IDLE',
            +        'LOADING': 'LOADING',
            +        'LOADED': 'LOADED',
            +        'PLAYING': 'PLAYING',
            +        'PAUSED': 'PAUSED',
            +        'STOPPED': 'STOPPED',
            +        'SEEKING': 'SEEKING',
            +        'ERROR': 'ERROR'
         */

        public static $StateToInt =
            [
                self::Idle              => 1,
                self::Play              => 2,
                self::Pause             => 3,
            ];

        public static $IntToAction =
            [
                0 => \Cast\MediaCommands::Prev,
                1 => \Cast\MediaCommands::Stop,
                2 => \Cast\MediaCommands::Play,
                3 => \Cast\MediaCommands::Pause,
                4 => \Cast\MediaCommands::Next
            ];
    }

    class Payload
    {
        public const isString = 0;
        public const isBinary = 1;

        public static function makePayload(string $Command, array $Payload = []): string
        {
            return json_encode(
                array_merge(Commands::GetType($Command), $Payload)
            );
        }
    }

    // Class to represent a protobuf object for a command.
    class CastMessageOld
    {
        private const protocol_version = 0;
        private const DataTypInt = 0;
        private const DataTypString = 2;
        private $SourceId;
        private $ReceiverId;
        private $Urn;
        private $PayloadType;
        private $Payload;

        public function __construct(string|array $Data)
        {
            if (is_array($Data)) {
                $this->SourceId = 'sender-' . (string) $Data[0];
                $this->ReceiverId = $Data[1];
                $this->Urn = $Data[2];
                $this->PayloadType = $Data[3];
                $this->Payload = $Data[4];
            } else {
                list(
                    2=> $this->SourceId,
                    3=> $this->ReceiverId,
                    4=> $this->Urn,
                    5=> $this->PayloadType,
                    6=> $this->Payload
                ) = $this->decode($Data);
            }
        }

        public function __debug(): array
        {
            return [
                'SourceId'    => $this->SourceId,
                'ReceiverId'  => $this->ReceiverId,
                'Urn'         => $this->Urn,
                'PayloadType' => $this->PayloadType,
                'Payload'     => ($this->Payload != '') ? json_decode($this->Payload, true) : ''
            ];
        }

        public function getUrn(): string
        {
            return $this->Urn;
        }

        public function getPayload(): ?array
        {
            return ($this->Payload != '') ? json_decode($this->Payload, true) : null;
        }

        public function getSourceId(): string
        {
            return $this->SourceId;
        }

        public function getReceiverId(): string
        {
            return $this->ReceiverId;
        }

        public function getMessage(): string
        {
            return $this->encode();
        }

        private function decode(string $Msg): array
        {
            $Data = [];
            while (strlen($Msg)) {
                list($Index, $Type) = self::decodeIndexAndTyp($Msg);
                switch ($Type) {
                    case self::DataTypInt:
                        $Value = self::decode7BitInt($Msg);
                        break;
                    case self::DataTypString:
                        $Value = self::decodeString($Msg);
                        break;
                    default:
                        $Value = 'invalid';
                        break 2;
                }
                $Data[$Index] = $Value;
            }
            return $Data;
        }

        private static function decodeIndexAndTyp(string &$Msg): array
        {
            $IndexAndTyp = [ord($Msg[0]) >> 3, (ord($Msg[0]) & 0x07)];
            $Msg = substr($Msg, 1);
            return $IndexAndTyp;
        }

        private static function encodeIndexAndTyp(int $Index, int $Type): string
        {
            return chr(bindec(sprintf('%05s', decbin($Index)) .
            sprintf('%03s', decbin($Type))));
        }

        private function encode(): string
        {
            $Data = self::encodeIndexAndTyp(1, self::DataTypInt) .
            self::encode7BitInt(self::protocol_version);
            $Data .= self::encodeIndexAndTyp(2, self::DataTypString) .
            self::encodeString($this->SourceId);
            $Data .= self::encodeIndexAndTyp(3, self::DataTypString) .
            self::encodeString($this->ReceiverId);
            $Data .= self::encodeIndexAndTyp(4, self::DataTypString) .
            self::encodeString($this->Urn);
            $Data .= self::encodeIndexAndTyp(5, self::DataTypInt) .
            self::encode7BitInt($this->PayloadType);
            $Data .= self::encodeIndexAndTyp(6, self::DataTypString) .
            self::encodeString($this->Payload);
            return pack('N', strlen($Data)) . $Data;
        }

        private static function decode7BitInt(string &$Msg): int
        {
            $i = 0;
            $Value = 0;
            do {
                $while = ((ord($Msg[$i]) & 0x80) == 0x80);
                $Value += ((ord($Msg[$i]) & 0x7f) << ($i * 7));
                $i++;
            } while ($while);
            $Msg = substr($Msg, $i);
            return $Value;
        }

        private static function encode7BitInt(int $number): string
        {
            if ($number == 0) {
                return chr(0);
            }
            $bytes = '';
            while ($number > 0) {
                $byte = $number & 0x7F; // Extrahiert die 7 niedrigsten Bits
                $number >>= 7;          // Verschiebt die Bits um 7 Stellen nach rechts
                if ($number > 0) {
                    $byte |= 0x80;     // Setzt das höchstwertige Bit, wenn noch weitere Bytes folgen
                }
                $bytes .= chr($byte); // Fügt das Byte am Ende des Arrays hinzu
            }
            return $bytes;
        }

        private static function decodeString(string &$Msg): string
        {
            $len = self::decode7BitInt($Msg);
            $Value = substr($Msg, 0, $len);
            $Msg = substr($Msg, $len);
            return $Value;
        }

        private static function encodeString(string $str): string
        {
            return self::encode7BitInt(strlen($str)) . $str;
        }
    }

    class CastMessage
    {
        private \Chromecast\CCastMessage $Message;

        public function __construct(string|array $Data)
        {
            $this->Message = new \Chromecast\CCastMessage();
            if (is_array($Data)) {
                //$this->Message->setProtocolVersion(0);
                $this->Message->setSourceId('sender-' . (string) $Data[0]);
                $this->Message->setReceiverId($Data[1]);
                $this->Message->setUrn($Data[2]);
                $this->Message->setPayloadType($Data[3]);
                $this->Message->setPayload($Data[4]);
                //$this->Message = new \Chromecast\CCastMessage($Data);
            } else {
                //$this->Message = new \Chromecast\CCastMessage();
                $this->Message->mergeFromString($Data);
            }
        }

        public function __debug(): array
        {
            $Payload = $this->Message->getPayload();
            return [
                'SourceId'    => $this->Message->getSourceId(),
                'ReceiverId'  => $this->Message->getReceiverId(),
                'Urn'         => $this->Message->getUrn(),
                'PayloadType' => $this->Message->getPayloadType(),
                'Payload'     => $Payload, //($Payload[0] != '{') ? $Payload : json_decode($Payload, true)
                '-------'     => '------------------------------'
            ];
        }

        public function getUrn(): string
        {
            return $this->Message->getUrn();
        }

        public function getPayload(): ?array
        {
            $Payload = $this->Message->getPayload();
            return ($Payload != '') ? (($Payload[0] != '{') ? $Payload : json_decode($Payload, true)) : '';
        }

        public function getSourceId(): string
        {
            return $this->Message->getSourceId();
        }

        public function getReceiverId(): string
        {
            return $this->Message->getReceiverId();
        }

        public function getMessage(): string
        {
            $Data = $this->Message->serializeToString();
            return pack('N', strlen($Data)) . $Data;
            //return $Data;
        }
    }
}