<?php

namespace DreamFactory\Core\Email\Models;

use DreamFactory\Core\Exceptions\BadRequestException;
use DreamFactory\Core\Models\BaseServiceConfigModel;

class BaseEmailServiceConfigModel extends BaseServiceConfigModel
{
    /**
     * @return array|null
     */
    public static function getConfigSchema()
    {
        $schema = parent::getConfigSchema();
        $schema = array_merge($schema, EmailServiceParameterConfig::getConfigSchema());

        return $schema;
    }

    /**
     * {@inheritdoc}
     */
    public static function getConfig($id, $protect = true)
    {
        $config = parent::getConfig($id, $protect);

        /** @var EmailServiceParameterConfig $params */
        $params = EmailServiceParameterConfig::whereServiceId($id)->get();
        $config['parameters'] = (empty($params)) ? [] : $params->toArray();

        return $config;
    }

    /**
     * {@inheritdoc}
     */
    public static function setConfig($id, $config)
    {
        if (isset($config['parameters'])) {
            $params = $config['parameters'];
            if (!is_array($params)) {
                throw new BadRequestException('Web service parameters must be an array.');
            }
            EmailServiceParameterConfig::setConfig($id, $params);
            unset($config['parameters']);
        }

        parent::setConfig($id, $config);
    }
}