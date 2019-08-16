<?php

namespace wzzwx\yii2common\annotations;

use wzzwx\yii2common\base\Annotation;

/**
 * @Annotation
 */
class RuledAnnotation extends Annotation
{
    public $placeholder;
    public $hint;
    public $staticValue;
}
