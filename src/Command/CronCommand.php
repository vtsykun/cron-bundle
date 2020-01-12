<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Command;

use Okvpn\Bundle\CronBundle\Loader\ScheduleLoaderInterface;
use Okvpn\Bundle\CronBundle\Runner\ScheduleRunnerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class CronCommand extends Command
{
    private $scheduleRunner;
    private $loader;

    protected static $defaultName = 'okvpn:cron';

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
            ->addOption('with', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'StampFqcn to add command stamp to all schedules')
            ->addOption('without', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'StampFqcn to remove command stamp from all schedules.')
            ->addOption('command', null, InputOption::VALUE_OPTIONAL, 'Run only selected command')
            ->addOption('demand', null, InputOption::VALUE_NONE, 'Start cron scheduler every one minute without exit')
            ->addOption('group', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Run schedules for specific groups.')
            ->setDescription('Runs currently schedule cron');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($input->getOption('demand')) {
            $output->writeln('Run scheduler without exit');
            while ($now = time()) {
                sleep(60 - ($now % 60));
                $startTime = microtime(true);
                $this->scheduler($input, $output);
                $output->writeln(sprintf('All schedule tasks completed in %.3f seconds', microtime(true) - $startTime), OutputInterface::VERBOSITY_VERBOSE);
            }
        } else {
            $this->scheduler($input, $output);
        }
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

        foreach ($this->loader->getSchedules($options) as $schedule) {
            if (null !== $command && $schedule->getCommand() !== $command) {
                continue;
            }

            if ($without = $input->getOption('without')) {
                $schedule = $schedule->without(...$without);
            }

            $output->writeln(" > Scheduling run for command {$schedule->getCommand()} ...", OutputInterface::VERBOSITY_VERBOSE);
            $this->scheduleRunner->execute($schedule);
        }
    }
}
