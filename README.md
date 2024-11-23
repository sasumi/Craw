# Simple Craw library
> The current library is based on PHP CURL extension and tested in PHP7.2 or above environment.
> In view of ethical standards, please do not use this library to illegally obtain network resources without permission.

## Install

1. PHP version is greater than or equal to 7.2
2. Extensions must be installed: mb_string, curl, json, dom, tidy, iconv

Please use Composer to install:
```shell script
composer require lfphp/craw
```

## Usage

### 1. CURL method to obtain content
```php
<?php
use function LFPhp\Craw\craw_curl_get_cache;

//Introduce class library to automatically load files
include 'autoload.php';

//Get content using CURL method
$result = crawl_curl_get_cache('https://www.baidu.com');

//Print results
echo "Execution result:", var_dump($result), PHP_EOL;
echo "Get content:", $result;
```
//Document to be improved
