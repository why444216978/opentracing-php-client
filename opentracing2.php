<?php
require_once './inject.php';
require_once './curl.php';

$injectHeaders = injectOpenTracing('test-opentracing', 'baidu');
$method = 'GET';
$url = '127.0.0.1:777/ping';
$res = curlSend($url, [], $injectHeaders, 'GET');