# Okvpn - Cron Bundle

This bundle provides interfaces for registering scheduled tasks within your Symfony application.

### Purpose
This is a more simpler alternative of existing cron bundle without doctrine deps, 
supporting invoke a service as cron job.
Here also added support middleware for customization handling cron jobs across a cluster install: 
(Send jobs to message queue, like Symfony Messenger; locking, etc.)

Features
--------

- Not need doctrine/database.
- Load a cron job from a different storage.
- Support many engines to run cron (in parallel process, message queue, consistently).
- Support many types of cron handlers/command: (services, symfony commands, UNIX commands).
- Middleware and customization.

Usage
-----

To regularly run a set of commands from your application, configure your system to run the 
oro:cron command every minute. On UNIX-based systems, you can simply set up a crontab entry for this:

```
*/1 * * * * /path/to/php /path/to/bin/console okvpn:cron:run --env=prod > /dev/null
```

Add cron commands 

```

services:
    app.you_cron_service:
        class: App/Cron/YouService
        tags:
            - { name: okvpn.cron, cron: '*/5 * * * *', lock: true, arguments: {'arg1': 5}, async: true }

```

where:

- `cron` - A cron expression. (Optional). If empty, the command will run always.
- `lock` - Prevent to run the command again, if prev. command is not finished yet. (Optional).
To use it required symfony [lock component](https://symfony.com/doc/4.4/components/lock.html) 
- `async` - Run command async in the new process without blocking main thread
- `arguments` - Array command of arguments. (Optional).
- `lockName` - Lock name. (Optional).
- `lockTtl` - Set ttl (Time To Live) for expiring locks. (Optional).

### Cron Handlers

1. Service.

```php
<?php

namespace App\Cron;

class MyCron
{
    public function __invoke($arguments)
    {
        // processing...
    }
}
```

```
services:
    App\Cron\MyCron:
        tags:
            - { name: okvpn.cron, cron: '*/5 * * * *' }

```

2. Command

```
services:
    App\Command\CronCommand:
        tags:
            - { name: console.command }
            - { name: okvpn.cron, cron: '*/5 * * * *' }
```

### Custom cron loaders

```php
<?php declare(strict_types=1);

use Okvpn\Bundle\CronBundle\Loader\ScheduleLoaderInterface;

final class DatabaseScheduleLoader implements ScheduleLoaderInterface
{
    private $configuration;
    private $factory;

    public function __construct(array $configuration, ScheduleFactoryInterface $factory)
    {
        $this->factory = $factory;
        $this->configuration = $configuration;
    }

    /**
     * @inheritDoc
     */
    public function getSchedules(): iterable
    {
        foreach ($this->configuration as $config) {
            yield $this->factory->create($config);
        }
    }
}

```

License
---

MIT License.

