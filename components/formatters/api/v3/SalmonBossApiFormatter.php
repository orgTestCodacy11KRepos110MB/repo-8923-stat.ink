<?php

/**
 * @copyright Copyright (C) 2015-2022 AIZAWA Hina
 * @license https://github.com/fetus-hina/stat.ink/blob/master/LICENSE MIT
 * @author AIZAWA Hina <hina@fetus.jp>
 */

declare(strict_types=1);

namespace app\components\formatters\api\v3;

use app\models\SalmonBoss3;

final class SalmonBossApiFormatter
{
    public static function toJson(?SalmonBoss3 $model, bool $fullTranslate = false): ?array
    {
        if (!$model) {
            return null;
        }

        return [
            'key' => $model->key,
            'aliases' => AliasApiFormatter::allToJson($model->salmonBoss3Aliases, $fullTranslate),
            'name' => NameApiFormatter::toJson($model->name, 'app-salmon-boss3', $fullTranslate),
        ];
    }
}
