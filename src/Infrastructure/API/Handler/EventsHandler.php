<?php

namespace OpenCCK\Infrastructure\API\Handler;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\SessionFactory;
use OpenCCK\Infrastructure\API\Session\SessionMiddleware;
use Psr\Log\LoggerInterface;
use Amp\Http\Server\Middleware;

final class EventsHandler extends Handler {
    private ClosureRequestHandler $requestHandler;
    private SessionFactory $sessionFactory;

    private function __construct(LoggerInterface $logger, SessionFactory $sessionFactory) {
        $this->sessionFactory = $sessionFactory;
        $this->requestHandler = new ClosureRequestHandler(function (Request $request): Response {
            // We stream the response here, one event every 500 ms.
            return new Response(
                status: HttpStatus::OK,
                headers: ['content-type' => 'text/event-stream; charset=utf-8'],
                body: new \Amp\ByteStream\ReadableIterableStream(
                    (function () {
                        for ($i = 0; $i < 30; $i++) {
                            \Amp\delay(0.5);
                            yield "event: notification\ndata: Event {$i} Memory: " . memory_get_usage() . "\n\n";
                        }
                    })()
                )
            );
        });
    }

    public static function getInstance(LoggerInterface $logger, SessionFactory $sessionFactory): EventsHandler {
        return new self($logger, $sessionFactory);
    }

    /**
     * @return RequestHandler
     */
    public function getHandler(): RequestHandler {
        return Middleware\stack($this->requestHandler, new SessionMiddleware($this->sessionFactory));
    }
}
