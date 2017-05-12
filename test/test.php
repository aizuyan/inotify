<?php
require "../vendor/autoload.php";

$obj = new Aizuyan\Inotify\Inotify();

$obj->addExclude([
    "/swp$/",
    "/swpx$/",
    "/~$/",
    "/\d$/",
    "/swx$/"
])->setCallback(function ($item){
    echo $item["event"] . " 文件 " . $item["file"] . "\n";
})->addPaths("/datas/git/")->start();
