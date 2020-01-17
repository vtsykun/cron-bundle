# Okvpn - Cron Bundle

This bundle provides interfaces for registering and handle scheduled tasks within your Symfony application.

[![Latest Stable Version](https://poser.okvpn.org/okvpn/cron-bundle/v/stable)](https://packagist.org/packages/okvpn/cron-bundle) [![Total Downloads](https://poser.okvpn.org/okvpn/cron-bundle/downloads)](https://packagist.org/packages/okvpn/cron-bundle) [![Latest Unstable Version](https://poser.okvpn.org/okvpn/cron-bundle/v/unstable)](https://packagist.org/packages/okvpn/cron-bundle) [![License](https://poser.okvpn.org/okvpn/cron-bundle/license)](https://packagist.org/packages/okvpn/cron-bundle)

## Purpose
This is a more simpler alternative of existing cron bundle without doctrine deps.
Here also added support middleware for customization handling cron jobs across a cluster install: 
(Send jobs to message queue, like Symfony Messenger; locking; etc.).
This allow to limit the number of parallel running processes and prioritized it.

Features
--------

- Not need doctrine/database.
- Integration with Symfony Messenger.
- Load a cron job from a different storage (config.yml, tagged services, commands).
- Support many engines to run cron (in parallel process, message queue, consistently).
- Support many types of cron handlers/command: (services, symfony commands, UNIX shell commands).
- Middleware and customization.

## Table of Contents

 - [Base Usage](#usage)
 - [Registration a new scheduled task](#registration-a-new-scheduled-task)
 - [Configuration](#full-configuration-reference)
 - [Symfony Messenger Integration](#handle-cron-jobs-via-symfony-messenger)
 - [Your own Scheduled Tasks Loader](#your-own-scheduled-tasks-loaders)
 - [Handling cron jobs across a cluster](#handling-cron-jobs-across-a-cluster-or-custom-message-queue)

Usage
-----

#### First way. Install system crontab

To regularly run a set of commands from your application, configure your system to run the 
oro:cron command every minute. On UNIX-based systems, you can simply set up a crontab entry for this:

```
*/1 * * * * /path/to/php /path/to/bin/console okvpn:cron --env=prod > /dev/null
```

#### Second way. Using supervisor

Setup Supervisor to run cron on demand.

```
sudo apt -y --no-install-recommends install supervisor
```

Create a new supervisor configuration.

```
sudo vim /etc/supervisor/conf.d/app_cron.conf
```
Add the following lines to the file.

```
[program:app-cron]
command=/path/to/bin/console okvpn:cron --env=prod --demand
process_name=%(program_name)s_%(process_num)02d
numprocs=1
autostart=true
autorestart=true
startsecs=0
redirect_stderr=true
priority=1
user=www-data
```

## Registration a new scheduled task

To add a new scheduled task you can use tag `okvpn.cron` or using `autoconfigure`
with interface `Okvpn\Bundle\CronBundle\CronSubscriberInterface`.

#### Services.

```php
<?php

namespace App\Cron;

use Okvpn\Bundle\CronBundle\CronSubscriberInterface;

class MyCron implements CronSubscriberInterface // implements is not required, but helpful if yor are use autoconfigure
{
    public function __invoke(array $arguments = [])
    {
        // processing...
    }

    public static function getCronExpression(): string
    {
        return '*/10 * * * *';
    }
}
```

If you use the default configuration, the corresponding service will be automatically registered thanks to `autoconfigure`. 
To declare the service explicitly you can use the following snippet:

```yaml
services:
    App\Cron\MyCron:
        tags:
            - { name: okvpn.cron, cron: '*/5 * * * *' }
    

    App\Cron\SmsNotificationHandler:
        tags:
            - { name: okvpn.cron, cron: '*/5 * * * *', lock: true, async: true }

```

Possible options to configure with tags are:

- `cron` -  A cron expression, if empty, the command will run always.
- `lock` -  Prevent to run the command again, if prev. command is not finished yet. Possible value: `true`, `{name: lock1, ttl: 300}`.
To use this option need to install symfony [lock component](https://symfony.com/doc/4.4/components/lock.html) 
- `async` - Run command in the new process without blocking main thread.
- `arguments` - Array of arguments, used to run symfony console commands or pass arguments to handler. 
- `priority` - Sorting priority.
- `group` - Group name, see Cron Grouping section.
- `messenger` - Send jobs into Messenger Bus. Default `false`. You also can specify transport here `{routing: async}`,
see [Symfony Routing Messages to a Transport](https://symfony.com/doc/current/messenger.html#routing-messages-to-a-transport) 

#### Symfony console command

```yaml
services:
    App\Command\DowloadOrdersCommand:
        tags:
            - { name: console.command }
            - { name: okvpn.cron, cron: '*/5 * * * *' }
```

#### Via configuration / shell commands

```yaml
okvpn_cron:
  tasks:
    -
      command: "php %kernel_project.dir%/bin/console cache:clear --env=prod" # Shell command 
      cron: "0 0 * * *"
    -
      command: "bash /root/renew.sh > /root/renew.txt" # Shell command
      group: root # Filtering by group. You can run `bin/console okvpn:cron --group=root` under the root user 
      cron: "0 0 * * *"
    -
      command: 'App\Cron\YouServiceName' # Your service name
      cron: "0 0 * * *"
    -
      command: 'app:cron:sync-amazon-orders' # Your symfony console command name
      cron: "*/30 * * * *"
      async: true
      arguments: { '--transport': 15 } # command arguments or options
```

## Full Configuration Reference

```yaml
# Your config file
okvpn_cron:
    lock_factory: ~ # The Service to create lock. Default lock.factory, see Symfony Lock component.
    timezone: ~ # default timezone, like Europe/Minsk. if null will use php.ini default
    messenger:
        enable: false # Enable symfony messenger
        
    # Default options allow to add define default policy for all tasks, 
    # For example to always run commands with locking and asynchronously
    default_policy:
        async: true # Default false
        lock: true # Default false
        messenger: true # Handle all jobs with symfony messenger bus.
    
    # Stamps it's markers that will add to each tasks.
    with_stamps:
        - 'Packagist\WebBundle\Cron\WorkerStamp'

    tasks: # Defined tasks via configuration
      - 
        command: 'app:noaa:gfs-grib-download'
        cron: '34,45 */6 * * *'
        messenger: { routing: lowpriority } # See Messenger configuration
        lock: true
        arguments: { '--transport': '0p25' }
        # Here you can also add other custom options and create your own middleware.
      -
        command: "bash /root/renew.sh > /root/renew.txt" # Shell command
        group: root # Group filter. You can run `bin/console okvpn:cron --group=root` under the root user 
        cron: "0 0 * * *"
```

## Handle Cron Jobs via Symfony Messenger 

To limit the number of parallel running processes you can handle the cron jobs in the queue using Symfony Messenger.

1. Install Symfony Messenger
2. Enable default route for cron job

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            async: "%env(MESSENGER_TRANSPORT_DSN)%"
            lowpriority: "%env(MESSENGER_TRANSPORT_LOW_DSN)%"

        routing:
            # async is whatever name you gave your transport above
            'Okvpn\Bundle\CronBundle\Messenger\CronMessage':  async
```

3. Enable Messenger for cron.

```yaml
# config/packages/cron.yaml
okvpn_cron:
    # Required. messenger middleware is disable
    messenger:
        enable: true

    # Optional
    default_options:
        messenger: true # For handle all cron jobs with messenger
    
    # Optional 
    tasks:
        - 
            command: 'app:noaa:gfs-grib-download'
            cron: '34,45 */6 * * *'
#           messenger: true # OR
            messenger: { routing: lowpriority } # Send to lowpriority transport
```

More information how to use [messenger here](https://symfony.com/doc/current/messenger.html)

## Your own Scheduled Tasks Loaders

You can create custom tasks loaders, see example

```php
<?php declare(strict_types=1);

namespace Packagist\WebBundle;

use Okvpn\Bundle\CronBundle\Loader\ScheduleLoaderInterface;
use Okvpn\Bundle\CronBundle\Model\ScheduleEnvelope;
use Okvpn\Bundle\CronBundle\Model;

class DoctrineCronLoader implements ScheduleLoaderInterface
{
    /**
     * @inheritDoc
     */
    public function getSchedules(array $options = []): iterable
    {
        // ... get active cron from database/etc.

        yield new ScheduleEnvelope(
            'yor_service_command_name', // A service name, (object must be have a __invoke method)
            // !!! Important. You must mark this service with tag `okvpn.cron_service` to add into our service locator.
            new Model\ScheduleStamp('*/5 * * * *'), // Cron expression
            new Model\LockStamp('yor_service_command_name'), // If you want to use locking
            new Model\AsyncStamp() // If you want to run asynchronously
        );

        yield new ScheduleEnvelope(
            'app:cron:sync-amazon-orders', // Symfony console
            new Model\ScheduleStamp('*/5 * * * *'), // Cron expression
            new Model\LockStamp('sync-amazon-orders_1'), // If you want to use locking
            new Model\ArgumentsStamp(['--integration' => 1]), // Command arguments
            new Model\AsyncStamp() // If you want to run asynchronously
        );

        yield new ScheduleEnvelope(
            'ls -l', // shell command
            new Model\ScheduleStamp('*/10 * * * *'),  // Cron expression
            new Model\ShellStamp(['timeout'=> 300]), // Run command as shell
        );

        // ...
    }
}

```

And register your loader.

```yaml
services:
    Packagist\WebBundle\DoctrineCronLoader:
        tags: [okvpn_cron.loader]

```

## Handling cron jobs across a cluster or custom message queue 

See example of customization 
[one](https://github.com/vtsykun/packeton/tree/master/src/Packagist/WebBundle/Cron/WorkerMiddleware.php), 
[two](https://github.com/vtsykun/packeton/tree/master/src/Packagist/WebBundle/Cron/CronWorker.php)

License
-------

MIT License.
