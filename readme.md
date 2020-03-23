# Craw 库
> 当前库基于PHP CURL扩展，在 PHP5.6以上环境测试。
> 鉴于到道德规范，请勿将本库用于非法获取未经允许网络资源。

## 安装

1. PHP 版本大于或等于 5.6
2. 必须安装扩展：mb_string、curl

请使用Composer进行安装：
```shell script
composer require lfphp/craw
```

## 使用

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

库提供fsphp\logger方法进行简单日志记录收集。
具体使用方法请参考 https://github.com/sasumi/Logger