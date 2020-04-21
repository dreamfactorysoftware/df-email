<?php

namespace DreamFactory\Core\Email\Services;

use DreamFactory\Core\Exceptions\InternalServerErrorException;
use GuzzleHttp\Client;
use Illuminate\Mail\Transport\SparkPostTransport;

class SparkPost extends BaseService
{
    protected function setTransport($config)
    {
        $key = array_get($config, 'key');
        $options = (array)array_get($config, 'options');
        $this->transport = static::getTransport($key, $options);
    }

    /**
     * @param $key
     * @param $options
     *
     * @return \Illuminate\Mail\Transport\SparkPostTransport
     * @throws \DreamFactory\Core\Exceptions\InternalServerErrorException
     */
    public static function getTransport($key, $options = [])
    {
        if (empty($key)) {
            throw new InternalServerErrorException('Missing key for SparkPost service.');
        }

        return new SparkPostTransport(new Client(), $key, $options);
    }
}