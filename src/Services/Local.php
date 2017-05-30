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
        if (!empty($command = array_get($config, 'command'))) {
            $this->transport = static::getTransport($command);
        } else {
            // old usage of mail config and env
            switch (Config::get('mail.driver')) {
                case 'sendmail':
                    $this->transport = static::getTransport(Config::get('mail.sendmail'));
                    break;
                case 'smtp':
                    $host = Config::get('mail.host');
                    $port = Config::get('mail.port');
                    $encryption = Config::get('mail.encryption');
                    $username = Config::get('mail.username');
                    $password = Config::get('mail.password');
                    $this->transport = Smtp::getTransport($host, $port, $encryption, $username, $password);
                    break;
            }
        }
    }

    /**
     * @param $command
     *
     * @return SendmailTransport
     */
    public static function getTransport($command)
    {
        if (!empty($command)) {
            return SendmailTransport::newInstance();
        }

        return SendmailTransport::newInstance($command);
    }
}