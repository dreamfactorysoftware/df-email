<?php

namespace DreamFactory\Core\Email\Models;

class CloudEmailConfig extends BaseEmailServiceConfigModel
{
    protected $table = 'cloud_email_config';

    protected $fillable = [
        'service_id',
        'domain',
        'key',
        'parameters'
    ];

    protected $encrypted = ['key'];
}