<?php

namespace DreamFactory\Core\Email\Models;

use DreamFactory\Core\Email\Components\SupportsEmailParameters;
use DreamFactory\Core\Models\BaseServiceConfigNoDbModel;

class LocalEmailConfig extends BaseServiceConfigNoDbModel
{
    use SupportsEmailParameters;

    protected $fillable = [
        'service_id',
        'parameters'
    ];
}