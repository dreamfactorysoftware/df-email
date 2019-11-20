<?php

namespace DreamFactory\Core\Email\Models;

use DreamFactory\Core\Email\Components\SupportsEmailParameters;
use DreamFactory\Core\Models\BaseServiceConfigModel;

class CloudEmailConfig extends BaseServiceConfigModel
{
    use SupportsEmailParameters;

    protected $table = 'cloud_email_config';

    protected $fillable = [
        'service_id',
        'domain',
        'key',
        'region_endpoint',
        'parameters'
    ];

    protected $encrypted = ['key'];
}