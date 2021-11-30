<?php

namespace Clygin\TaskJob;

class TaskJob
{
    protected $childProcesses = [];

    protected $childProcessesQuantity = 0;

    private $task = [];

    private $parentProcesses = [];

    public function __construct($childMaxProcessesQuantity = 10)
    {
        $this->childProcessesQuantity = $childMaxProcessesQuantity;
    }

    public function addTask($task, $args = [])
    {
        $this->task[] = ['callback' => $task, 'args' => $args];
        return $this;
    }

    public function exec()
    {
        pcntl_async_signals(true);

        pcntl_signal(SIGCHLD, SIG_IGN);

        $rest_num = count($this->task);

        if ($rest_num > $this->childProcessesQuantity) {
            return false;
        }

        foreach ($this->task as $task) {
            $callback = $task['callback'];
            $args     = $task['args'];
            $callbackSign = md5(json_encode($callback));
            if (!isset($this->childProcesses[$callbackSign])) {
                $this->childProcesses[$callbackSign] = [];
            }
            if (count($this->childProcesses[$callbackSign]) < $this->childProcessesQuantity) {
                $pid = pcntl_fork();
                if ($pid == -1) {
                    throw new \RuntimeException("fork失败了");
                } elseif ($pid) {
                    $rest_num--;
                    $this->childProcesses[$callbackSign][$pid] = true;
                    if (empty($this->parentProcesses[posix_getpid()])) {
                        $this->parentProcesses[posix_getpid()] = true;
                    }
                } else {
                    if ($args) {
                        call_user_func_array($callback, $args);
                    } else {
                        call_user_func($callback);
                    }
                    if (!empty($this->parentProcesses) && $rest_num <= 1) {
                        foreach ($this->parentProcesses as $parentProcesses => $rs) {
                            posix_kill($parentProcesses, SIGKILL);
                            pcntl_signal_dispatch();
                            unset($this->parentProcesses[$parentProcesses]);
                        }
                    }
                    posix_kill(posix_getpid(), SIGKILL);
                    pcntl_signal_dispatch();
                    exit;
                }
            } else {
                sleep(1);
            }
        }
    }
}
