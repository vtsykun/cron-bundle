<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Command;

use Okvpn\Bundle\CronBundle\Loader\ScheduleLoaderInterface;
use Okvpn\Bundle\CronBundle\Model\LoggerAwareStamp;
use Okvpn\Bundle\CronBundle\Runner\ScheduleRunnerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('okvpn:cron', description: 'Runs currently schedule cron')]
class CronCommand extends Command
{
    private $scheduleRunner;
    private $loader;

    /**
     * @param ScheduleRunnerInterface $scheduleRunner
     * @param ScheduleLoaderInterface $loader
     */
    public function __construct(ScheduleRunnerInterface $scheduleRunner, ScheduleLoaderInterface $loader)
    {
        $this->scheduleRunner = $scheduleRunner;
        $this->loader = $loader;

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
            ->addOption('time-limit', null, InputOption::VALUE_OPTIONAL, 'Run cron scheduler during this time (sec.)');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('demand')) {
            $output->writeln('Run scheduler without exit');
            $startTime = \time();
            $timeLimit = $input->getOption('time-limit');

            while ($now = \time() and (null === $timeLimit || $now - $startTime < $timeLimit)) {
                \sleep(60 - ($now % 60));
                $runAt = \microtime(true);
                $this->scheduler($input, $output);
                $output->writeln(sprintf('All schedule tasks completed in %.3f seconds', \microtime(true) - $runAt), OutputInterface::VERBOSITY_VERBOSE);
            }
        } else {
            $this->scheduler($input, $output);
        }

        return 0;
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

        $loggerStamp = $this->createLoggerStamp($output);
        foreach ($this->loader->getSchedules($options) as $schedule) {
            if (null !== $command && $schedule->getCommand() !== $command) {
                continue;
            }

            if ($without = $input->getOption('without')) {
                $schedule = $schedule->without(...$without);
            }
            if (null !== $loggerStamp) {
                $schedule = $schedule->with($loggerStamp);
            }

            $this->scheduleRunner->execute($schedule);
        }
    }

    protected function createLoggerStamp(OutputInterface $output)
    {
        if (\class_exists(ConsoleLogger::class)) {
            return new LoggerAwareStamp(new ConsoleLogger($output));
        }

        return null;
    }
}
