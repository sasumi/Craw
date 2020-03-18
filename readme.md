# Craw 库
> 当前库基于PHP CURL扩展，在 PHP5.6以上环境测试。
> 鉴于到道德规范，请勿将本库用于非法获取未经允许网络资源。

## 快速入门

### 1.CURL方式获取内容
```php
<?php    
use Craw\Http\Curl;

//引入类库自动加载文件
include 'autoload.php';

//CURL方式获取内容
$result = Curl::getContent('http://www.baidu.com');

//打印结果
echo "执行结果：", $result->getResultMessage(), PHP_EOL;
echo "获取内容：", $result;
```
### 2.关联上下文环境方式获取内容
```php
<?php
use Craw\Context;
use Craw\Http\Proxy;
use Craw\Http\Result;use Craw\Http\UserAgent;
include 'autoload.php';

$data_list = Context::create() //创建上下文
    ->autoUpdateCookieOn() //设置自动更新Cookie（缺省自动更新）
    ->autoUpdateRefererOn() //设置自动更新Referer（缺省自动更新）
    ->setProxy(new Proxy('proxy.com', '8080')) //设置代理
    ->setUserAgent(UserAgent::chrome()) //设置浏览器UA
    ->setTimeout(10)    //设置超时时间
    ->post('www.website.com/login', ['name'=>'foo', 'password'=>'bar']) //执行登录
    ->continueWhile(function(Result $result){
        return $result->decodeAsJSON()->code == 0; //判定结果为成功，才继续后续操作
    })
    ->get('www.website.com/datalist'); //拉取目标数据

var_dump($data_list);
```

## 其他请求格式
其他请求格式请使用 ```Curl::request()``` 方法获取数据。

## 代理
代理传入```Context->setProxy()```方法对象类型为 ```Proxy```
```Proxy```对象（类）支持代理测试(```Proxy->test())，或批量并发测试代理 ```Proxy::testConcurrent()```

更多例子，请查看 ``test`` 目录下代码。

## 缓存

```Context```开启缓存方法为：```Context->cacheOn(10)```，默认使用缓存目录为系统Temp/craw_cache/目录（Liunx系统为/tmp/craw_cache目录）。可通过 ```Cache::$temp_fold_name```变量重置改目录下的子文件夹名称。若需要缓存到其他磁盘目录，可执行调用```Cache::instance($dir)```重置。

## 日志

库提供Logger方法进行简单日志记录收集。外部调用程序通过注册方法 ```Logger::register``` 进行事件处理注册。

例：

```php
<?php
use Craw\Http\Curl;
use Craw\Logger\Output\ConsoleOutput;
use Craw\Logger\Output\FileOutput;
use Craw\Logger\Logger;

require_once "autoload.php";

//打印所有日志信息到控制台（屏幕）
Logger::register(new ConsoleOutput, Logger::VERBOSE);

//记录等级大于或等于LOG的信息到文件
Logger::register(new FileOutput(__DIR__.'/log/craw.debug.log'), Logger::LOG);

//记录注册ID为Curl::class（一般使用类名作为注册ID）的所有日志信息到文件
Logger::register(new FileOutput(__DIR__.'/log/craw.curl.log'), Logger::VERBOSE, Curl::class);

//仅在发生WARN级别日志事件时记录所有等级大于或等于LOG的信息到文件
Logger::registerWhile(Logger::WARN, new FileOutput(__DIR__.'/log/craw.error.log'), Logger::LOG);

//自行处理信息
Logger::register(function($messages, $level){
	//执行处理逻辑
}, Logger::LOG);
```

