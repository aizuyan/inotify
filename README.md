# inotify
文件、文件夹递归监控变动

#### 使用例子
```php
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
```

#### 安装
要使用这个功能，需要在机器上安装inotify-tools，只能在linux\unix上使用

```sh
yum install inotify-tools
```
