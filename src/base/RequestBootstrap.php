<?php

namespace wzzwx\yii2common\base;

use yii\base\BootstrapInterface;
use wzzwx\yii2common\helpers\StringHelper;

class RequestBootstrap implements BootstrapInterface
{
    /**
     * @inheritdoc
     */
    public function bootstrap($app)
    {
        $ip = StringHelper::getRealIp();
        $_SERVER['REMOTE_ADDR'] = $ip;
    }
}
