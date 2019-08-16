<?php

namespace wzzwx\yii2common\annotations;

use wzzwx\yii2common\base\Annotation;

/**
 * @Annotation
 */
class S2Annotation extends Annotation
{
    public $ajax = true;
    public $multiple = false;
    public $allowClear = true;
    public $url;
    public $name;
    public $initValueText;
    public $placeholder;
    public $hint;
    public $staticValue;
}
