<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle;

/**
 * Only for simplify DI.
 *
 * Used for autoconfigure, not required for implementation
 */
interface CronSubscriberInterface extends CronServiceInterface
{
    /**
     * Returns a valid cron expression, like * /10 * * * *
     *
     * @return string
     */
    public static function getCronExpression(): string;
}
