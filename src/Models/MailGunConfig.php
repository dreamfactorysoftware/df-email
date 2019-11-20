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
            case 'region_endpoint':
                $schema['type'] = 'picklist';
                $schema['values'] = [
                    ['label' => 'United States', 'name' => 'api.mailgun.net'],
                    ['label' => 'Europe', 'name' => 'api.eu.mailgun.net'],
                ];
                $schema['default'] = 'api.mailgun.net';
                $schema['description'] = 'Select Mailgun service REST API Region Endpoint. 
                According to <a href="https://documentation.mailgun.com/en/latest/api-intro.html#mailgun-regions">Mailgun documentation</a>.';
                break;
        }
    }
}