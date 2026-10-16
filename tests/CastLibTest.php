<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../libs/Cast.php';

/**
 * Tests der Symcon-unabhängigen Hilfsklassen aus libs/Cast.php
 */
class CastLibTest extends TestCase
{
    public function testConvertSecondsShort(): void
    {
        $this->assertSame('01:05', \Cast\Device\TimeConvert::ConvertSeconds(65));
    }

    public function testConvertSecondsWithFraction(): void
    {
        // Nachkommastellen dürfen keine Deprecation (float modulo) auslösen
        $this->assertSame('02:03', \Cast\Device\TimeConvert::ConvertSeconds(123.789));
    }

    public function testConvertSecondsLong(): void
    {
        $this->assertSame('01:01:01', \Cast\Device\TimeConvert::ConvertSeconds(3661));
    }

    public function testMakePayload(): void
    {
        $payload = json_decode(\Cast\Payload::MakePayload(\Cast\Commands::GET_STATUS, ['requestId' => 5]), true);
        $this->assertSame(['type' => 'GET_STATUS', 'requestId' => 5], $payload);
    }

    public function testCastMessageRoundTrip(): void
    {
        $payload = \Cast\Payload::MakePayload(\Cast\Commands::PING);
        $message = new \Cast\CastMessage([12345, 'receiver-0', \Cast\Urn::HEARTBEAT_NAMESPACE, 0, $payload]);
        $raw = $message->GetMessage();
        $length = unpack('N', substr($raw, 0, 4))[1];
        $this->assertSame(strlen($raw) - 4, $length);

        $decoded = new \Cast\CastMessage(substr($raw, 4));
        $this->assertSame('sender-12345', $decoded->GetSourceId());
        $this->assertSame('receiver-0', $decoded->GetReceiverId());
        $this->assertSame(\Cast\Urn::HEARTBEAT_NAMESPACE, $decoded->GetUrn());
        $this->assertSame(['type' => 'PING'], $decoded->GetPayload());
    }

    public function testGetPayloadReturnsNullForNonJson(): void
    {
        $message = new \Cast\CastMessage([1, 'receiver-0', \Cast\Urn::HEARTBEAT_NAMESPACE, 0, '']);
        $this->assertNull($message->GetPayload());
        $message = new \Cast\CastMessage([1, 'receiver-0', \Cast\Urn::HEARTBEAT_NAMESPACE, 0, 'no json']);
        $this->assertNull($message->GetPayload());
    }

    public function testDebounceAppliesImmediatelyWithoutActiveValue(): void
    {
        $this->assertSame([true, -1], \Cast\MediaCommands::Debounce(0, -1, 274511));
        $this->assertSame([true, -1], \Cast\MediaCommands::Debounce(-1, -1, 274511));
    }

    public function testDebounceWaitsForConfirmation(): void
    {
        $this->assertSame([false, 262147], \Cast\MediaCommands::Debounce(274511, -1, 262147));
        $this->assertSame([true, -1], \Cast\MediaCommands::Debounce(274511, 262147, 262147));
    }

    public function testDebounceIgnoresAlternatingValues(): void
    {
        // Abwechselnd gemeldete Werte (alte/neue Liste) dürfen nie übernommen werden
        [$apply, $pending] = \Cast\MediaCommands::Debounce(274511, -1, 274383);
        $this->assertFalse($apply);
        [$apply, $pending] = \Cast\MediaCommands::Debounce(274511, $pending, 274511);
        $this->assertFalse($apply);
        $this->assertSame(-1, $pending);
        [$apply, $pending] = \Cast\MediaCommands::Debounce(274511, $pending, 274383);
        $this->assertFalse($apply);
    }

    public function testQueueCommandsEnableQueueControls(): void
    {
        $commands = \Cast\MediaCommands::ListAvailableCommands(274447 | \Cast\MediaCommands::QUEUE_COMMANDS);
        foreach ([\Cast\MediaCommands::NEXT, \Cast\MediaCommands::SHUFFLE, \Cast\MediaCommands::REPEAT_ALL, \Cast\MediaCommands::REPEAT_ONE] as $command) {
            $this->assertContains($command, $commands);
        }
    }

    public function testParseRepeatShuffle(): void
    {
        $this->assertSame([2, null], \Cast\Queue::ParseRepeatShuffle(['repeatMode' => 'REPEAT_SINGLE']));
        $this->assertSame([1, false], \Cast\Queue::ParseRepeatShuffle(['queueData' => ['repeatMode' => 'REPEAT_ALL', 'shuffle' => false]]));
        $this->assertSame([1, true], \Cast\Queue::ParseRepeatShuffle(['repeatMode' => 'REPEAT_ALL_AND_SHUFFLE']));
        $this->assertSame([0, null], \Cast\Queue::ParseRepeatShuffle(['repeatMode' => 'REPEAT_OFF']));
        // Spotify: eigener Player-Status hat Vorrang vor repeatMode der Cast-Ebene (Mitschnitt Nest Hub 2026-10-16)
        $spotify = ['repeatMode' => 'REPEAT_ALL', 'media' => ['customData' => ['playerPlaybackState' => ['shuffle' => true, 'repeat_mode' => 2, 'shuffle_mode' => 1]]]];
        $this->assertSame([2, true], \Cast\Queue::ParseRepeatShuffle($spotify));
        $spotify['media']['customData']['playerPlaybackState'] = ['shuffle' => false, 'repeat_mode' => 0];
        $this->assertSame([0, false], \Cast\Queue::ParseRepeatShuffle($spotify));
        // Fehlende bzw. unbekannte Werte liefern null
        $this->assertSame([null, null], \Cast\Queue::ParseRepeatShuffle([]));
        $this->assertSame([null, null], \Cast\Queue::ParseRepeatShuffle(['repeatMode' => 'UNKNOWN']));
    }

    public function testRepeatMappingMatchesProfile(): void
    {
        foreach (\Cast\Queue::INT_TO_REPEAT as $value => $mode) {
            $this->assertSame($value, \Cast\Queue::REPEAT_TO_INT[$mode]);
        }
    }

    public function testBuildItems(): void
    {
        $resolver = fn (string $extension): string => $extension === 'mp3' ? 'audio/mpeg' : 'text/plain';
        $items = \Cast\Queue::BuildItems([['contentUrl' => 'http://host/a.mp3'], ['contentUrl' => 'http://host/b', 'contentType' => 'video/mp4', 'streamType' => 'LIVE']], $resolver);
        $this->assertIsArray($items);
        $this->assertSame(['contentUrl' => 'http://host/a.mp3', 'streamType' => 'BUFFERED', 'contentType' => 'audio/mpeg'], $items[0]['media']);
        $this->assertSame('LIVE', $items[1]['media']['streamType']);
        $this->assertSame('video/mp4', $items[1]['media']['contentType']);
    }

    public function testBuildItemsRejectsInvalidItems(): void
    {
        $resolver = fn (string $extension): string => 'text/plain';
        $this->assertSame('Queue has no items', \Cast\Queue::BuildItems([], $resolver));
        $this->assertSame('Every queue item needs a contentUrl', \Cast\Queue::BuildItems([['title' => 'x']], $resolver));
        $this->assertSame('Every queue item needs a contentUrl', \Cast\Queue::BuildItems(['http://host/a.mp3'], $resolver));
        $this->assertSame('Every queue item needs a contentUrl', \Cast\Queue::BuildItems([['contentUrl' => '']], $resolver));
    }

    public function testGetArtist(): void
    {
        $this->assertSame('A', \Cast\Queue::GetArtist(['metadataType' => 3, 'artist' => 'A', 'albumArtist' => 'B']));
        $this->assertSame('B', \Cast\Queue::GetArtist(['metadataType' => 3, 'albumArtist' => 'B']));
        $this->assertSame('S', \Cast\Queue::GetArtist(['metadataType' => 0, 'subtitle' => 'S']));
        // Bei Filmen ist subtitle eine Beschreibung
        $this->assertSame('', \Cast\Queue::GetArtist(['metadataType' => 1, 'subtitle' => 'S']));
    }

    public function testMergeItemsAndBuildPlaylist(): void
    {
        // Ausschnitt aus einem MEDIA_STATUS des Default Media Receivers (Nest Hub, 2026-10-16)
        $items = [
            ['media' => ['contentUrl' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-1.mp3', 'metadata' => ['albumName' => 'テスト Album', 'artist' => 'Künstler ß', 'metadataType' => 3, 'title' => 'Prüfung Büro Ä'], 'duration' => 372.715083], 'itemId' => 3, 'orderId' => 0],
            ['media' => ['contentUrl' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-2.mp3', 'metadata' => ['artist' => 'Öl', 'metadataType' => 3, 'title' => 'テスト Zwei']], 'itemId' => 4, 'orderId' => 1]
        ];
        $cache = \Cast\Queue::MergeItems([], $items);
        $this->assertSame(['artist' => 'Künstler ß', 'song' => 'Prüfung Büro Ä', 'duration' => 372], $cache[3]);
        $this->assertSame(0, $cache[4]['duration']);
        $this->assertSame([5], \Cast\Queue::MissingItemIds([3, 4, 5], $cache));

        // Ohne Titel wird der Dateiname angezeigt, eine fehlende Dauer überschreibt keine bekannte
        $cache[4]['duration'] = 100;
        $cache = \Cast\Queue::MergeItems($cache, [['itemId' => 5, 'media' => ['contentUrl' => 'http://host/dir/Song-3.mp3']], ['itemId' => 4, 'media' => ['metadata' => ['title' => 'テスト Zwei']]]]);
        $this->assertSame('Song-3.mp3', $cache[5]['song']);
        $this->assertSame(100, $cache[4]['duration']);

        $playlist = json_decode(\Cast\Queue::BuildPlaylist([5, 3, 4], $cache, 3), true);
        $this->assertSame(1, $playlist['current']);
        $this->assertSame(['Song-3.mp3', 'Prüfung Büro Ä', 'テスト Zwei'], array_column($playlist['entries'], 'song'));
        $this->assertSame(-1, json_decode(\Cast\Queue::BuildPlaylist([5, 3, 4], $cache, 99), true)['current']);
        $this->assertSame(['current' => -1, 'entries' => []], json_decode(\Cast\Queue::EMPTY_PLAYLIST, true));
    }

    public function testGetTitle(): void
    {
        $this->assertSame('Titel', \Cast\Queue::GetTitle(['contentUrl' => 'http://host/a.mp3', 'metadata' => ['title' => 'Titel']]));
        $this->assertSame('SoundHelix-Song-4.mp3', \Cast\Queue::GetTitle(['contentUrl' => 'https://www.soundhelix.com/examples/mp3/SoundHelix-Song-4.mp3']));
        // Kodierte Zeichen werden lesbar dargestellt, Query-Parameter entfallen
        $this->assertSame('Alarm Büro.mp3', \Cast\Queue::GetTitle(['contentId' => 'http://host/user/Alarm%20B%C3%BCro.mp3?x=1', 'metadata' => ['title' => '']]));
        $this->assertSame('', \Cast\Queue::GetTitle([]));
    }

    public function testGetItemIdFromSelection(): void
    {
        $this->assertSame(4, \Cast\Queue::GetItemIdFromSelection([3, 4, 5], '{"current":1,"entries":[]}'));
        $this->assertSame(5, \Cast\Queue::GetItemIdFromSelection([3, 4, 5], 2));
        $this->assertNull(\Cast\Queue::GetItemIdFromSelection([3, 4, 5], '{"current":3}'));
        $this->assertNull(\Cast\Queue::GetItemIdFromSelection([3, 4, 5], 'kein json'));
        $this->assertNull(\Cast\Queue::GetItemIdFromSelection([], 0));
    }

    public function testListAvailableCommands(): void
    {
        $commands = \Cast\MediaCommands::ListAvailableCommands(1 | 2 | 64);
        $this->assertSame([\Cast\MediaCommands::PAUSE, \Cast\MediaCommands::SEEK, \Cast\MediaCommands::NEXT], $commands);
        $this->assertSame([], \Cast\MediaCommands::ListAvailableCommands(0));
    }

    public function testAppProfileAssociations(): void
    {
        $associations = \Cast\Apps::GetAllAppsAsProfileAssociation();
        $this->assertCount(count(\Cast\Apps::APPS), $associations);
        $this->assertContains([\Cast\Apps::YOUTUBE, 'YouTube', '', -1], $associations);
    }

    public function testAppProfileAssociationsWithKnownApps(): void
    {
        // gelernte Apps werden ergänzt, eingebaute Namen nicht überschrieben
        $associations = \Cast\Apps::GetAllAppsAsProfileAssociation(['ABCD1234' => 'Test App', \Cast\Apps::YOUTUBE => 'Fremd']);
        $this->assertCount(count(\Cast\Apps::APPS) + 1, $associations);
        $this->assertContains(['ABCD1234', 'Test App', '', -1], $associations);
        $this->assertContains([\Cast\Apps::YOUTUBE, 'YouTube', '', -1], $associations);
    }
}
