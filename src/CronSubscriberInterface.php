<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle;

interface CronSubscriberInterface
{
    /**
     * Returns a valid cron expression, like * /10 * * * *
     *
     * @return string
     */
    public static function getCronExpression(): string;

    /**
     * Custom logic to process your job
     *
     * @param array $arguments
     * @return mixed
     */
    public function __invoke(array $arguments = []);
}
