# PHP Backend Task With PCNTL (For PHP-FPM Mode)

- demo:

```php
<?php
require 'vendor/autoload.php';


$job = new \Clygin\TaskJob\TaskJob(1000);

for($i=0;$i<900;$i++) {
    $job->addTask('test');
}
$job->exec();
echo 'success';

function test()
{
    $log = new \Monolog\Logger('test');
    $log->pushHandler(new \Monolog\Handler\StreamHandler('logs/test.log',\Monolog\Logger::INFO));
    $log->info('test:'.mt_rand(1000,9999));
}
```

- Logic:
use pcntl_fork create a child process run in backend and kill it that will be a zombie process.
I use pcntl_signal(SIGCHLD, SIG_IGN); to recycle the zombie process. And I will kill the parent process in the last childprocess.
pcntl_waitpid is useless.
