<?php

namespace DreamFactory\Core\Email\Models;

use DreamFactory\Core\Email\Components\SupportsEmailParameters;
use DreamFactory\Core\Models\BaseServiceConfigModel;

class LocalEmailConfig extends BaseServiceConfigModel
{
    use SupportsEmailParameters;

    protected $table = 'local_email_config';

    protected $fillable = [
        'service_id',
        'command',
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

    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'command':
                $schema['label'] = 'Local Command';
                $schema['description'] = 'Local command to be executed to send mail.';
                break;
        }
    }
}
