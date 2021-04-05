<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 2021/4/5
 * Time: 21:50
 */

namespace wzzwx\yii2common\actions;


use Yii;
use yii\base\Action;
use yii\base\InvalidConfigException;
use yii\caching\Cache;
use yii\di\Instance;
use wzzwx\yii2common\helpers\StringHelper;
use wzzwx\yii2common\helpers\SysMsg;
use Closure;

class SearchAction extends Action
{
    const TYPE_DEFAULT = 0;
    const TYPE_SYSMSG = 1;

    public $limit = 20;
    public $processQuery;
    public $type = 0; //默认方式, 1为sysmsg格式
    public $cache = 'cache';
    // 默认不开启cache
    public $enableCache = false;

    // 默认缓存时间 5min足矣, 因为只是search
    public $cacheTime = 300;
    public $cachePrefix;

    public function init()
    {
        parent::init();
        if (!($this->processQuery instanceof Closure)) {
            throw new InvalidConfigException('查询必须是closure');
        }
        $this->cache = Instance::ensure($this->cache, Cache::class);
    }

    /**
     * Runs the action.
     */
    public function run()
    {
        $this->setCachePrefix();
        \Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
        $data = [];
        $q = Yii::$app->request->get('q', '');
        if ($q) {
            $key = $this->getKey($q);
            $this->enableCache && $data = $this->cache->get($key);
            if (empty($data)) {
                $data = call_user_func($this->processQuery, $q);
                $this->enableCache && $this->cache->set($key, $data, $this->cacheTime);
            }
        }
        if ($this->type == self::TYPE_DEFAULT) {
            $ret = [];
            $ret['results'] = $data;
            return $ret;
        }
        return SysMsg::getOkData($data);
    }

    public function getKey($origin)
    {
        return $this->cachePrefix . $origin;
    }

    public function setCachePrefix()
    {
        empty($this->cachePrefix) && $this->cachePrefix = StringHelper::getUniqueActionId();
    }
}
