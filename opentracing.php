<?php
require_once './inject.php';
require_once './curl.php';

$injectHeaders = injectOpenTracing('test-opentracing');

$method = 'GET';
$url = 'localhost/opentracing1.php';
$res = curlSend($url, [], $injectHeaders, 'GET');