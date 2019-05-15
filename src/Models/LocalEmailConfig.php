<?php

namespace DreamFactory\Core\Email\Models;

use DreamFactory\Core\Email\Components\SupportsEmailParameters;
use DreamFactory\Core\Models\BaseServiceConfigNoDbModel;

class LocalEmailConfig extends BaseServiceConfigNoDbModel
{
    use SupportsEmailParameters;

    protected $fillable = [
        'service_id',
        'command',
        'parameters'
    ];

    /**
     * {@inheritdoc}
     */
    public static function getSchema()
    {

        if(!empty(env('SENDMAIL_DEFAULT_COMMAND'))) {
            $defaultCommand = env('SENDMAIL_DEFAULT_COMMAND');
        } else {
            $defaultCommand = '/usr/sbin/sendmail -bs';
        }

        return [
            'command' => [
                'name'        => 'command',
                'label'       => 'Local Command',
                'type'        => 'string',
                'allow_null'  => false,
                'default'     => $defaultCommand,
                'description' => 'Local command to be executed to send mail.',
            ]
        ];
    }
}