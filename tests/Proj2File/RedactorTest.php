<?php

declare(strict_types=1);

namespace Foreline\Tests\Proj2File;

use Foreline\Proj2File\Redactor;
use PHPUnit\Framework\TestCase;

class RedactorTest extends TestCase
{
    private Redactor $redactor;

    protected function setUp(): void
    {
        $this->redactor = new Redactor();
    }

    public function testPlainTextUnchanged(): void
    {
        $input = 'This is normal text with no secrets.';
        $this->assertSame($input, $this->redactor->redact($input));
        $this->assertSame(0, $this->redactor->getRedactionCount());
    }

    public function testEnvSecretRedacted(): void
    {
        $input = "DB_PASSWORD=super_secret_value\n";
        $result = $this->redactor->redact($input);

        $this->assertStringContainsString('DB_PASSWORD', $result);
        $this->assertStringNotContainsString('super_secret_value', $result);
        $this->assertStringContainsString('***REDACTED***', $result);
    }

    public function testGitHubTokenRedacted(): void
    {
        $input = 'token: ghp_ABCDEFabcdef1234567890abcdef12345678';
        $result = $this->redactor->redact($input);

        $this->assertStringContainsString('***REDACTED***', $result);
        $this->assertStringNotContainsString('ghp_ABCDEF', $result);
    }

    public function testAwsKeyRedacted(): void
    {
        $input = 'aws_key=AKIAIOSFODNN7EXAMPLE';
        $result = $this->redactor->redact($input);

        $this->assertStringContainsString('***REDACTED***', $result);
    }

    public function testEmailRedacted(): void
    {
        $input = 'Contact us at admin@example.com for help.';
        $result = $this->redactor->redact($input);

        $this->assertStringContainsString('***REDACTED***', $result);
        $this->assertStringNotContainsString('admin@example.com', $result);
    }

    public function testRedactionCountAccumulates(): void
    {
        $this->redactor->redact("API_KEY=abc123\n");
        $first = $this->redactor->getRedactionCount();

        $this->redactor->redact("SECRET_TOKEN=xyz789\n");
        $this->assertGreaterThan($first, $this->redactor->getRedactionCount());
    }

    public function testResetCount(): void
    {
        $this->redactor->redact("API_KEY=abc123\n");
        $this->assertGreaterThan(0, $this->redactor->getRedactionCount());

        $this->redactor->resetCount();
        $this->assertSame(0, $this->redactor->getRedactionCount());
    }

    public function testPrivateKeyRedacted(): void
    {
        $input = "-----BEGIN RSA PRIVATE KEY-----\nMIIBogIBAAJ...\n-----END RSA PRIVATE KEY-----";
        $result = $this->redactor->redact($input);

        $this->assertStringContainsString('***REDACTED***', $result);
        $this->assertStringNotContainsString('MIIBogIBAAJ', $result);
    }
}
