# Okvpn - Cron Bundle

This bundle provides interfaces for registering scheduled tasks within your Symfony application.

[![Latest Stable Version](https://poser.okvpn.org/okvpn/cron-bundle/v/stable)](https://packagist.org/packages/okvpn/cron-bundle) [![Total Downloads](https://poser.okvpn.org/okvpn/cron-bundle/downloads)](https://packagist.org/packages/okvpn/cron-bundle) [![Latest Unstable Version](https://poser.okvpn.org/okvpn/cron-bundle/v/unstable)](https://packagist.org/packages/okvpn/cron-bundle) [![License](https://poser.okvpn.org/okvpn/cron-bundle/license)](https://packagist.org/packages/okvpn/cron-bundle)

### Purpose
This is a more simpler alternative of existing cron bundle without doctrine deps.
Here also added support middleware for customization handling cron jobs across a cluster install: 
(Send jobs to message queue, like Symfony Messenger; locking; etc.)

Features
--------

- Not need doctrine/database.
- Load a cron job from a different storage (config.yml, tagged services, commands).
- Support many engines to run cron (in parallel process, message queue, consistently).
- Support many types of cron handlers/command: (services, symfony commands, UNIX shell commands).
- Middleware and customization.

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
with interface `Okvpn\Bundle\CronBundle\CronSubscriberInterface`
also you can do it in the configuration section, see [Cron Handlers](#cron-handlers).

```yaml

services:
    App/Cron/GCDatabase:
        tags:
            - { name: okvpn.cron, cron: '5 0 */2 * *' }

    App/Cron/YourService:
        tags:
            - { name: okvpn.cron, cron: '*/5 * * * *', lock: true, arguments: {'arg1': 5}, async: true }

```

where:

- `cron` - *(Optional)* A cron expression, if empty, the command will run always.
- `lock` - *(Optional)* Prevent to run the command again, if prev. command is not finished yet.
To use it required symfony [lock component](https://symfony.com/doc/4.4/components/lock.html) 
- `async` - *(Optional)* Run command in the new process without blocking main thread
- `arguments` - *(Optional)* Array command of arguments. 
- `lockName` - *(Optional)* Lock name, see symfony lock component
- `lockTtl` - *(Optional)* Set ttl (Time To Live) for expiring locks.
- `priority` - *(Optional)* Sorting priority.

## Cron Handlers.


#### Service Handler.

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

```

#### Symfony console Handler

```yaml
services:
    App\Command\CronCommand:
        tags:
            - { name: console.command }
            - { name: okvpn.cron, cron: '*/5 * * * *' }
```

#### Via configuration

```yaml
okvpn_cron:
  tasks:
    clearcache:
      command: "php %kernel_project.dir%/bin/console cache:clear --env=prod" # Shell command 
      cron: "0 0 * * *"
    letsenecrypt:
      command: "bash /root/renew.sh > /root/renew.txt" # Shell command 
      cron: "0 0 * * *"
    memorygc:
      command: "App\Cron\MemoryGC" # Your service name
      cron: "0 0 * * *"
```

## Configuration options.

### Default options.

You can add any default option to all scheduled tasks, for example to always run commands with locking and asynchronously.

```yaml
# Your config.yml
okvpn_cron:
    default_options:
        async: true # Default false
        lock: true # Default false 
```

## Custom Scheduled Tasks Loaders

//TODO: Add description.
```php

```

License
---

MIT License.

