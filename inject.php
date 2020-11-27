<?php
/**
 * jaeger客户端
 * @author weihaoyu
 */
$autoload = './vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

use Jaeger\Config;
use OpenTracing\Formats;
use OpenTracing\Reference;

class JaegerInject
{
    protected $dsn = '127.0.0.1:6831';

    protected $serviceName;
    protected $spanList = [];
    protected $client;
    protected $tracer;

    public function __construct(string $dsn = '', string $serviceName = 'test')
    {
        if ($dsn){
            $this->dsn = $dsn;
        }

        $this->serviceName = $serviceName;

        unset($_SERVER['argv']);
        $this->client = Config::getInstance();
        $this->client->gen128bit();
        $this->client::$propagator = Jaeger\Constants\PROPAGATOR_JAEGER;
        $this->tracer              = $this->client->initTracer($this->serviceName, $this->dsn);
    }

    public function resetServiceName(string $serviceName): string
    {
        $backServiceName = $this->serviceName;
        $this->tracer    = $this->client->initTracer($serviceName, $this->dsn);
        return $backServiceName;
    }

    public function getSpanName(): string
    {
        return explode('?', $_SERVER['REQUEST_URI'])[0];
    }

    public function start(string $spanName, array $headers = []): array
    {
        if (!$headers) {
            $headers = $this->getAllHeaders();
        }

        $parentContext = $this->tracer->extract(Formats\TEXT_MAP, $headers);
        if (!$parentContext) {
            $span = $this->tracer->startSpan($spanName);
        } else {
            $span = $this->tracer->startSpan($spanName, ['references' => [
                Reference::create(Reference::FOLLOWS_FROM, $parentContext),
                Reference::create(Reference::CHILD_OF, $parentContext)
            ]]);
        }

        $this->spanList[$spanName] = [
            'current_span'   => $span,
            'parent_context' => $parentContext,
        ];

        $injectHeaders = [];
        $this->tracer->inject($span->getContext(), Formats\TEXT_MAP, $injectHeaders);

        return $injectHeaders;
    }

    public function finish(string $spanName, array $spanList = [])
    {
        $info = $this->spanList[$spanName];

        $span          = $info['current_span'];
        $parentContext = $info['parent_context'];

        $span->setTag('parent', $parentContext ? $parentContext->spanIdToString() : '');
        $span->setTag('trace_id', $span->getContext()->traceIdLowToString());
        foreach ($spanList ?: [] as $k => $v) {
            $span->setTag($k, $v);
        }
        $span->finish();
    }

    public function getTraceId()
    {
        $spn = current($this->spanList)['current_span'];
        return $spn->getContext()->traceIdLowToString();
    }

    private function getAllHeaders()
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
                    $key           = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
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
                $basic_pass               = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
                $headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
            } elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
                $headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
            }
        }

        return $headers;
    }

    private function checkDsn()
    {
        if (!$this->dsn) {
            return false;
        }
        return true;
    }

    public function __destruct()
    {
        $this->client->flush();
    }
}
