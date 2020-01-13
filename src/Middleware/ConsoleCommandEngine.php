<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Okvpn\Bundle\CronBundle\Model\ArgumentsStamp;
use Okvpn\Bundle\CronBundle\Model\OutputStamp;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\HttpKernel\KernelInterface;

final class ConsoleCommandEngine implements MiddlewareEngineInterface
{
    /** @var KernelInterface|null */
    private $kernel;

    /**
     * Allow null, because symfony/http-kernel is not required by default
     *
     * @param KernelInterface|null $kernel
     */
    public function __construct(KernelInterface $kernel = null)
    {
        $this->kernel = $kernel;
    }

    /**
     * @inheritDoc
     */
    public function handle(ScheduleEnvelope $envelope, StackInterface $stack): ScheduleEnvelope
    {
        if (null === $this->kernel) {
            return $stack->next()->handle($envelope, $stack);
        }

        $application = new Application($this->kernel);
        $application->setAutoExit(false);
        if (false === $application->has($envelope->getCommand())) {
            return $stack->next()->handle($envelope, $stack);
        }

        $commandArguments = [];
        if ($stamp = $envelope->get(ArgumentsStamp::class)) {
            $commandArguments = $stamp->getArguments();
        }

        $input = new ArrayInput(array_merge(['command' => $envelope->getCommand()], $commandArguments));

        $output = new BufferedOutput();
        $application->run($input, $output);

        $envelope = $envelope->with(new OutputStamp($output->fetch()));
        return $stack->end()->handle($envelope, $stack);
    }
}
