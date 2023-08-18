<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Model;

use Okvpn\Bundle\CronBundle\Cron\CronChecker;

class ScheduleStamp implements CommandStamp, PeriodicalStampInterface
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

    /**
     * {@inheritdoc}
     */
    public function getNextRunDate(\DateTimeInterface $run): \DateTimeInterface
    {
        return CronChecker::getNextRunDate($this->cronExpression, $run->getTimezone()->getName(), $run);
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->cronExpression;
    }
}
