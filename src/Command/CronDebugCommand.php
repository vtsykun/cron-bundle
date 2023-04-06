<?php

declare(strict_types=1);

namespace Okvpn\Bundle\CronBundle\Command;

use Okvpn\Bundle\CronBundle\Loader\ScheduleLoaderInterface;
use Okvpn\Bundle\CronBundle\Model\ArgumentsStamp;
use Okvpn\Bundle\CronBundle\Model\LoggerAwareStamp;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Okvpn\Bundle\CronBundle\Model\ScheduleStamp;
use Okvpn\Bundle\CronBundle\Runner\ScheduleRunnerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class CronDebugCommand extends Command
{
    protected $scheduleRunner;
    protected $loader;

    protected static $defaultName = 'okvpn:debug:cron';
    protected static $defaultDescription = 'Debug and execute cron jobs manually and show list';

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
        $this->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription)
            ->addOption('with', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'StampFqcn to add command stamp to all schedules')
            ->addOption('without', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'StampFqcn to remove command stamp from all schedules.')
            ->addOption('execute-one', null, InputOption::VALUE_OPTIONAL, 'Execute a selected cron job by the number.')
            ->addOption('group', null, InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY, 'Run schedules for specific groups.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = [];
        if ($input->getOption('group')) {
            $options['groups'] = (array) $input->getOption('group');
        }
        if ($input->getOption('with')) {
            $options['with'] = (array) $input->getOption('with');
        }
        $loggerStamp = $this->createLoggerStamp($output);
        $executeOne = (int)($input->getOption('execute-one') !== null ? $input->getOption('execute-one') : -1);

        $jobs = [];
        $number = 0;
        foreach ($this->loader->getSchedules($options) as $schedule) {
            if ($without = $input->getOption('without')) {
                $schedule = $schedule->without(...$without);
            }
            if (null !== $loggerStamp) {
                $schedule = $schedule->with($loggerStamp);
            }

            if ($executeOne === $number) {
                $output->writeln(" > Scheduling run for command {$schedule->getCommand()} ...");
                $this->scheduleRunner->execute($schedule->without(ScheduleStamp::class));
                return 0;
            }

            $jobs[] = $this->getJobInfo($number, $schedule);
            $number++;
        }


        $table = new Table($output);
        $table
            ->setHeaders(['ID', 'Command', 'Cron', 'Arguments'])
            ->setRows($jobs);

        $table->render();

        return 0;
    }

    protected function getJobInfo(int $id, ScheduleEnvelope $envelope): array
    {
        $info = [$id, $envelope->getCommand()];

        $stamp = $envelope->get(ScheduleStamp::class);
        $info[] = $stamp ? $stamp->cronExpression() : '*';

        $stamp = $envelope->get(ArgumentsStamp::class);
        $info[] = $stamp ? @\json_encode($stamp->getArguments()) : '[]';

        return $info;
    }

    protected function createLoggerStamp(OutputInterface $output)
    {
        if (\class_exists(ConsoleLogger::class)) {
            return new LoggerAwareStamp(new ConsoleLogger($output));
        }

        return null;
    }
}
