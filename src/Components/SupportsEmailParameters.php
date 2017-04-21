<?php

namespace DreamFactory\Core\Email\Components;

use DreamFactory\Core\Email\Models\EmailServiceParameterConfig;
use DreamFactory\Core\Exceptions\BadRequestException;

trait SupportsEmailParameters
{
    /**
     * @return array|null
     */
    public static function getConfigSchema()
    {
        $schema = parent::getConfigSchema();
        $schema[] = [
            'name'        => 'parameters',
            'label'       => 'Parameters',
            'description' => 'Supply additional parameters to be replace in the email body.',
            'type'        => 'array',
            'required'    => false,
            'allow_null'  => true,
            'items'       => EmailServiceParameterConfig::getConfigSchema(),
        ];

        return $schema;
    }

    /**
     * {@inheritdoc}
     */
    public static function getConfig($id, $local_config = null, $protect = true)
    {
        $config = parent::getConfig($id, $local_config, $protect);

        /** @var EmailServiceParameterConfig $params */
        $params = EmailServiceParameterConfig::whereServiceId($id)->get();
        $config['parameters'] = $params->toArray();

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public static function setConfig($id, $config, $local_config = null)
    {
        if (isset($config['parameters'])) {
            $params = $config['parameters'];
            if (!is_array($params)) {
                throw new BadRequestException('Web service parameters must be an array.');
            }
            EmailServiceParameterConfig::whereServiceId($id)->delete();
            foreach ($params as $param) {
                EmailServiceParameterConfig::setConfig($id, $param, $local_config);
            }
            unset($config['parameters']);
        }

        return parent::setConfig($id, $config, $local_config);
    }

    /**
     * {@inheritdoc}
     */
    public static function storeConfig($id, $config)
    {
        if (isset($config['parameters'])) {
            $params = (array)$config['parameters'];
            EmailServiceParameterConfig::whereServiceId($id)->delete();
            foreach ($params as $param) {
                EmailServiceParameterConfig::storeConfig($id, $param);
            }
            unset($config['parameters']);
        }

        return parent::storeConfig($id, $config);
    }
}