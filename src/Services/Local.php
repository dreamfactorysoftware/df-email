<?php

namespace DreamFactory\Core\Email\Services;

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
            $disallowedCommands = ['rm', 'sudo', 'sh', 'bash', 'fsockopen', 'exec', 'system', 'popen', 'proc_open', 'passthru', 'curl', 'wget'];

            foreach ($disallowedCommands as $disallowed) {
                if (strpos($command, $disallowed) !== false) {
                    throw new InternalServerErrorException('Command "' . $command . '" is not allowed.');
                }
            }
            $this->transport = static::getTransport($command);
        }
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

        return new SendmailTransport($command);
    }
}
