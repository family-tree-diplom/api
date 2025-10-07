<?php

namespace OpenCCK\Infrastructure\API;

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
use Amp\Redis\RedisException;
use Amp\Sync\LocalSemaphore;
use Monolog\Level;
use Monolog\Logger;

use Amp\Socket;
use Amp\ByteStream\WritableResourceStream;
use Amp\ByteStream\StreamException;
use Amp\CompositeException;

use OpenCCK\AsyncTest;
use Revolt\EventLoop;
use function Amp\delay;
use function OpenCCK\dbg;

/**
 * @covers \OpenCCK\Infrastructure\API\Server
 */
final class ServerTest extends AsyncTest {
    private bool $applicationStarted = false;

    /**
     * @return void
     * @throws RedisException
     */
    protected function setUp(): void {
        parent::setUp();

        $this->app->addHandler(fn() => Server::getInstance());
        if (!$this->applicationStarted) {
            EventLoop::defer(fn() => $this->app->start());
        }
        delay(0.1);

        $this->applicationStarted = true;
    }

    function testCheckServerStatus() {
        $this->assertEquals(
            'Started',
            $this->app->getModule(Server::class)->getStatus()->name,
            'Server is not starting'
        );
    }

    /**
     * @throws StreamException
     */
    function testControllerNotFoundHttpRequest() {
        $response = $this->httpRequest('unknownName', 'missing method');
        $data = $this->getResponseData($response, false);
        $error = $data->error;
        $this->assertEquals('Controller not found', $error->message, 'HttpRequest failed');
    }
}
