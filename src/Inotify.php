<?php
/**
 * 文件、文件夹递归监控
 */
namespace Aizuyan\Inotify;

use Aizuyan\Pipe\Pipe;

class Inotify
{
    /**
     * 监控的基本命令
     */
    protected $cmd = "inotifywait -mrq --format '%w,%e,%f'";

    /**
     * 监控的事件列表
     */
    protected $events = [
        'create',
        'delete',
        'modify',
        'moved_to'
    ];

    /**
     * 要监控的文件、文件夹列表
     */
    protected $paths = [];

    /**
     * 要监控文件的规则，为空则不进行过滤
     */
    protected $fileInclude = [];

    /**
     * 要进行排除的文件规则，为空不进行过滤，优先级高于fileInclude
     */
    protected $fileExclude = [];

    /**
     * 处理结果的函数
     */
    protected $callback = null;

    public function __construct()
    {
        $this->callback = function(array $item) {
            /**
             * item => [
             *  "file" => "/datas/git/php-inotify/READNE.md",
             *  "event" => "MODIFY"
             * ]
             *
             */
            echo $item['event'] . " : " . $item['file'] . "\n";
        };
    }

    /**
     * 添加要监控的事件
     *
     * @param $events mixed 要监控的事件
     */
    public function addEvent($events)
    {
        $events = (array)$events;
        $this->events = array_unique(array_merge($this->events, $events));
        return $this;
    }

    /**
     * 拼接命令
     *
     * @return string
     */
    protected function getCmd()
    {
        $cmd = $this->cmd . " -e " . implode(",", $this->events) . " " . implode(" ", $this->paths);
        return $cmd;
    }

    /**
     * 添加要监控的文件、文件夹
     *
     * @param $paths mixed 要监控的文件、文件夹
     */
    public function addPaths($paths)
    {
        $paths = (array)$paths;
        foreach ($paths as &$path) {
            $path = realpath($path);
        }
        $this->paths = array_unique(array_merge($this->paths, $paths));
        return $this;
    }

    /**
     * 开始进行监控
     */
    public function start()
    {
        if (empty($this->paths)) {
            throw new RuntimeException("you should add one path");
        }
        $cmd = $this->getCmd();
        $pipe = new Pipe();
        $pipe->setCmd($cmd)->setCallback(function($item){
            $parts = explode(",", $item);
            $item = [
                'file' => $parts[0] . $parts[2],
                'event' => $parts[1]
            ];
            if ($this->isHandle($item['file'])) {
                call_user_func($this->callback, $item);
            }
        })->setDelimiter("\n")->run();
    }

    /**
     * 添加要排除的文件名规则
     *
     * @param $regex mixed 
     */
    public function addExclude($regex)
    {
        $regex = (array)$regex;
        $this->fileExclude = array_unique(array_merge($this->fileExclude, $regex));
        return $this;
    }

    /**
     * 添加要包含的文件名规则
     *
     * @param $regex mixed
     */
    public function addInclude($regex)
    {
        $regex = (array)$regex;
        $this->fileInclude = array_unique(array_merge($this->fileInclude, $regex));
        return $this;
    }

    /**
     * 设置回调函数，覆盖默认回调函数
     *
     * @param $cb callable
     */
    public function setCallback(callable $cb)
    {
        $this->callback = $cb;
        return $this;
    }

    /**
     * 判断文件是否忽略掉，不做处理
     * 先验证include规则，然后验证exclude规则，后者优先级高
     *
     * @param filePath string 改动文件的路径
     * @return boolean 是否处理，返回false直接丢弃不做处理
     */
    protected function isHandle($filePath)
    {
        $pathParts = pathinfo($filePath);
        $basename = $pathParts["basename"];

        if (empty($basename)) {
            return false;
        }

        if (!empty($this->fileInclude)) {
            foreach ($this->fileInclude as $includeRegex) {
                if (preg_match($includeRegex, $basename)) {
                    break;
                }
            }
            return false;
        }

        if (!empty($this->fileExclude)) {
            foreach ($this->fileExclude as $excludeRegex) {
                if (preg_match($excludeRegex, $basename)) {
                    return false;
                }
            }
        }

        return true;
    }
}
