<?php

/**
 * @copyright Copyright (C) 2015-2018 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\models;

use Exception;
use Yii;
use yii\base\Model;
use yii\db\ActiveQuery;

class Salmon2FilterForm extends Model
{
    public $user;

    public $stage;
    public $special;
    public $result;
    public $reason;
    public $filter;

    private $filterRotation;

    public function formName()
    {
        return 'filter';
    }

    public function rules()
    {
        return [
            [['stage', 'special', 'result', 'reason', 'filter'], 'string'],
            [['stage'], 'exist', 'skipOnError' => true,
                'targetClass' => SalmonMap2::class,
                'targetAttribute' => 'key',
            ],
            [['special'], 'exist', 'skipOnError' => true,
                'targetClass' => SalmonSpecial2::class,
                'targetAttribute' => 'key',
            ],
            [['result'], 'in',
                'range' => [
                    'cleared',
                    'failed',
                    'failed-wave3',
                    'failed-wave2',
                    'failed-wave1',
                ]
            ],
            [['reason'], 'exist', 'skipOnError' => true,
                'targetClass' => SalmonFailReason2::class,
                'targetAttribute' => 'key',
            ],
            [['filter'], 'validateFilter', 'skipOnEmpty' => true],
        ];
    }

    public function attributeLabels()
    {
        return [
            'stage' => Yii::t('app', 'Stage'),
            'special' => Yii::t('app', 'Special'),
            'result' => Yii::t('app', 'Result'),
            'reason' => Yii::t('app', 'Fail Reason'),
            'filter' => Yii::t('app', 'Filter'),
        ];
    }

    public function validateFilter(string $attr, $params): void
    {
        $value = trim((string)($this->$attr));
        if ($value === '' || $this->hasErrors($attr)) {
            return;
        }

        try {
            foreach (explode(' ', $value) as $v) {
                $v = trim($v);
                if ($v === '') {
                    continue;
                }

                $pos = strpos($v, ':');
                if ($pos === false || $pos < 1) {
                    throw new Exception();
                }

                switch (substr($v, 0, $pos)) {
                    case 'rotation':
                        $v = substr($v, $pos + 1);
                        if (preg_match('/^\d+$/', $v)) {
                            $this->filterRotation = [(int)$v, (int)$v];
                        } elseif (preg_match('/^(\d+)-(\d+)$/', $v, $match)) {
                            $this->filterRotation = [
                                (int)$match[1],
                                (int)$match[2],
                            ];
                        } else {
                            throw new Exception();
                        }
                        return;

                    default:
                        throw new Exception();
                }
            }
        } catch (Exception $e) {
            $this->addError($attr, Yii::t('yii', '{attribute} is invalid.', [
                'attribute' => $this->getAttributeLabel($attr),
            ]));
        }
    }

    public function decorateQuery(ActiveQuery $query): ActiveQuery
    {
        if (!$this->validate()) {
            $query->andWhere('0 = 1');
            return $query;
        }

        if ($this->stage) {
            $stage = SalmonMap2::findOne(['key' => $this->stage]);
            $query->andWhere(['{{salmon2}}.[[stage_id]]' => $stage->id]);
        }

        if ($this->special) {
            $special = SalmonSpecial2::findOne(['key' => $this->special]);
            $query
                ->innerJoin(
                    'salmon_player2',
                    implode(' AND ', [
                        '{{salmon2}}.[[id]] = {{salmon_player2}}.[[work_id]]',
                        '{{salmon_player2}}.[[is_me]] = TRUE',
                    ])
                )
                ->andWhere([
                    '{{salmon_player2}}.[[special_id]]' => $special->id,
                ]);
        }

        if ($this->result) {
            switch ($this->result) {
                case 'cleared':
                    $query->andWhere(['{{salmon2}}.[[clear_waves]]' => 3]);
                    break;

                case 'failed':
                    $query->andWhere(['<', '{{salmon2}}.[[clear_waves]]', 3]);
                    break;

                case 'failed-wave3':
                    $query->andWhere(['{{salmon2}}.[[clear_waves]]' => 2]);
                    break;

                case 'failed-wave2':
                    $query->andWhere(['{{salmon2}}.[[clear_waves]]' => 1]);
                    break;

                case 'failed-wave1':
                    $query->andWhere(['{{salmon2}}.[[clear_waves]]' => 0]);
                    break;
            }
        }

        if ($this->reason) {
            $reason = SalmonFailReason2::findOne(['key' => $this->reason]);
            $query->andWhere(['{{salmon2}}.[[fail_reason_id]]' => $reason->id]);
        }

        if ($this->filterRotation) {
            $query->andWhere(['BETWEEN', '{{salmon2}}.[[shift_period]]',
                $this->filterRotation[0],
                $this->filterRotation[1],
            ]);
        }

        return $query;
    }
}
