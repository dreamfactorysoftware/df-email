<?php

namespace DreamFactory\Core\Email\Services;

use Swift_SendmailTransport as SendmailTransport;
use Config;

class Local extends BaseService
{
    /**
     * {@inheritdoc}
     */
    protected function setTransport($config)
    {
        $command = array_get($config, 'command');
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
