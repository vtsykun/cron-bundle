<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

use Cron\CronExpression;

class ScheduleStamp implements CommandStamp
{
    private $cronExpression;

    /**
     * @param string $cronExpression
     */
    public function __construct(string $cronExpression)
    {
        $this->cronExpression = $cronExpression;
    }

    public function cronExpression(): CronExpression
    {
        return  CronExpression::factory($this->cronExpression);
    }
}
