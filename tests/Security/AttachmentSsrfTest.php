<?php

namespace DreamFactory\Core\Email\Tests\Security;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\System\Components\SsrfValidator;
use PHPUnit\Framework\TestCase;

/**
 * Security: BaseService::getUrlAttachment() must validate import URLs through
 * SsrfValidator before fetching them.
 *
 * The April 2026 audit (df-email P1) found that
 * `BaseService::getUrlAttachment()` calls `FileUtilities::importUrlFileToTemp($fileURL)`
 * with NO scheme allowlist and NO private-IP block. The same vulnerability
 * pattern was fixed in df-system (Resources/App.php, Import.php, Package.php)
 * by routing every import URL through `SsrfValidator::validateExternalUrl()`,
 * but that fix was not propagated to df-email.
 *
 * After the fix, df-email passes any caller-supplied URL through the same
 * validator before any network call. AWS metadata, RFC 1918 ranges,
 * loopback, and non-http(s) schemes are rejected at the validator boundary.
 */
class AttachmentSsrfTest extends TestCase
{
    private string $sourcePath;
    private string $contents;

    protected function setUp(): void
    {
        $this->sourcePath = __DIR__ . '/../../src/Services/BaseService.php';
        $this->assertFileExists($this->sourcePath);
        $this->contents = file_get_contents($this->sourcePath);
    }

    public function testSourceCallsSsrfValidator(): void
    {
        $this->assertMatchesRegularExpression(
            '/SsrfValidator::validateExternalUrl\s*\(/',
            $this->contents,
            'BaseService::getUrlAttachment() must call SsrfValidator::validateExternalUrl() '
            . 'on each caller-supplied URL before passing it to FileUtilities::importUrlFileToTemp().'
        );
    }

    public function testValidatorIsCalledBeforeFetch(): void
    {
        $validatorPos = strpos($this->contents, 'SsrfValidator::validateExternalUrl');
        $fetchPos = strpos($this->contents, 'importUrlFileToTemp(');

        $this->assertNotFalse($validatorPos,
            'SsrfValidator::validateExternalUrl call must appear in source');
        $this->assertNotFalse($fetchPos,
            'importUrlFileToTemp call must appear in source');
        $this->assertLessThan(
            $fetchPos,
            $validatorPos,
            'SsrfValidator::validateExternalUrl must be called BEFORE importUrlFileToTemp '
            . '(otherwise the network request fires before validation).'
        );
    }

    public function testValidatorRejectsAwsMetadataUrl(): void
    {
        $this->expectException(BadRequestException::class);
        SsrfValidator::validateExternalUrl('http://169.254.169.254/latest/meta-data/');
    }

    public function testValidatorRejectsLoopbackUrl(): void
    {
        $this->expectException(BadRequestException::class);
        SsrfValidator::validateExternalUrl('http://127.0.0.1:8080/');
    }

    public function testValidatorRejectsFileScheme(): void
    {
        $this->expectException(BadRequestException::class);
        SsrfValidator::validateExternalUrl('file:///etc/passwd');
    }
}
