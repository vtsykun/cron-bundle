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
        $this->setName('okvpn:cron:run')
            ->addOption('with', null, InputOption::VALUE_IS_ARRAY, 'StampFqcn to add command stamp to all schedules')
            ->addOption('without', null, InputOption::VALUE_IS_ARRAY, 'StampFqcn to remove command stamp from all schedules.')
            ->addOption('command', null, InputOption::VALUE_OPTIONAL, 'Run only selected command')
            ->setDescription('Runs any currently schedule cron');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $command = $input->getOption('command');
        foreach ($this->loader->getSchedules() as $schedule) {
            if (null !== $command && $schedule->getCommand() !== $command) {
                continue;
            }

            $output->writeln(" > Scheduling run for command {$schedule->getCommand()} ...");
            $this->scheduleRunner->execute($schedule);
        }
    }
}
