<?php

namespace wzzwx\yii2common\assets;

use yii\web\AssetBundle;

/**
 * Class DiffAsset
 * @package wzzwx\yii2common\assets
 */
class DiffAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/src';
    public $css = [
        'css/diff.css',
    ];

    public $js = [
    ];

    public $depends = [
        'yii\web\YiiAsset',
    ];

    public function init()
    {
        parent::init();
    }
}
