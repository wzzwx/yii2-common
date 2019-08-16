<?php

namespace wzzwx\yii2common\validators;

use yii\validators\RegularExpressionValidator;

class MobileValidator extends RegularExpressionValidator
{
    public function init()
    {
        $this->pattern = "/^1[3-9]{1}\d{9}$/";
        parent::init();
    }
}
