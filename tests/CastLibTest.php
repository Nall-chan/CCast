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
