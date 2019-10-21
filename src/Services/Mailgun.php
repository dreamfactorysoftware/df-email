<?php

namespace DreamFactory\Core\Email\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use GuzzleHttp\Client;
use Illuminate\Mail\Transport\MailgunTransport;

class MailGun extends BaseService
{
    protected function setTransport($config)
    {
        $domain = array_get($config, 'domain');
        $key = array_get($config, 'key');
        $regionEndpoint = array_get($config, 'region_endpoint');

        $this->transport = static::getTransport($domain, $key, $regionEndpoint);
    }

    /**
     * @param $domain
     * @param $key
     * @param $regionEndpoint
     * @return \Illuminate\Mail\Transport\MailgunTransport
     * @throws InternalServerErrorException
     */
    public static function getTransport($domain, $key, $regionEndpoint)
    {
        if (empty($domain) || empty($key)) {
            throw new InternalServerErrorException('Missing one or more configuration for MailGun service.');
        }

        return new MailgunTransport(new Client(), $key, $domain, $regionEndpoint);
    }
}