# LFPhp\Craw 库
> 当前库基于PHP CURL扩展，在 PHP7.2以上环境测试。
> 鉴于到道德规范，请勿将本库用于非法获取未经允许网络资源。

## 安装

1. PHP 版本大于或等于 7.2
2. 必须安装扩展：mb_string、curl、json、dom、tidy、iconv

请使用Composer进行安装：
```shell script
composer require lfphp/craw
```

## 使用

### 1.CURL方式获取内容
```php
<?php    
use function LFPhp\Craw\craw_curl_get_cache;

//引入类库自动加载文件
include 'autoload.php';

//CURL方式获取内容
$result = craw_curl_get_cache('http://www.baidu.com');

//打印结果
echo "执行结果：", var_dump($result), PHP_EOL;
echo "获取内容：", $result;
```
//文档待完善
