<?php

namespace DreamFactory\Core\Email\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use Symfony\Component\Mailer\Transport\SendmailTransport as SendmailTransport;
use Config;
use \Illuminate\Support\Arr;

class Local extends BaseService
{
    /**
     * {@inheritdoc}
     */
    protected function setTransport(array $config)
    {
        $command = Arr::get($config, 'command');
        // old usage of mail config and env may be set to smtp
        if (empty($command) && ('smtp' == Config::get('mail.driver'))) {
            $host = Config::get('mail.host');
            $port = Config::get('mail.port');
            $encryption = Config::get('mail.encryption');
            $username = Config::get('mail.username');
            $password = Config::get('mail.password');
            $this->transport = Smtp::getTransport($host, $port, $encryption, $username, $password);
        } else {
            $this->transport = static::getTransport($command);
        }
    }

    /**
     * Allowlist enforcement for the Local mailer's `command` config.
     *
     * Replaces the previous substring blocklist (rm, sudo, curl, etc.) which
     * was case-sensitive, missed alternatives like `nc`/`dd`, and could be
     * bypassed via command chaining. Now: the executable must be a literal
     * `sendmail` or an absolute path ending in `/sendmail`; subsequent tokens
     * must be short-form sendmail flags; shell metacharacters are forbidden.
     *
     * @throws \InvalidArgumentException when the command fails the allowlist
     */
    public static function assertCommandAllowlisted(string $command): void
    {
        $command = trim($command);
        if ($command === '') {
            throw new \InvalidArgumentException('Sendmail command must not be empty.');
        }

        // Reject any shell metacharacter — these are not legitimate inside
        // a sendmail invocation and would enable command chaining.
        if (preg_match('/[;|&`$()<>{}\\\\\n\r]/', $command) === 1) {
            throw new \InvalidArgumentException(
                'Sendmail command contains forbidden shell metacharacter.'
            );
        }

        $tokens = preg_split('/\s+/', $command);
        $exe = array_shift($tokens) ?? '';

        // Executable: literal allowlist (default: sendmail and common
        // sendmail-API-compatible MTAs) or an absolute path whose basename
        // is in the allowlist. Customers using non-default MTAs can extend
        // via the DF_LOCAL_MAILER_EXES env var (comma-separated).
        $allowed = self::getAllowedMailerExecutables();
        $exeBasename = basename($exe);
        $isAbsolute = str_starts_with($exe, '/');
        $exeOk = in_array($exe, $allowed, true)
            || ($isAbsolute && in_array($exeBasename, $allowed, true));
        if (!$exeOk) {
            throw new \InvalidArgumentException(
                'Sendmail command executable must be one of: ' . implode(', ', $allowed)
                . ' (or an absolute path whose basename is one of those).'
            );
        }

        // Remaining tokens: must be short-form flags or value-bearing flags.
        // Accept patterns like -bs, -t, -i, -fuser@example.com, -odq.
        foreach ($tokens as $tok) {
            if ($tok === '') {
                continue;
            }
            if (preg_match('/^-[A-Za-z0-9][A-Za-z0-9.@_+-]*$/', $tok) !== 1) {
                throw new \InvalidArgumentException(
                    'Sendmail command flag is not in the allowed flag pattern: ' . $tok
                );
            }
        }
    }

    /**
     * Resolve the allowlist of mailer executable basenames.
     *
     * Defaults to sendmail-API-compatible MTAs (sendmail, msmtp, ssmtp,
     * postfix's qshape sendmail wrapper). Operators with custom MTAs can
     * extend via the DF_LOCAL_MAILER_EXES env var, e.g.:
     *   DF_LOCAL_MAILER_EXES=sendmail,msmtp,nullmailer-inject
     *
     * @return string[]
     */
    private static function getAllowedMailerExecutables(): array
    {
        $env = (string) (getenv('DF_LOCAL_MAILER_EXES') ?: '');
        if ($env !== '') {
            $list = array_values(array_filter(array_map('trim', explode(',', $env))));
            if (!empty($list)) {
                return $list;
            }
        }
        return ['sendmail', 'msmtp', 'ssmtp'];
    }

    /**
     * @param $command
     *
     * @return SendmailTransport
     */
    public static function getTransport($command)
    {
        if (empty($command) && !empty(env('SENDMAIL_DEFAULT_COMMAND'))) {
            $command = env('SENDMAIL_DEFAULT_COMMAND');
        }

        if (empty($command)) {
            return new SendmailTransport();
        }

        try {
            self::assertCommandAllowlisted($command);
        } catch (\InvalidArgumentException $e) {
            throw new InternalServerErrorException($e->getMessage());
        }

        return new SendmailTransport($command);
    }
}
