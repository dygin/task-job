<?php
namespace Clygin\TaskJob;

class TaskJob
{
    protected $childProcesses = [];

    protected $childProcessesQuantity = 0;

    private $task = [];

    public function __construct($childProcessesQuantity = 10)
    {
        $this->childProcessesQuantity = $childProcessesQuantity;
    }

    public function addTask($task,$args = [])
    {
        $this->task[] = ['callback' => $task,'args' => $args];
        return $this;
    }

    public function exec()
    {
        foreach ($this->task as $task) {
            $callback = $task['callback'];
            $args     = $task['args'];
            $callbackSign = md5(json_encode($callback));
            $sign         = false;
            if (!isset($this->childProcesses[$callbackSign])) {
                $this->childProcesses[$callbackSign] = [];
            }
            while (!$sign) {
                while ($signalPid = pcntl_waitpid(-1,$status,WNOHANG)) {
                    if ($signalPid == -1) {
                        $this->childProcesses[$callbackSign] = array();
                        break;
                    } else {
                        unset($this->childProcesses[$callbackSign][$signalPid]);
                    }
                }

                if (count($this->childProcesses[$callbackSign]) < $this->childProcessesQuantity) {
                    $pid = pcntl_fork();
                    if ($pid == -1) {
                        throw new \RuntimeException("fork失败了");
                    } elseif ($pid) {
                        //父进程
                        $this->childProcesses[$callbackSign][$pid] = true;
                        $sign = true;
                    } else {
                        //子进程处理
                        if ($args) {
                            call_user_func_array($callback, $args);
                        } else {
                            call_user_func($callback);
                        }
                        //执行完就退出 预防造成僵尸进程
                        exit;
                    }
                } else {
                    sleep(1);
                }
            }
        }
    }
}