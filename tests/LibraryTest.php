<?php

declare(strict_types=1);

include_once __DIR__ . '/stubs/Validator.php';

class LibraryTest extends TestCaseSymconValidation
{
    public function testValidateLibrary(): void
    {
        $this->validateLibrary(__DIR__ . '/..');
    }
    public function testValidateCastDiscovery(): void
    {
        $this->validateModule(__DIR__ . '/../Chrome Cast Discovery');
    }
    public function testValidateCastDevice(): void
    {
        $this->validateModule(__DIR__ . '/../Chrome Cast');
    }
}
