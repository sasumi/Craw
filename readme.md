# Craw 库
> 当前库基于PHP CURL扩展，在 PHP5.6以上环境测试。
> 鉴于到道德规范，请勿将本库用于非法获取未经允许网络资源。

## 快速入门

```php
<?php    
use craw\Fetcher;
use craw\Logger\ScreenLogger;

//引入类库自动加载文件
include 'Craw/autoload.php';

//获取内容
$result = Fetcher::getContent('http://www.baidu.com');

//打印结果
echo "执行结果：", $result->getResultMessage(), PHP_EOL;
echo "获取内容：", $result;
```

## 策略

在获取远程网页内容时，允许脚本指定自定义策略。系统策略(`Policy` 类)支持定义以下内容：

1. 指定使用代理列表（随机其中一个）
2. 浏览器UA
3. cookie
4. 引用来源页地址（referer url)
5. 请求前等待时间、或随机时间范围
6. 允许结果最大重定向次数
7. 结果判定超时时间
8. 其他额外追加头部信息

通过自定义扩展策略，可实现针对当前爬取URL，定义其他策略项。

## 并发

在批量获取内容时，可使用 ``` Fetcher::getContents()``` 并发获取内容。同时也支持指定策略、最大同时并发数量。

## 代理

代理地址格式为：{协议}://{主机地址}:{端口}，其中协议可为：http、socks4、socks5、空 其中一种，
缺省为http。端口缺省为自动识别（http:80，socks:1080）。

合法地址格式范例：

```
192.168.1.1
192.168.1.1:8080
http://192.168.0.1:80
http://www.myproxy.com:80
socks4://192.168.1.1
socks4://192.168.1.1:8888
```

代码调用方法：

```php
<?php
use ProxyTest\ProxyTest;

include 'autoload.php';
$results = ProxyTest::instance()->addProxy('192.168.0.1:80')->testConcurrent();
var_dump($results);

$result = ProxyTest::test('socks5://192.168.1.1:1080');
echo $result;

```

更多例子，请查看 ``test`` 目录下代码。

