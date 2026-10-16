<?php

declare(strict_types=1);

namespace {
    $autoLoader = new AutoLoaderCCast('Google\Protobuf');
    $autoLoader->Register();

    class AutoLoaderCCast
    {
        private $namespace;

        public function __construct($namespace = null)
        {
            $this->namespace = $namespace;
        }

        public function Register()
        {
            spl_autoload_register([$this, 'LoadClass']);
        }

        public function LoadClass($className)
        {
            $libPath = __DIR__ . DIRECTORY_SEPARATOR;
            $file = $libPath . str_replace('\\', DIRECTORY_SEPARATOR, $className) . '.php';
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
        public const OPEN = 'Open';
        public const HOST = 'Host';
        public const PORT = 'Port';
        public const USE_SSL = 'UseSSL';
        public const VERIFY_HOST = 'VerifyHost';
        public const VERIFY_PEER = 'VerifyPeer';
    }
}

namespace Cast\Device
{
    const GUID = '{9034A9D8-F004-22EA-9391-BF2E5E1CAB31}';

    class Property
    {
        public const OPEN = 'Open';
        public const WATCHDOG = 'Watchdog';
        public const INTERVAL = 'Interval';
        public const CONDITION_TYPE = 'ConditionType';
        public const WATCHDOG_CONDITION = 'WatchdogCondition';
        public const MEDIA_SIZE_WIDTH = 'MediaSizeWidth';
        public const APP_ICON_SIZE_WIDTH = 'AppIconSizeWidth';
        public const ENABLE_RAW_DURATION = 'enableRawDuration';
        public const ENABLE_RAW_POSITION = 'enableRawPosition';
        public const ENABLE_PLAYLIST = 'enablePlaylist';
    }

    class VariableIdent
    {
        public const APP_ID = 'appId';
        public const VOLUME = 'level';
        public const MUTED = 'muted';
        public const PLAYER_STATE = 'playerState';
        public const REPEAT_MODE = 'repeatMode';
        public const DURATION_RAW = 'durationRaw';
        public const POSITION_RAW = 'positionRaw';
        public const DURATION = 'duration';
        public const POSITION = 'position';
        public const CURRENT_TIME = 'currentTime';
        public const TITLE = 'title';
        public const ARTIST = 'artist';
        public const COLLECTION = 'collection';
        public const SHUFFLE = 'shuffleMode';
        public const PLAYLIST = 'playlist';
    }

    class Profile
    {
        public const APP_ID_PREFIX = 'CCast.AppId.';
        public const DURATION_SECONDS = 'CCast.DurationSeconds';
        public const DURATION = 'CCast.Duration';
        public const POSITION = 'CCast.Position';
        public const COLLECTION = 'CCast.Collection';
        public const SUFFIX_SECONDS = ' s';
    }

    class ProfileIcon
    {
        public const APP_ID = 'TV';
        public const DURATION = 'Hourglass';
        public const POSITION = 'Clock';
        public const COLLECTION = 'Music';
    }

    class Attribute
    {
        public const KNOWN_APPS = 'KnownApps';
    }

    class Timer
    {
        public const WATCHDOG = 'WatchdogTimer';
        public const PROGRESS_STATE = 'ProgressState';
        public const KEEP_ALIVE = 'KeepAlive';
    }

    class TimeConvert
    {
        /**
         * Wandelt Sekunden in einen Zeitstring (hh:mm:ss bzw. mm:ss) um.
         *
         * @param float $time Zeit in Sekunden
         * @return string Formatierte Zeit
         */
        public static function ConvertSeconds(float $time): string
        {
            $seconds = (int) $time;
            if ($seconds > 3600) {
                return sprintf('%02d:%02d:%02d', intdiv($seconds, 3600), intdiv($seconds, 60) % 60, $seconds % 60);
            }
            return sprintf('%02d:%02d', intdiv($seconds, 60) % 60, $seconds % 60);
        }
    }
}

namespace Cast\Youtube{
    const BASE_URL = 'https://www.youtube.com/';
    const LOUNGE_TOKEN_URL = BASE_URL . 'api/lounge/pairing/get_lounge_token_batch';
    // Lounge-Theme des Bildschirms (cl = klassisches YouTube)
    const THEME = 'cl';

}

namespace Cast\YoutubeMusic{
    const BASE_URL = 'https://music.youtube.com/';
    const LOUNGE_TOKEN_URL = BASE_URL . 'api/lounge/pairing/get_lounge_token_batch';
    // Lounge-Theme des Bildschirms (m = YouTube Music), sonst antwortet die Lounge mit unmatchingTheme
    const THEME = 'm';

}

namespace Cast
{
    class Urn
    {
        //public const AuthNamespace = 'urn:x-cast:com.google.cast.tp.deviceauth';
        public const CONNECTION_NAMESPACE = 'urn:x-cast:com.google.cast.tp.connection';
        public const DASH_CAST = 'urn:x-cast:es.offd.dashcast';
        public const HEARTBEAT_NAMESPACE = 'urn:x-cast:com.google.cast.tp.heartbeat';
        public const RECEIVER_NAMESPACE = 'urn:x-cast:com.google.cast.receiver';
        public const MEDIA_NAMESPACE = 'urn:x-cast:com.google.cast.media';
        public const MULTI_ZONE_NAMESPACE = 'urn:x-cast:com.google.cast.multizone';
        //urn:x-cast:com.google.cast.remotecontrol
        //urn:x-cast:com.google.cast.system
        public const BROADCAST_NAMESPACE = 'urn:x-cast:com.google.cast.broadcast';
        public const SSE = 'urn:x-cast:com.google.cast.sse'; //Backdrop
        public const DEFAULT_MEDIA_RENDER = 'urn:x-cast:com.google.cast.cac';
        // remoting
        // webrtc
        public const YOUTUBE = 'urn:x-cast:com.google.youtube.mdx';
    }

    class Apps
    {
        public const ANDROID_NATIVE_APP = 'AndroidNativeApp';
        public const AUDIBLE = '25456794'; //no response on GetAppAvailability
        public const BACKDROP = 'E8C28D3C';
        public const CAST_BRIDGE = '46C1A819';
        public const CHROME_MIRRORING = '0F5096E8';
        public const DASH_CAST = '5C3F0A3C';
        public const DEFAULT_MEDIA_RECEIVER = 'CC1AD845'; //'85CDB22F' old
        public const DISNEY_PLUS = 'C3DE6BC2';
        public const EUREKA_IDLE_SCREEN = 'EurekaIdleScreen'; //not connected to internet
        public const GOOGLE_PHOTOS = '96084372'; //no response on GetAppAvailability
        public const GOOGLE_PODCAST = '3DFCDBD1';
        public const NETFLIX = 'CA5E8412';
        public const SCREEN_MIRRORING = '674A0243';
        public const SPOTIFY = 'CC32E753';
        public const YOUTUBE = '233637DE';
        public const YOUTUBE_MUSIC = '2DB7CC49';

        /**
         * Öffentliche App-Konfiguration von Google (liefert u.a. display_name)
         */
        public const APP_CONFIG_URL = 'https://clients3.google.com/cast/chromecast/device/app?a=';

        public const APPS =
            [
                self::ANDROID_NATIVE_APP      => 'Android App',
                self::AUDIBLE                 => 'Audible',
                self::BACKDROP                => 'Backdrop',
                self::CAST_BRIDGE             => 'AirConnect & CastBridge',
                self::CHROME_MIRRORING        => 'Chrome Mirroring',
                self::DASH_CAST               => 'Dashcast',
                self::DEFAULT_MEDIA_RECEIVER  => 'Default Media Receiver',
                self::DISNEY_PLUS             => 'Disney+',
                self::EUREKA_IDLE_SCREEN      => 'Idle Screen',
                self::GOOGLE_PHOTOS           => 'Google Photos',
                self::GOOGLE_PODCAST          => 'Google Podcast',
                self::NETFLIX                 => 'Netflix',
                self::SCREEN_MIRRORING        => 'Screen Mirroring',
                self::SPOTIFY                 => 'Spotify',
                self::YOUTUBE                 => 'YouTube',
                self::YOUTUBE_MUSIC           => 'YouTube Music',
            ];

        /**
         * Liefert die Assoziationen für das App-Profil.
         *
         * @param array $knownApps Zusätzlich gelernte Apps (AppId => Name), überschreiben keine eingebauten Apps
         * @return array Assoziationen [Wert, Name, Icon, Farbe]
         */
        public static function GetAllAppsAsProfileAssociation(array $knownApps = []): array
        {
            $apps = self::APPS + $knownApps;
            return array_map(function ($k, $v)
            {
                return [(string) $k, (string) $v, '', -1];
            }, array_keys($apps), array_values($apps));
        }

        /**
         * Ermittelt den Namen einer App über die öffentliche App-Konfiguration von Google.
         *
         * @param string $appId AppId
         * @return string Name der App oder Leerstring
         */
        public static function GetAppNameFromGoogle(string $appId): string
        {
            if (!preg_match('/^[0-9A-Za-z_\-]+$/', $appId)) {
                return '';
            }
            $response = @Sys_GetURLContentEx(self::APP_CONFIG_URL . $appId, ['Timeout' => 3000]);
            if (!is_string($response) || ($response === '')) {
                return '';
            }
            // Antwort beginnt mit einem XSSI-Schutz-Präfix ( )]}' ) in der ersten Zeile
            $json = substr($response, (int) strpos($response, '{'));
            $data = json_decode($json, true);
            return is_array($data) ? (string) ($data['display_name'] ?? '') : '';
        }
    }

    class Commands
    {
        public const PING = 'PING'; //heartbeat
        public const PONG = 'PONG'; //heartbeat

        public const CONNECT = 'CONNECT'; // connection
        public const CLOSE = 'CLOSE'; // connection

        public const GET_STATUS = 'GET_STATUS'; //Receiver, multizone, Media (to transportid)
        public const RECEIVER_STATUS = 'RECEIVER_STATUS'; // Receiver
        public const MEDIA_STATUS = 'MEDIA_STATUS';  // nur wenn aktiv
        public const MULTI_ZONE_STATUS = 'MULTIZONE_STATUS'; // multizone

        public const GET_APP_AVAILABILITY = 'GET_APP_AVAILABILITY'; // Receiver appId as array
        public const APP_UNAVAILABLE = 'APP_UNAVAILABLE';
        public const APP_AVAILABLE = 'APP_AVAILABLE';

        public const RPC = 'RPC';
        public const BROADCAST = 'APPLICATION_BROADCAST';
        public const LAUNCH = 'LAUNCH'; //Receiver
        public const STOP = 'STOP'; //Receiver, beendet eine App (sessionId)
        public const LOAD = 'LOAD';
        public const LAUNCH_STATUS = 'LAUNCH_STATUS';
        public const LAUNCH_ERROR = 'LAUNCH_ERROR';
        public const OFFER = 'OFFER';
        public const ANSWER = 'ANSWER';

        public const SET_VOLUME = 'SET_VOLUME';
        public const USER_ACTION = 'USER_ACTION';

        //??
        public const GET_CAPABILITIES = 'GET_CAPABILITIES';
        public const CAPABILITIES_RESPONSE = 'CAPABILITIES_RESPONSE';

        public const STATUS_RESPONSE = 'STATUS_RESPONSE';
        public const INVALID_PLAYER_STATE = 'INVALID_PLAYER_STATE';
        public const LOAD_FAILED = 'LOAD_FAILED';
        public const LOAD_CANCELLED = 'LOAD_CANCELLED';
        public const INVALID_REQUEST = 'INVALID_REQUEST';
        public const ERROR = 'ERROR';
        public const PRESENTATION = 'PRESENTATION';
        public const OTHER = 'OTHER';

        // Wiedergabeliste (Media-Namespace)
        public const QUEUE_GET_ITEM_IDS = 'QUEUE_GET_ITEM_IDS';
        public const QUEUE_GET_ITEMS = 'QUEUE_GET_ITEMS';
        public const QUEUE_ITEM_IDS = 'QUEUE_ITEM_IDS';
        public const QUEUE_ITEMS = 'QUEUE_ITEMS';
        public const QUEUE_CHANGE = 'QUEUE_CHANGE';

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

        public static function GetType(string $command): array
        {
            return ['type' => $command];
        }
    }
    class MediaCommands
    {
        public const PLAY = 'PLAY';
        public const PAUSE = 'PAUSE';
        public const STOP = 'STOP';
        public const SEEK = 'SEEK';
        public const QUEUE_UPDATE = 'QUEUE_UPDATE';
        public const STREAM_VOLUME = 'STREAM_VOLUME';  //check
        public const STREAM_MUTE = 'STREAM_MUTE';  //check
        public const NEXT = 'QUEUE_NEXT';
        public const PREV = 'QUEUE_PREV';
        public const SHUFFLE = 'QUEUE_SHUFFLE';
        public const REPEAT_ALL = 'QUEUE_REPEAT_ALL';
        public const REPEAT_ONE = 'QUEUE_REPEAT_ONE';
        public const EDIT_TRACKS = 'INBAND_TRACK_ADDED';  //check
        public const PLAYBACK_RATE = 'PLAYBACK_RATE';  //check SET_PLAYBACK_RATE
        public const STREAM_TRANSFER = 'STREAM_TRANSFER';

        public const LIKE = 'LIKE';
        public const DISLIKE = 'DISLIKE';
        public const FOLLOW = 'FOLLOW';
        public const UNFOLLOW = 'UNFOLLOW';
        public const FLAG = 'FLAG';
        public const SKIP_AD = 'SKIP_AD';  //check
        public const LYRICS = 'LYRICS';

        //public const EditTracks = 'EDIT_TRACKS';

        public const MEDIA_COMMANDS = [
            1       => self::PAUSE,
            2       => self::SEEK,
            4       => self::STREAM_VOLUME,
            8       => self::STREAM_MUTE,
            64      => self::NEXT,
            128     => self::PREV,
            256     => self::SHUFFLE,
            512     => self::SKIP_AD,
            1024    => self::REPEAT_ALL,
            2048    => self::REPEAT_ONE,
            4096    => self::EDIT_TRACKS,
            8192    => self::PLAYBACK_RATE,
            16384   => self::LIKE,
            32768   => self::DISLIKE,
            65536   => self::FOLLOW,
            131072  => self::UNFOLLOW,
            262144  => self::STREAM_TRANSFER,
            524288  => self::LYRICS,
        ];

        /**
         * Befehle, die mit einer Wiedergabeliste (queueData) immer möglich sind.
         * Der Default Media Receiver nimmt QUEUE_UPDATE mit jump, repeatMode und shuffle an,
         * meldet diese Bits aber nicht in supportedMediaCommands (live geprüft 2026-10-16, Nest Hub).
         */
        public const QUEUE_COMMANDS = 64 | 256 | 1024 | 2048;

        public static function ListAvailableCommands(int $available): array
        {
            $commands = [];
            foreach (self::MEDIA_COMMANDS as $commandInt => $commandValue) {
                if (self::IsCommandAvailable($available, $commandInt)) {
                    $commands[] = $commandValue;
                }
            }
            return $commands;
        }

        public static function IsCommandAvailable(int $available, int $command): bool
        {
            return ($command & $available) == $command;
            //return self::$MediaCommands[]
        }

        /**
         * Entprellt die vom Gerät gemeldete Bitmaske supportedMediaCommands.
         * Einige Receiver (z.B. YouTube Music) melden kurzzeitig bzw. abwechselnd unterschiedliche Werte.
         * Eine Änderung wird erst übernommen, wenn derselbe Wert zweimal in Folge gemeldet wurde.
         * Ist noch kein Wert aktiv (kleiner gleich 0, z.B. nach App-Wechsel), wird sofort übernommen.
         *
         * @param int $current Aktuell übernommene Bitmaske
         * @param int $pending Zuletzt gemeldete, noch nicht übernommene Bitmaske (-1 = keine)
         * @param int $reported Neu gemeldete Bitmaske
         * @return array{0: bool, 1: int} [Übernehmen, neuer Wert für pending]
         */
        public static function Debounce(int $current, int $pending, int $reported): array
        {
            if (($reported == $current) || ($current <= 0) || ($reported == $pending)) {
                return [$reported != $current, -1];
            }
            return [false, $reported];
        }
    }

    class Queue
    {
        public const REPEAT_OFF = 'REPEAT_OFF';
        public const REPEAT_ALL = 'REPEAT_ALL';
        public const REPEAT_ONE = 'REPEAT_SINGLE';
        public const REPEAT_ALL_AND_SHUFFLE = 'REPEAT_ALL_AND_SHUFFLE';
        public const REPEAT_MODES = [
            self::REPEAT_OFF,
            self::REPEAT_ALL,
            self::REPEAT_ONE,
            self::REPEAT_ALL_AND_SHUFFLE
        ];
        /**
         * Werte vom Systemprofil ~Repeat (0 = Aus, 1 = Kontext, 2 = Lied)
         */
        public const REPEAT_TO_INT = [
            self::REPEAT_OFF             => 0,
            self::REPEAT_ALL             => 1,
            self::REPEAT_ALL_AND_SHUFFLE => 1,
            self::REPEAT_ONE             => 2
        ];
        public const INT_TO_REPEAT = [
            0 => self::REPEAT_OFF,
            1 => self::REPEAT_ALL,
            2 => self::REPEAT_ONE
        ];
        public const MEDIA_ITEM_KEYS = [
            'contentUrl'  => '',
            'streamType'  => 'BUFFERED',
            'contentType' => 'video/mp4'
        ];

        /**
         * Inhalt der Variable mit ~Playlist ohne Wiedergabeliste
         */
        public const EMPTY_PLAYLIST = '{"current":-1,"entries":[]}';

        /**
         * Maximale Anzahl Einträge je QUEUE_GET_ITEMS (Empfehlung der Cast-Doku: max. 20)
         */
        public const ITEMS_PER_REQUEST = 20;

        /**
         * Ermittelt den Künstler aus den Metadaten eines Mediums.
         * artist, bei Musik ersatzweise albumArtist,
         * bei GenericMediaMetadata (Typ 0, z.B. Musikvideos bei YouTube Music) ersatzweise subtitle.
         * Bei Filmen/Serien (Typ 1/2) enthält subtitle eine Beschreibung bzw. den Episodentitel und wird nicht genutzt.
         *
         * @param array $metadata media.metadata
         * @return string Künstler oder leerer String
         */
        public static function GetArtist(array $metadata): string
        {
            $artist = $metadata['artist'] ?? $metadata['albumArtist'] ?? '';
            if (($artist === '') && ((int) ($metadata['metadataType'] ?? 0) === 0)) {
                $artist = $metadata['subtitle'] ?? '';
            }
            return is_string($artist) ? $artist : '';
        }

        /**
         * Ermittelt den Titel eines Mediums, ohne Titel in den Metadaten den Dateinamen der Quelle.
         *
         * @param array $media media aus MEDIA_STATUS bzw. einem Queue-Item
         * @return string Titel, Dateiname oder leerer String
         */
        public static function GetTitle(array $media): string
        {
            $title = $media['metadata']['title'] ?? '';
            if (is_string($title) && ($title !== '')) {
                return $title;
            }
            $source = $media['contentUrl'] ?? $media['contentId'] ?? '';
            if (!is_string($source) || ($source === '')) {
                return '';
            }
            $path = parse_url($source, PHP_URL_PATH);
            return is_string($path) ? rawurldecode(basename($path)) : '';
        }

        /**
         * Übernimmt Einträge aus MEDIA_STATUS.items bzw. QUEUE_ITEMS in den Zwischenspeicher.
         *
         * @param array $cache Bisherige Einträge (itemId => [artist, song, duration])
         * @param array $items Queue-Items vom Gerät (mit itemId und media)
         * @return array Aktualisierter Zwischenspeicher
         */
        public static function MergeItems(array $cache, array $items): array
        {
            foreach ($items as $item) {
                if (!is_array($item) || !isset($item['itemId'])) {
                    continue;
                }
                $media = is_array($item['media'] ?? null) ? $item['media'] : [];
                $metadata = is_array($media['metadata'] ?? null) ? $media['metadata'] : [];
                $cache[(int) $item['itemId']] = [
                    'artist'   => self::GetArtist($metadata),
                    'song'     => self::GetTitle($media),
                    // Nicht gemeldete Dauer nicht mit einem bekannten Wert überschreiben
                    'duration' => isset($media['duration']) ? (int) $media['duration'] : ($cache[(int) $item['itemId']]['duration'] ?? 0)
                ];
            }
            return $cache;
        }

        /**
         * Liefert die itemIds aus der Reihenfolge, zu denen noch keine Einträge vorliegen.
         *
         * @param array $itemIds itemIds in Wiedergabereihenfolge
         * @param array $cache Zwischenspeicher (itemId => Eintrag)
         * @return array Fehlende itemIds
         */
        public static function MissingItemIds(array $itemIds, array $cache): array
        {
            return array_values(array_filter($itemIds, fn ($itemId): bool => !isset($cache[(int) $itemId])));
        }

        /**
         * Erzeugt den Inhalt einer Variable mit ~Playlist.
         *
         * @param array $itemIds itemIds in Wiedergabereihenfolge
         * @param array $cache Zwischenspeicher (itemId => Eintrag)
         * @param int $currentItemId itemId der aktuellen Wiedergabe
         * @return string JSON {"current": Index ab 0 (-1 = unbekannt), "entries": [{artist, song, duration}]}
         */
        public static function BuildPlaylist(array $itemIds, array $cache, int $currentItemId): string
        {
            $entries = [];
            foreach ($itemIds as $itemId) {
                $entries[] = $cache[(int) $itemId] ?? ['artist' => '', 'song' => '', 'duration' => 0];
            }
            $current = array_search($currentItemId, array_map('intval', $itemIds), true);
            return json_encode([
                'current' => ($current === false) ? -1 : $current,
                'entries' => $entries
            ]);
        }

        /**
         * Ermittelt die itemId zu einer Auswahl aus der Variable mit ~Playlist.
         *
         * @param array $itemIds itemIds in Wiedergabereihenfolge
         * @param mixed $value Wert der Bedienaktion (JSON mit current bzw. Index)
         * @return int|null itemId oder null bei ungültiger Auswahl
         */
        public static function GetItemIdFromSelection(array $itemIds, mixed $value): ?int
        {
            if (is_string($value)) {
                $selection = json_decode($value, true);
                $index = is_array($selection) ? ($selection['current'] ?? null) : null;
            } else {
                $index = $value;
            }
            if (!is_numeric($index) || !isset($itemIds[(int) $index])) {
                return null;
            }
            return (int) $itemIds[(int) $index];
        }

        /**
         * Ermittelt Wiederholung und Zufallswiedergabe aus einem MEDIA_STATUS.
         * Fehlt ein Wert im Status, wird null geliefert (Variable behält ihren Wert).
         *
         * @param array $status Ein Eintrag aus MEDIA_STATUS.status
         * @return array{0: ?int, 1: ?bool} [Wert für ~Repeat, Wert für ~Shuffle]
         */
        public static function ParseRepeatShuffle(array $status): array
        {
            $repeatMode = $status['repeatMode'] ?? $status['queueData']['repeatMode'] ?? null;
            $repeat = is_string($repeatMode) ? (self::REPEAT_TO_INT[$repeatMode] ?? null) : null;
            $shuffle = null;
            if (isset($status['queueData']['shuffle'])) {
                $shuffle = (bool) $status['queueData']['shuffle'];
            }
            if ($repeatMode === self::REPEAT_ALL_AND_SHUFFLE) {
                $shuffle = true;
            }
            // Spotify meldet den tatsächlichen Zustand nur im eigenen Player-Status (repeat_mode: 0 = Aus, 1 = Kontext, 2 = Lied),
            // repeatMode der Cast-Ebene wird sofort gesetzt, auch wenn Spotify den Befehl erst später oder gar nicht übernimmt
            $playerState = $status['media']['customData']['playerPlaybackState'] ?? null;
            if (is_array($playerState)) {
                if (isset($playerState['repeat_mode']) && isset(self::INT_TO_REPEAT[(int) $playerState['repeat_mode']])) {
                    $repeat = (int) $playerState['repeat_mode'];
                }
                if (isset($playerState['shuffle'])) {
                    $shuffle = (bool) $playerState['shuffle'];
                }
            }
            return [$repeat, $shuffle];
        }

        /**
         * Prüft die Einträge einer Wiedergabeliste und ergänzt fehlende Standardwerte.
         *
         * @param array $items Einträge mit mindestens contentUrl
         * @param callable $mimeResolver Liefert den MIME-Typ zu einer Dateiendung
         * @return array|string Liste der Queue-Items, bei Fehler die (unübersetzte) Fehlermeldung
         */
        public static function BuildItems(array $items, callable $mimeResolver): array|string
        {
            if (count($items) == 0) {
                return 'Queue has no items';
            }
            $payloadItems = [];
            foreach ($items as $item) {
                if (!is_array($item) || !isset($item['contentUrl']) || !is_string($item['contentUrl']) || ($item['contentUrl'] === '')) {
                    return 'Every queue item needs a contentUrl';
                }
                $item['contentType'] = $item['contentType'] ?? $mimeResolver(pathinfo((string) parse_url($item['contentUrl'], PHP_URL_PATH), PATHINFO_EXTENSION));
                $payloadItems[] = [
                    'media' => array_merge(self::MEDIA_ITEM_KEYS, $item)
                ];
            }
            return $payloadItems;
        }
    }
    class PlayerState
    {
        public const IDLE = 'IDLE';
        public const PLAY = 'PLAYING';
        public const PAUSE = 'PAUSED';
        public const BUFFERING = 'BUFFERING';
        public const LOADING = 'LOADING'; // nur in extendedStatus

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

        public const STATE_TO_INT =
            [
                self::IDLE              => 1,
                self::PLAY              => 2,
                self::PAUSE             => 3,
            ];

        public const INT_TO_ACTION =
            [
                0 => \Cast\MediaCommands::PREV,
                1 => \Cast\MediaCommands::STOP,
                2 => \Cast\MediaCommands::PLAY,
                3 => \Cast\MediaCommands::PAUSE,
                4 => \Cast\MediaCommands::NEXT
            ];
    }

    class Payload
    {
        public const IS_STRING = 0;
        public const IS_BINARY = 1;

        public static function MakePayload(string $command, array $payload = []): string
        {
            return json_encode(
                array_merge(Commands::GetType($command), $payload)
            );
        }
    }

    class CastMessage
    {
        private \Chromecast\CCastMessage $Message;

        public function __construct(string|array $data)
        {
            $this->Message = new \Chromecast\CCastMessage();
            if (is_array($data)) {
                // Pflichtfelder des CastV2-Protokolls immer setzen (werden dann auch mit 0 serialisiert)
                $this->Message->setProtocolVersion(0);
                $this->Message->setSourceId('sender-' . (string) $data[0]);
                $this->Message->setReceiverId($data[1]);
                $this->Message->setUrn($data[2]);
                $this->Message->setPayloadType($data[3]);
                $this->Message->setPayload($data[4]);
                //$this->Message = new \Chromecast\CCastMessage($Data);
            } else {
                //$this->Message = new \Chromecast\CCastMessage();
                $this->Message->mergeFromString($data);
            }
        }

        public function GetDebugData(): array
        {
            $payload = $this->Message->getPayload();
            return [
                'SourceId'    => $this->Message->getSourceId(),
                'ReceiverId'  => $this->Message->getReceiverId(),
                'Urn'         => $this->Message->getUrn(),
                'PayloadType' => $this->Message->getPayloadType(),
                'Payload'     => $payload, //($Payload[0] != '{') ? $Payload : json_decode($Payload, true)
                '-------'     => '------------------------------'
            ];
        }

        public function GetUrn(): string
        {
            return $this->Message->getUrn();
        }

        /**
         * Liefert den JSON-Payload als Array.
         *
         * @return array|null Null bei leerem, binärem oder nicht dekodierbarem Payload
         */
        public function GetPayload(): ?array
        {
            $payload = $this->Message->getPayload();
            if (($payload === '') || ($payload[0] != '{')) {
                return null;
            }
            $decoded = json_decode($payload, true);
            return is_array($decoded) ? $decoded : null;
        }

        public function GetSourceId(): string
        {
            return $this->Message->getSourceId();
        }

        public function GetReceiverId(): string
        {
            return $this->Message->getReceiverId();
        }

        public function GetMessage(): string
        {
            $data = $this->Message->serializeToString();
            return pack('N', strlen($data)) . $data;
            //return $Data;
        }
    }
}