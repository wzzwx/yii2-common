<?php

namespace wzzwx\yii2common\handlers;

use Yii;
use wzzwx\yii2common\controllers\BaseServiceController;

class ConsoleErrorHandler extends \yii\console\ErrorHandler
{
    protected function renderException($exception)
    {
        if (Yii::$app->controller instanceof BaseServiceController) {
            Yii::$app->controller->processException($exception);
        }
        return parent::renderException($exception);
    }
}
