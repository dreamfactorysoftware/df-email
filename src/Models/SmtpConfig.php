<?php

namespace DreamFactory\Core\Email\Models;

use DreamFactory\Core\Email\Components\SupportsEmailParameters;
use DreamFactory\Core\Models\BaseServiceConfigModel;

class SmtpConfig extends BaseServiceConfigModel
{
    use SupportsEmailParameters;

    protected $table = 'smtp_config';

    protected $fillable = [
        'service_id',
        'host',
        'port',
        'encryption',
        'username',
        'password',
        'parameters'
    ];

    protected $casts = [
        'service_id' => 'integer',
        'port'       => 'integer',
        'parameters' => 'array'
    ];

    protected $encrypted = ['username', 'password'];

    protected $rules = ['host' => 'required'];

    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'host':
                $schema['description'] = 'SMTP Host.';
                break;
            case 'port':
                $schema['description'] = 'SMTP Port (default: 587).';
                break;
            case 'encryption':
                $schema['description'] = 'SMTP Encryption: tls/ssl.';
                break;
            case 'username':
                $schema['description'] = 'SMTP Username.';
                break;
            case 'password':
                $schema['description'] = 'SMTP Password.';
                break;
        }
    }
}