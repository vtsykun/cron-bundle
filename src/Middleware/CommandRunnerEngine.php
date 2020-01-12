<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Middleware;

use Okvpn\Bundle\CronBundle\Model\ArgumentsStamp;
use Okvpn\Bundle\CronBundle\Model\OutputStamp;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;

final class CommandRunnerEngine implements MiddlewareEngineInterface
{
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @inheritDoc
     */
    public function handle(ScheduleEnvelope $envelope, StackInterface $stack): ScheduleEnvelope
    {
        if (!$this->container->has($envelope->getCommand())) {
            return $stack->next()->handle($envelope, $stack);
        }

        $handler = $envelope->get($envelope->getCommand());
        if ($handler instanceof Command) {
            $commandArguments = $envelope->get(ArgumentsStamp::class) ?
                $envelope->get(ArgumentsStamp::class)->getArguments() : [];

            $input = new ArrayInput(array_merge(['command' => $handler->getName()], $commandArguments));

            $output = new BufferedOutput();
            $handler->run($input, $output);
            return $stack->end()->handle($envelope->with(new OutputStamp($output->fetch())), $stack);
        }

        return $stack->next()->handle($envelope, $stack);
    }
}
