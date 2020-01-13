<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Messenger;

use Okvpn\Bundle\CronBundle\Model\MessengerStamp;
use Okvpn\Bundle\CronBundle\Runner\ScheduleRunnerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CronMessageHandler implements MessageHandlerInterface
{
    private $runner;

    public function __construct(ScheduleRunnerInterface $runner)
    {
        $this->runner = $runner;
    }

    public function __invoke(CronMessage $message)
    {
        return $this->runner->execute($message->getSchedule()->without(MessengerStamp::class));
    }
}
