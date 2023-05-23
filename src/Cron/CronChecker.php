<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Cron;

use Cron\CronExpression;

class CronChecker
{
    private static $exprMapping = ['@random' => [__CLASS__, 'isRandomDue']];

    public static function isDue(string $cron, $timeZone = null, $current = 'now'): bool
    {
        foreach (static::$exprMapping as $expr => $callable) {
            if (\strpos($cron, $expr) === 0) {
                return \call_user_func($callable, $cron, $timeZone, $current, $expr);
            }
        }

        return (new CronExpression($cron))->isDue($current, $timeZone);
    }

    public static function addExprMapping(string $expr, $callable): void
    {
        static::$exprMapping[$expr] = $callable;
    }

    public static function isValidExpression(string $cron): bool
    {
        foreach (static::$exprMapping as $expr => $callable) {
            if (\strpos($cron, $expr) === 0) {
                return true;
            }
        }

        return CronExpression::isValidExpression($cron);
    }

    public static function isRandomDue(string $cron): bool
    {
        $probe = \mt_rand() / (float) \mt_getrandmax();

        $probability = \trim(\str_replace('@random', '', $cron)) / 60.0; // 60 sec

        return $probe < 1/$probability;
    }
}
