<?php
require_once './inject.php';
require_once './curl.php';

$injectHeaders = injectOpenTracing('test-opentracing', 'opentracing1');

$method = 'GET';
$url = 'localhost/opentracing1.php';
$res = curlSend($url, [], $injectHeaders, 'GET');