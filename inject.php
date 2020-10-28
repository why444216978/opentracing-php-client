<?php
$autoload = './vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

use Jaeger\Config;
use OpenTracing\Formats;
use OpenTracing\Reference;

if (!function_exists('getAllHeaders')) {
    function getAllHeaders()
    {
        $headers = array();

        $copy_server = array(
            'CONTENT_TYPE'   => 'Content-Type',
            'CONTENT_LENGTH' => 'Content-Length',
            'CONTENT_MD5'    => 'Content-Md5',
        );

        foreach ($_SERVER as $key => $value) {
            if (substr($key, 0, 5) === 'HTTP_') {
                $key = substr($key, 5);
                if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
                    $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
                    $headers[$key] = $value;
                }
            } elseif (isset($copy_server[$key])) {
                $headers[$copy_server[$key]] = $value;
            }
        }

        if (!isset($headers['Authorization'])) {
            if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
                $headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
            } elseif (isset($_SERVER['PHP_AUTH_USER'])) {
                $basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }

        return $headers;
    }
}

if (!function_exists('injectOpenTracing')) {
    function injectOpenTracing($serviceName)
    {
        unset($_SERVER['argv']);
        $config = Config::getInstance();
        $config->gen128bit();
        $config::$propagator = Jaeger\Constants\PROPAGATOR_JAEGER;
        $tracer = $config->initTracer($serviceName, '127.0.0.1:6831');
        
        $injectHeaders = [];
        $parentContext = $tracer->extract(Formats\TEXT_MAP, getAllHeaders());
        if (!$parentContext) {
            //如果是首个span
           
            //创建第一个span
            $span = $tracer->startSpan($_SERVER['REQUEST_URI']);
            // //将header注入span
            // $tracer->inject($span->getContext(), Formats\TEXT_MAP, $injectHeaders);
            // //结束首个span
            // $span->finish();
            
            // //根据首个span生成第一个子span
            // $span = $tracer->startSpan($_SERVER['REQUEST_URI'], ['references' => [
            //     Reference::create(Reference::FOLLOWS_FROM, $span->getContext()),
            //     Reference::create(Reference::CHILD_OF, $span->getContext())
            // ]]);
        } else {
            $span = $tracer->startSpan($_SERVER['REQUEST_URI'], ['references' => [
                Reference::create(Reference::FOLLOWS_FROM, $parentContext),
                Reference::create(Reference::CHILD_OF, $parentContext)
            ]]);
        }
        $tracer->inject($span->getContext(), Formats\TEXT_MAP, $injectHeaders);
        
        $span->setTag('parent', $parentContext ? $parentContext->spanIdToString() : '');
        $span->setTag('http.status_code', 200);
        $span->setTag('http.method', 'GET');
        $span->setTag('http.url', $_SERVER['SCRIPT_FILENAME']);
        //$span->setTag('http.res', $res);
        $span->finish();
        $config->flush();

        return $injectHeaders;
    }
}
