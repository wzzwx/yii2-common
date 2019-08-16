<?php

namespace wzzwx\yii2common\validators;

class CaptchaValidator extends \yii\captcha\CaptchaValidator
{
    /**
     * @inheritdoc
     */
    public function clientValidateAttribute($object, $attribute, $view)
    {
        return '';
    }
}
