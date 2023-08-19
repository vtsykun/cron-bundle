<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Command;

use Okvpn\Bundle\CronBundle\Event\LoopEvent;
use Okvpn\Bundle\CronBundle\Loader\ScheduleLoaderInterface;
use Okvpn\Bundle\CronBundle\Logger\CronConsoleLogger;
use Okvpn\Bundle\CronBundle\Model\EnvironmentStamp;
use Okvpn\Bundle\CronBundle\Model\LoggerAwareStamp;
use Okvpn\Bundle\CronBundle\React\ReactLoopAdapter;
use Okvpn\Bundle\CronBundle\Runner\ScheduleLoopInterface;
use Okvpn\Bundle\CronBundle\Runner\ScheduleRunnerInterface;
use Psr\Clock\ClockInterface as PsrClockInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand('okvpn:cron', description: 'Runs currently schedule cron')]
class CronCommand extends Command
{
    private $scheduleRunner;
    private $loader;
    private $scheduleLoop;
    private $clock;
    private $timezone;
    private $dispatcher;

    /**
     * @param ScheduleRunnerInterface $scheduleRunner
     * @param ScheduleLoaderInterface $loader
     * @param EventDispatcherInterface|null $dispatcher
     * @param ScheduleLoopInterface|null $scheduleLoop
     * @param PsrClockInterface|null $clock
     * @param string|null $timezone
     */
    public function __construct(ScheduleRunnerInterface $scheduleRunner, ScheduleLoaderInterface $loader, EventDispatcherInterface $dispatcher = null, ScheduleLoopInterface $scheduleLoop = null, PsrClockInterface $clock = null, string $timezone = null)
    {
        $this->scheduleRunner = $scheduleRunner;
        $this->loader = $loader;
        $this->dispatcher = $dispatcher;
        $this->scheduleLoop = $scheduleLoop;
        $this->clock = $clock;
        $this->timezone = $timezone;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName('okvpn:cron')
            ->setDescription('Runs currently schedule cron')
            ->addOption('with', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'StampFqcn to add command stamp to all schedules')
            ->addOption('without', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'StampFqcn to remove command stamp from all schedules.')
            ->addOption('command', null, InputOption::VALUE_OPTIONAL, 'Run only selected command')
            ->addOption('demand', null, InputOption::VALUE_NONE, 'Start cron scheduler every one minute without exit')
            ->addOption('group', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Run schedules for specific groups.')
            ->addOption('time-limit', null, InputOption::VALUE_OPTIONAL, 'Run cron scheduler during this time (sec.)')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Debug periodical tasks without execution it.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('demand')) {
            $this->executeLoop($input, $output);
        } else {
            $this->scheduler($input, $output);
        }

        return 0;
    }

    protected function executeLoop(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln('Run scheduler without exit');

        $loop = $this->scheduleLoop;
        if (($timeLimit = $input->getOption('time-limit')) > 0) {
            $loop->addTimer((int)$timeLimit, static function () use ($loop) {
                $loop->stop();
            });
        }

        $schedulerRunner = function () use ($input, $output, $loop) {
            $runAt = \microtime(true);
            if ($loop instanceof ReactLoopAdapter) {
                $loop->setDefaultLoopTime($this->getCurrentDate());
            }

            $this->scheduler($input, $output);
            $output->writeln(sprintf('[%s] All schedule tasks completed in %.3f seconds', $this->getCurrentDate()->format('Y-m-d H:i:s.u'), \microtime(true) - $runAt), OutputInterface::VERBOSITY_VERBOSE);
            if ($loop instanceof ReactLoopAdapter) {
                $loop->setDefaultLoopTime();
            }
        };

        $this->dispatchLoopEvent(LoopEvent::LOOP_INIT);

        $delayRun = 60.0 - fmod((float)$this->getCurrentDate()->format('U.u'), 60.0);
        $loop->addTimer($delayRun, static function () use ($schedulerRunner, $loop) {
            $loop->futureTick($schedulerRunner);
            $loop->addPeriodicTimer(60, $schedulerRunner);
        });

        $this->scheduleLoop->run();
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function scheduler(InputInterface $input, OutputInterface $output): void
    {
        $options = [];
        $command = $input->getOption('command');
        if ($input->getOption('group')) {
            $options['groups'] = (array) $input->getOption('group');
        }
        if ($input->getOption('with')) {
            $options['with'] = (array) $input->getOption('with');
        }

        $now = $this->getCurrentDate();
        $roundTime = (int)(round($now->getTimestamp()/60)*60);
        $options['demand'] = $input->getOption('demand');
        $options['dry-run'] = $input->getOption('dry-run');

        $envStamp = new EnvironmentStamp($options + ['now' => new \DateTimeImmutable('@'.$roundTime, $now->getTimezone()), 'dispatch-loop' => null !== $this->dispatcher]);
        $loggerStamp = $this->createLoggerStamp($output);

        $this->dispatchLoopEvent(LoopEvent::LOOP_START);

        foreach ($this->loader->getSchedules($options) as $schedule) {
            if (null !== $command && $schedule->getCommand() !== $command) {
                continue;
            }
            $schedule = $schedule->with($envStamp, $loggerStamp);

            if ($without = $input->getOption('without')) {
                $schedule = $schedule->without(...$without);
            }

            $this->scheduleRunner->execute($schedule);
        }

        $this->dispatchLoopEvent(LoopEvent::LOOP_END);
    }

    protected function createLoggerStamp(OutputInterface $output)
    {
        return new LoggerAwareStamp(new CronConsoleLogger($output));
    }

    protected function getCurrentDate(): \DateTimeImmutable
    {
        $now = $this->clock ? $this->clock->now() : new \DateTimeImmutable('now');
        if (null !== $this->timezone) {
            $now = new \DateTimeImmutable('@'.$now->format('U.u'), new \DateTimeZone($this->timezone));
        }

        return $now;
    }

    protected function dispatchLoopEvent(string $name): void
    {
        if (null !== $this->dispatcher && null !== $this->scheduleLoop) {
            $this->dispatcher->dispatch(new LoopEvent($this->scheduleLoop), $name);
        }
    }
}
