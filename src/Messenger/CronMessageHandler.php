<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Messenger;

use Okvpn\Bundle\CronBundle\Runner\ScheduleRunnerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

if (!interface_exists(MessageHandlerInterface::class)) {
    interface OkCronMessageHandlerBridge {}
} else {
    interface OkCronMessageHandlerBridge extends MessageHandlerInterface {}
}

#[AsMessageHandler]
final class CronMessageHandler implements OkCronMessageHandlerBridge
{
    private $runner;

    public function __construct(ScheduleRunnerInterface $runner)
    {
        $this->runner = $runner;
    }

    public function __invoke(CronMessage $message)
    {
        return $this->runner->execute($message->getSchedule());
    }
}
