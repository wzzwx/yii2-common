<?php

namespace wzzwx\yii2common\behaviors;

use Yii;
use yii\db\BaseActiveRecord;
use yii\behaviors\BlameableBehavior as YiiBlameableBehavior;

/**
 * Class BlameableBehavior
 */
class BlameableBehavior extends YiiBlameableBehavior
{
    public $createdByAttribute = 'createBy';
    public $updatedByAttribute = 'updateBy';
    public $logUpdate = true;
    public $logCreate = true;

    public function init()
    {
        if (empty($this->attributes)) {
            if ($this->logUpdate) {
                $this->attributes = [
                    BaseActiveRecord::EVENT_BEFORE_INSERT => [$this->updatedByAttribute],
                    BaseActiveRecord::EVENT_BEFORE_UPDATE => $this->updatedByAttribute,
                ];
            }
            if ($this->logCreate) {
                $this->attributes[BaseActiveRecord::EVENT_BEFORE_INSERT][] = $this->createdByAttribute;
            }
        }
    }

    protected function getValue($event)
    {
        if ($this->value === null) {
            $user = Yii::$app->get('user', false);
            return $user && !$user->getIsGuest() ? $user->id : 0;
        }

        return parent::getValue($event);
    }
}
