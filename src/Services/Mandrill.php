<?php

namespace DreamFactory\Core\Email\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use \Illuminate\Support\Arr;

/**
 * Mandrill transport support was removed upstream by Laravel/Symfony and the
 * Mandrill API itself is no longer publicly available for new customers.
 * The class is retained for backward compatibility with stored service-type
 * registrations, but instantiation now throws.
 */
class Mandrill extends BaseService
{
    protected function setTransport(array $config)
    {
        $key = Arr::get($config, 'key');
        $this->transport = static::getTransport($key);
    }

    /**
     * @param string|null $key
     *
     * @throws InternalServerErrorException
     * @return never
     */
    public static function getTransport($key)
    {
        throw new InternalServerErrorException(
            'Mandrill transport is no longer supported. Please switch to a supported email service (SMTP, Mailgun, etc.).'
        );
    }
}
