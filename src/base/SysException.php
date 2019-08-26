<?php

namespace wzzwx\yii2common\base;

use wzzwx\yii2common\helpers\SysMsg;

class SysException extends \Exception
{
    public function __construct($message = '', $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}