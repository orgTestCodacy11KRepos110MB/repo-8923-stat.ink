<?php

declare(strict_types=1);

use app\components\widgets\FA;
use app\components\widgets\Icon;
use app\models\User;
use yii\helpers\Html;

/**
 * @var User $user
 * @var View $this
 */

echo Html::tag(
  'p',
  implode('', [
    Html::a(
      implode(' ', [
        Icon::listConfig(),
        Html::encode(Yii::t('app', 'View Settings')),
      ]),
      '#table-config',
      ['class' => 'btn btn-default mr-1'],
    ),
    Html::a(
      implode(' ', [
        Icon::list(),
        Html::encode(Yii::t('app', 'Simplified List')),
      ]),
      array_merge(
        [], // $filter->toQueryParams(),
        ['salmon-v3/index',
          'screen_name' => $user->screen_name,
          'v' => 'simple',
        ]
      ),
      [
        'class' => 'btn btn-default',
        'rel' => 'nofollow',
      ]
    ),
  ]),
);
