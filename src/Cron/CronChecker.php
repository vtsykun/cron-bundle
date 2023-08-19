<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Cron;

use Cron\CronExpression;

class CronChecker
{
    private static $exprMapping = ['@random' => [[__CLASS__, 'isRandomDue'], [__CLASS__, 'isRandomNext']]];

    public static function isDue(string $cron, $timeZone = null, $current = 'now'): bool
    {
        foreach (static::$exprMapping as $expr => $callable) {
            if (\strpos($cron, $expr) === 0) {
                return \call_user_func($callable[0], $cron, $timeZone, $current, $expr);
            }
        }

        return (new CronExpression($cron))->isDue($current, $timeZone);
    }

    public static function getNextRunDate(string $cron, $timeZone = null, $current = 'now'): \DateTimeInterface
    {
        foreach (static::$exprMapping as $expr => $callable) {
            if (\strpos($cron, $expr) === 0) {
                return \call_user_func($callable[1], $cron, $timeZone, $current, $expr);
            }
        }

        return (new CronExpression($cron))->getNextRunDate($current, 0, false, $timeZone);
    }

    public static function addExprMapping(string $expr, callable $dueCallable, $nextCallable = null): void
    {
        static::$exprMapping[$expr] = [$dueCallable, $nextCallable];
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

    public static function isRandomNext(string $cron, $timeZone, $current): \DateTimeInterface
    {
        $cron = (int)\trim(\str_replace('@random', '', $cron));
        $current = \is_string($current) ? new \DateTime($current) : $current;
        $next = $current->getTimestamp() + \random_int(0, $cron);

        return new \DateTimeImmutable('@'.$next, \is_string($timeZone) ? new \DateTimeZone($timeZone) : $timeZone);
    }

    public static function isRandomDue(string $cron): bool
    {
        $probe = \mt_rand() / (float) \mt_getrandmax();

        $probability = \trim(\str_replace('@random', '', $cron)) / 60.0; // 60 sec

        return $probe < 1/$probability;
    }
}
