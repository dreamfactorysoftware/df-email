<?php

namespace DreamFactory\Core\Email\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;

class LocalEmailConfig extends BaseServiceConfigModel
{
    protected $table = 'local_email_config';

    protected $fillable = [
        'service_id',
        'from_name',
        'from_email',
        'reply_to_name',
        'reply_to_email'
    ];

    protected $casts = [
        'service_id' => 'integer'
    ];

    // Add this property to define which fields should be treated as dates
    protected $dates = [];
}