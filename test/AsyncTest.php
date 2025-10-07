<?php

namespace OpenCCK;

use Amp\Http\Client\HttpException;
use Amp\Http\Server\Driver\ConnectionLimitingClientFactory;
use Amp\Http\Server\Driver\ConnectionLimitingServerSocketFactory;
use Amp\Http\Server\Driver\SocketClientFactory;
use Amp\PHPUnit\AsyncTestCase;

use Amp\Http\Server\SocketHttpServer;
use Amp\Http\Server\DefaultErrorHandler;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Server\StaticContent\DocumentRoot;
use Amp\Http\Server\FormParser;

use Amp\Http\Client;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpClient;
use Amp\Http\HttpStatus;

use Amp\Log\ConsoleFormatter;
use Amp\Log\StreamHandler;
use Amp\Sync\LocalSemaphore;
use Monolog\Level;
use Monolog\Logger;

use Amp\Socket;
use Amp\ByteStream\WritableResourceStream;
use Amp\ByteStream\StreamException;
use Amp\CompositeException;
use OpenCCK\Infrastructure\API\App;
use OpenCCK\Infrastructure\API\Server;
use OpenCCK\Infrastructure\Model\DB;
use Revolt\EventLoop;
use function Amp\delay;

abstract class AsyncTest extends AsyncTestCase {
    /**
     * @var HttpClient
     */
    protected HttpClient $httpClient;

    /**
     * @var App
     */
    protected App $app;

    protected function setUp(): void {
        parent::setUp();

        //        if (!defined('PATH_ROOT')) {
        //            define('PATH_ROOT', dirname(__DIR__));
        //        }
        $clientBuilder = new HttpClientBuilder();
        $this->httpClient = $clientBuilder->followRedirects(0)->build();
        $this->app = App::getInstance();
    }

    /**
     * @throws CompositeException
     */
    protected function tearDown(): void {
        parent::tearDown();
        EventLoop::defer(fn() => $this->app->stop());
        delay(0.1);
    }

    /**
     * @param string $controllerName
     * @param string $method
     * @param array $params
     * @param ?string $token
     * @param string $httpMethod
     * @return Client\Response
     * @throws HttpException
     */
    protected function httpRequest(
        string $controllerName,
        string $method,
        array $params = [],
        string $token = null,
        string $httpMethod = 'POST'
    ): Client\Response {
        $request = new Client\Request(
            'http://127.0.0.1:' .
                ($_ENV['HTTP_PORT'] ?? 8080) .
                '/api/' .
                $controllerName .
                ($token ? '/' . $method . '?token=' . $token : ''),
            $httpMethod,
            json_encode([
                'jsonrpc' => '2.0',
                'method' => $method,
                'params' => $params,
            ])
        );
        //$request->setHeader('Token', 'J.W.T');
        $request->setHeader('Content-Type', 'application/json');
        return $this->httpClient->request($request);
    }

    /**
     * @throws StreamException
     */
    protected function getResponseData(Client\Response $response, bool $failOnError = true): mixed {
        $body = $response->getBody()->buffer();
        $responseData = \json_decode($body);
        $data = !empty($responseData) ? $responseData[0] : null;
        if (isset($data->error) && $failOnError) {
            $this->fail(print_r($data->error, true));
        }
        return $data;
    }
}
