<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Okvpn\Bundle\CronBundle\Model\OutputStamp;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Okvpn\Bundle\CronBundle\Model\ShellStamp;
use Symfony\Component\Process\Process;

final class ShellInvokeEngine implements MiddlewareEngineInterface
{
    /**
     * @inheritDoc
     */
    public function handle(ScheduleEnvelope $envelope, StackInterface $stack): ScheduleEnvelope
    {
        if (!$stamp = $envelope->get(ShellStamp::class)) {
            return $stack->next()->handle($envelope, $stack);
        }

        $command = $envelope->getCommand();
        if (!class_exists(Process::class)) {
            throw new \LogicException('You need to install symfony/process to run tasks as shell command');
        }

        // in v3, commands should be passed in as arrays of cmd + args
        if (method_exists('Symfony\Component\Process\Process', 'fromShellCommandline')) {
            $process = Process::fromShellCommandline($command);
        } else {
            $process = new Process($command);
        }

        if (null !== $timeout = $stamp->getTimeout()) {
            $process->setTimeout($timeout);
        }

        $output = null;
        try {
            $process->run();
            $output = $process->getErrorOutput() . $process->getOutput();
        } catch (\Exception $exception) {
            $output = $exception->getMessage() . $process->getErrorOutput() . $process->getOutput();
        }

        return $stack->end()->handle($envelope->with(new OutputStamp($output)), $stack);
    }
}
