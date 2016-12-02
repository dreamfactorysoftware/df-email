<?php

namespace DreamFactory\Core\Email\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use GuzzleHttp\Client;
use Illuminate\Mail\Transport\MandrillTransport;

class Mandrill extends BaseService
{
    protected function setTransport($config)
    {
        $key = array_get($config, 'key');
        $this->transport = static::getTransport($key);
    }

    /**
     * @param $key
     *
     * @return \Illuminate\Mail\Transport\MandrillTransport
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public static function getTransport($key)
    {
        if (empty($key)) {
            throw new InternalServerErrorException('Missing key for Mandrill service.');
        }

        return new MandrillTransport(new Client(), $key);
    }
}