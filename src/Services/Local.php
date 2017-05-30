<?php

namespace DreamFactory\Core\Email\Services;

use Swift_SendmailTransport as SendmailTransport;

class Local extends BaseService
{
    /**
     * {@inheritdoc}
     */
    protected function setTransport($config)
    {
        $this->transport = static::getTransport(array_get($config, 'command'));
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