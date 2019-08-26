<?php

namespace wzzwx\yii2common\behaviors;

use yii\base\Behavior;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use wzzwx\yii2common\helpers\SysMsg;
use wzzwx\yii2common\base\SysException;

/**
 * Usage:
 * [
 *     'class' => StrictStateBehavior::class,
 *     'attr' => 'status',
 *     // default 指定初始化状态
 *     'default' => 1,
 *     // states 是以 state 值为 key, 描述为 value 的数组
 *     'states' => [
 *         1 => 'state active',
 *         2 => 'state inactive',
 *     ],
 *     // transitions 包含所有状态转移事件数组
 *     // 数组中定义了事件名、当前状态及目标状态。
 *     'transitions' => [
 *         [
 *             'name' => 'activate',
 *             'from' => 1,
 *             'to' => 2,
 *         ],
 *         [
 *             'name' => 'deactivate',
 *             'from' => 2,
 *             'to' => 1,
 *         ],
 *     ],
 * ]
 */
class StrictStateBehavior extends Behavior
{
    public $attr;
    public $dimensionMapping = [];
    public $default = null;
    public $states;
    public $transitions;

    private $events = [];
    private $errors = [];

    const EVENT_VALIDATE_TRANS = 'eventValidateTrans';
    const EVENT_BEFORE_TRANS = 'eventBeforeTrans';
    const EVENT_BEFORE_LEAVE_STATE = 'eventBeforeLeaveState';
    const EVENT_BEFORE_ENTER_STATE = 'eventBeforeEnterState';
    const EVENT_AFTER_LEAVE_STATE = 'eventAfterLeaveState';
    const EVENT_AFTER_ENTER_STATE = 'eventAfterEnterState';
    const EVENT_AFTER_TRANS = 'eventAfterTrans';

    public function init()
    {
        parent::init();

        if (false === $this->stateExists($this->default)) {
            throw new InvalidConfigException('default 必须为 states 中定义的状态');
        }

        foreach ($this->transitions as $event) {
            if (!isset($event['name']) || !isset($event['from']) || !isset($event['to'])) {
                throw new InvalidConfigException('transitions 中定义的事件必须同时包含 name, from, to');
            }
            if (isset($this->events[$event['name']])) {
                throw new InvalidConfigException("事件名{$event['name']}重复");
            }
            $this->events[$event['name']] = $event;
        }
    }

    public static function getValidateTransEventName($trans)
    {
        return self::EVENT_VALIDATE_TRANS . ucfirst($trans);
    }

    public static function getBeforeTransEventName($trans)
    {
        return self::EVENT_BEFORE_TRANS . ucfirst($trans);
    }

    public static function getBeforeLeaveStateEventName($state)
    {
        return self::EVENT_BEFORE_LEAVE_STATE . ucfirst($state);
    }

    public static function getBeforeEnterStateEventName($state)
    {
        return self::EVENT_BEFORE_ENTER_STATE . ucfirst($state);
    }

    public static function getAfterLeaveStateEventName($state)
    {
        return self::EVENT_AFTER_LEAVE_STATE . ucfirst($state);
    }

    public static function getAfterEnterStateEventName($state)
    {
        return self::EVENT_AFTER_ENTER_STATE . ucfirst($state);
    }

    public static function getAfterTransEventName($trans)
    {
        return self::EVENT_AFTER_TRANS . ucfirst($trans);
    }

    private function stateExists($state)
    {
        return isset($this->states[$state]);
    }

    private function transExists($trans)
    {
        return isset($this->events[$trans]);
    }

    private function getEventByName($eventName)
    {
        return ArrayHelper::getValue($this->events, $eventName, null);
    }

    public function getSsError(): string
    {
        if (empty($this->errors)) {
            return '';
        }
        return SysMsg::getErrMsg($this->errors[0]);
    }

    public function getSsAllErrors(): array
    {
        $msgList = [];
        foreach ($this->errors as $error) {
            $msgList[] = SysMsg::getErrMsg($error);
        }

        return $msgList;
    }

    public function ssCurrentState()
    {
        return $this->owner->{$this->attr};
    }

    public function ssIsState($state)
    {
        return $this->ssCurrentState() === $state;
    }

    public function ssSetState($state, $triggerEvent = true): bool
    {
        $this->errors = [];
        if (false === $this->stateExists($state)) {
            $this->errors[] = ['B_STATE_UNDEFINED_ERR', $state];
            return false;
        }
        $from = $this->ssCurrentState();

        if ($triggerEvent) {
            try {
                $this->ssBeforeLeaveState($from);
                $this->ssBeforeEnterState($state);
            } catch (SysException $e) {
                $this->errors[] = $e->getMessage();
                return false;
            }
        }

        $this->owner->{$this->attr} = $state;
        if (!$this->owner->save()) {
            return false;
        }

        if ($triggerEvent) {
            try {
                $this->ssAfterLeaveState($from);
                $this->ssAfterEnterState($state);
            } catch (SysException $e) {
                $this->errors[] = $e->getMessage();
                return false;
            }
        }

        return true;
    }

    public function ssBeforeLeaveState($state)
    {
        $this->owner->trigger(self::getBeforeLeaveStateEventName($state));
    }

    public function ssBeforeEnterState($state)
    {
        $this->owner->trigger(self::getBeforeEnterStateEventName($state));
    }

    public function ssAfterLeaveState($state)
    {
        $this->owner->trigger(self::getAfterLeaveStateEventName($state));
    }

    public function ssAfterEnterState($state)
    {
        $this->owner->trigger(self::getAfterEnterStateEventName($state));
    }

    /**
     * 检查 trans 是否被允许
     */
    public function ssCanTrans($trans)
    {
        $this->errors = [];
        if (false === $this->transExists($trans)) {
            $this->errors[] = ['B_EVENT_UNDEFINED_ERR', $trans];
            return false;
        }

        $this->initState();
        $event = $this->getEventByName($trans);
        if ($event['from'] !== $this->owner->{$this->attr}) {
            $this->errors[] = [
                'B_EVENT_STATE_MISMATCH_ERR',
                $trans,
                $this->getStateDesc($this->owner->{$this->attr}),
            ];
            return false;
        }

        return $this->ssValidateTrans($trans);
    }

    /**
     * 触发 validateTrans 事件。
     */
    public function ssValidateTrans($trans)
    {
        try {
            $this->owner->trigger(self::getValidateTransEventName($trans));
        } catch (SysException $e) {
            $this->errors[] = $e->getMessage();
            return false;
        }
        return true;
    }

    public function ssCannotTrans($trans)
    {
        return !$this->ssCanTrans($trans);
    }

    /**
     * 根据当前状态，返回当前可执行的 trans 列表
     */
    public function ssAvailableTrans($trans, $runValidate = true)
    {
        $state = $this->ssCurrentState();
        $availTrans = [];
        foreach ($this->events as $eventName => $event) {
            if ($state === $event['from']) {
                if ($runValidate && !$this->ssValidateTrans($eventName)) {
                    continue;
                }
                $availTrans[] = $eventName;
            }
        }

        return $availTrans;
    }

    public function ssTransition($trans): bool
    {
        if ($this->ssCanTrans($trans)) {
            $event = $this->getEventByName($trans);
            $from = $event['from'];
            $to = $event['to'];

            // 状态转移前的异常会中断转移过程
            try {
                $this->ssBeforeLeaveState($from);
                $this->ssBeforeEnterState($to);
                $this->ssBeforeTrans($trans);
            } catch (SysException $e) {
                $this->errors[] = $e->getMessage();
                return false;
            }

            $this->owner->{$this->attr} = $to;
            if (!$this->owner->save()) {
                return false;
            }

            try {
                $this->ssAfterLeaveState($from);
                $this->ssAfterEnterState($to);
                $this->ssAfterTrans($trans);
            } catch (SysException $e) {
                $this->errors[] = $e->getMessage();
            }

            return true;
        }

        return false;
    }

    public function ssDimensionTransition($keys): bool
    {
        $target = $this->dimensionMapping;
        foreach ($keys as $key) {
            $target = ArrayHelper::getValue($target, $key);
            if (empty($target)) {
                break;
            } elseif (is_array($target)) {
                continue;
            } else {
                return $this->ssTransition($target);
            }
        }
        return false;
    }

    public function ssBeforeTrans($trans)
    {
        $this->owner->trigger(self::getBeforeTransEventName($trans));
    }

    public function ssAfterTrans($trans)
    {
        $this->owner->trigger(self::getAfterTransEventName($trans));
    }

    private function initState()
    {
        if (is_null($this->owner->{$this->attr})) {
            $this->owner->{$this->attr} = $this->default;
        }
    }

    public function getStateDesc($state = null)
    {
        $state = ($state ?? $this->owner->{$this->attr});
        return ArrayHelper::getValue($this->states, $state, 'unknown');
    }
}

SysMsg::register('B_STATE_UNDEFINED_ERR', '未定义的状态 %s');
SysMsg::register('B_EVENT_UNDEFINED_ERR', '未定义的事件 %s');
SysMsg::register('B_EVENT_STATE_MISMATCH_ERR', '事件 %s 不适用于当前状态 %s');
