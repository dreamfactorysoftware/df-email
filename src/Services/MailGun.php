<?php

namespace DreamFactory\Core\Email\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use Symfony\Component\Mailer\Bridge\Mailgun\Transport\MailgunApiTransport as MailgunTransport;
use \Illuminate\Support\Arr;

class MailGun extends BaseService
{
    protected function setTransport(array $config)
    {
        $domain = Arr::get($config, 'domain');
        $key = Arr::get($config, 'key');
        $regionEndpoint = Arr::get($config, 'region_endpoint');

        $this->transport = static::getTransport($domain, $key, $regionEndpoint);
    }

    /**
     * @param $domain
     * @param $key
     * @param $regionEndpoint
     * @return MailgunTransport
     * @throws InternalServerErrorException
     */
    public static function getTransport($domain, $key, $regionEndpoint)
    {
        if (empty($domain) || empty($key)) {
            throw new InternalServerErrorException('Missing one or more configuration for MailGun service.');
        }

        return new MailgunTransport($key, $domain, $regionEndpoint);
    }
}