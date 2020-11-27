<?php
require_once './inject.php';
require_once './curl.php';

$jaeger = new JaegerInject();

$spanName = $jaeger->getSpanName();
$injectHeaders = $jaeger->start($spanName);

$method = 'GET';
$url = 'http://localhost:9999/opentracing1.php';
$res = curlSend($url, [], $injectHeaders, 'GET');

$jaeger->finish($spanName, ['time' => date('Y-m-d H:i:s')]);


