<?php
require_once './inject.php';
require_once './curl.php';

$injectHeaders = injectOpenTracing('test-opentracing');
$method = 'GET';
$url = 'https://www.baidu.com';
$res = curlSend($url, [], $injectHeaders, 'GET');