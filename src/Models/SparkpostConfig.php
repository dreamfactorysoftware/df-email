<?php

namespace DreamFactory\Core\Email\Models;


class SparkpostConfig extends CloudEmailConfig
{
    protected $fillable = [
        'service_id',
        'key',
        'parameters'
    ];

    protected $rules = ['key' => 'required'];

    /**
     * {@inheritdoc}
     */
    public static function getConfigSchema()
    {
        $schema = parent::getConfigSchema();
        $out = [];
        foreach ($schema as $key => $field) {
            if ($field['name'] === 'key' || $field['name'] === 'parameters') {
                $out[] = $schema[$key];
            }
        }

        return $out;
    }

    /**
     * @param array $schema
     */
    protected static function prepareConfigSchemaField(array &$schema)
    {
        parent::prepareConfigSchemaField($schema);

        switch ($schema['name']) {
            case 'key':
                $schema['label'] = 'API Key';
                $schema['description'] = 'A Sparkpost service API Key.';
                break;
        }
    }
}