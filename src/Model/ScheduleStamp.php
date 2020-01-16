<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

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

    public function cronExpression(): string
    {
        return $this->cronExpression;
    }
}
