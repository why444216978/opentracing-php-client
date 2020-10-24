<?php
require_once './inject.php';
require_once './curl.php';

$injectHeaders = injectOpenTracing('test-opentracing', 'opentracing2');
$method = 'GET';
$url = 'localhost/opentracing2.php';
$res = curlSend($url, [], $injectHeaders, 'GET');

$injectHeaders = injectOpenTracing('test-opentracing', 'opentracing2');
$method = 'GET';
$url = 'localhost/opentracing2.php';
$res = curlSend($url, [], $injectHeaders, 'GET');