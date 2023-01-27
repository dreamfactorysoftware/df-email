<?php

namespace DreamFactory\Core\Email\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport as SmtpTransport;
use \Illuminate\Support\Arr;

class Smtp extends BaseService
{
    protected function setTransport(array $config)
    {
        $host = Arr::get($config, 'host');
        $port = Arr::get($config, 'port');
        $encryption = Arr::get($config, 'encryption');
        $username = Arr::get($config, 'username');
        $password = Arr::get($config, 'password');

        $this->transport = static::getTransport($host, $port, $encryption, $username, $password);
    }

    /**
     * @param $host
     * @param $port
     * @param $encryption
     * @param $username
     * @param $password
     *
     * @throws InternalServerErrorException
     */
    public static function getTransport($host, $port, $encryption, $username, $password): SmtpTransport
    {
        if (empty($host)) {
            throw new InternalServerErrorException("Missing SMTP host. Check service configuration.");
        }
        if (empty($port)) {
            throw new InternalServerErrorException("Missing SMTP port. Check service configuration.");
        }
        $transport = new SmtpTransport($host, $port, boolval($encryption));

        if (!empty($username) && !empty($password)) {
            $transport->setUsername($username);
            $transport->setPassword($password);
        }

        return $transport;
    }
}