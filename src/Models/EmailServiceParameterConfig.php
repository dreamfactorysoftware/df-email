<?php

namespace DreamFactory\Core\Email\Models;

use DreamFactory\Core\Models\BaseServiceConfigModel;

class EmailServiceParameterConfig extends BaseServiceConfigModel
{
    protected $table = 'email_parameters_config';

    protected $primaryKey = 'id';

    protected $fillable = ['service_id', 'name', 'value', 'active'];

    protected $casts = ['id' => 'integer', 'service_id' => 'integer', 'active' => 'boolean'];

    /**
     * @var bool
     */
    public $timestamps = false;

    /**
     * @var bool
     */
    public $incrementing = true;

    /**
     * @var bool
     */
    public static $alwaysNewOnSet = true;
}