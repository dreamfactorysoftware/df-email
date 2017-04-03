<?php

namespace DreamFactory\Core\Email\Models;


class MailGunConfig extends CloudEmailConfig
{
    protected $rules = [
        'domain' => 'required',
        'key'    => 'required'
    ];

    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'domain':
                $schema['label'] = 'Mailgun Domain';
                $schema['description'] = 'Your Mailgun domain name.';
                break;
            case 'key':
                $schema['label'] = 'API Key';
                $schema['description'] = 'Mailgun service API Key.';
                break;
        }
    }
}