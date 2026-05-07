<?php

namespace DreamFactory\Core\Email\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use \Illuminate\Support\Arr;

/**
 * SparkPost transport support was removed when Laravel migrated from
 * SwiftMailer to Symfony Mailer in Laravel 7+. There is no first-party
 * Symfony bridge for SparkPost. The class is retained for backward
 * compatibility with stored service-type registrations, but instantiation
 * now throws.
 */
class SparkPost extends BaseService
{
    protected function setTransport(array $config)
    {
        $key = Arr::get($config, 'key');
        $options = (array)Arr::get($config, 'options');
        $this->transport = static::getTransport($key, $options);
    }

    /**
     * @param string|null $key
     * @param array       $options
     *
     * @throws InternalServerErrorException
     * @return never
     */
    public static function getTransport($key, $options = [])
    {
        throw new InternalServerErrorException(
            'SparkPost transport is no longer supported. Please switch to a supported email service (SMTP, Mailgun, etc.).'
        );
    }
}
