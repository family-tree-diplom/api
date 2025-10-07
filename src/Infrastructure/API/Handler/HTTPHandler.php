<?php

namespace OpenCCK\Infrastructure\API\Handler;

use Amp\Http\Cookie\CookieAttributes;
use Amp\Http\HttpStatus;
use Amp\Http\Server\FormParser\Form;
use Amp\Http\Server\FormParser\FormParser;
use Amp\Http\Server\Request;
use Amp\Http\Server\RequestHandler;
use Amp\Http\Server\RequestHandler\ClosureRequestHandler;
use Amp\Http\Server\Response;
use Amp\Http\Server\Router;
use Amp\Http\Server\Session\Session;
use Amp\Http\Server\Session\SessionFactory;
use Amp\Http\Server\Middleware;
use Amp\File;

use Exception;
use OpenCCK\Infrastructure\API\Session\SessionMiddleware;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Mapper\RequestMapper;
use function OpenCCK\parseBytes;
use function OpenCCK\getEnv;

use Psr\Log\LoggerInterface;
use Throwable;

final class HTTPHandler extends Handler implements HTTPHandlerInterface {
    private ClosureRequestHandler $requestHandler;
    private ClosureRequestHandler $uploadHandler;
    private SessionFactory $sessionFactory;

    private function __construct(
        private readonly LoggerInterface $logger,
        private readonly MapperInterface $mapper,
        SessionFactory $sessionFactory,
        array $headers = null
    ) {
        $this->sessionFactory = $sessionFactory;
        $this->requestHandler = new ClosureRequestHandler(function (Request $request) use (
            $logger,
            $mapper,
            $sessionFactory,
            $headers
        ): Response {
            /** @var Session $session */
            $session = $request->getAttribute(Session::class);
            //            if (!$session->isLocked()) {
            //                $session->lock();
            //            }
            try {
                $logger->notice('HTTP Request', [$request->getHeaders()]);
                $args = $request->getAttribute(Router::class);
                $response = $this->getController(
                    isset($args['namespace'])
                        ? ucfirst($args['namespace']) . '\\' . ucfirst($args['controller'])
                        : ucfirst($args['controller']),
                    $request,
                    $mapper,
                    $session
                )->getResponse();
            } catch (Throwable $e) {
                $logger->warning('Exception', [
                    'exception' => $e::class,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
                $response = new Response(
                    status: $e->getCode() ?: 500,
                    headers: $headers ?? $mapper::getDefaultHeaders(),
                    body: $mapper::mapBody(
                        $mapper::error(
                            array_merge(
                                ['message' => $e->getMessage(), 'code' => $e->getCode()],
                                getEnv('DEBUG') === 'true' ? ['file' => $e->getFile() . ':' . $e->getLine()] : []
                            )
                        )
                    )
                );
            }
            //            if ($session->isLocked()) {
            //                $session->commit();
            //            }

            return $response;
        });

        $this->uploadHandler = new ClosureRequestHandler(function (Request $request) use ($logger): Response {
            $mapper = new RequestMapper();
            /** @var Session $session */
            $session = $request->getAttribute(Session::class);
            //            if (!$session->isLocked()) {
            //                $session->lock();
            //            }
            try {
                $logger->notice('HTTP Upload Request', [$request->getHeaders()]);
                //$args = $request->getAttribute(Router::class);
                $response = $this->getController('Admin\\Upload', $request, $mapper, $session)->getResponse();
            } catch (Throwable $e) {
                $logger->warning('Exception', [
                    'exception' => $e::class,
                    'error' => $e->getMessage(),
                    'code' => $e->getCode(),
                ]);
                $response = new Response(
                    status: $e->getCode() ?: 500,
                    headers: $headers ?? $mapper::getDefaultHeaders(),
                    body: $mapper::mapBody(
                        $mapper::error(
                            array_merge(
                                ['message' => $e->getMessage(), 'code' => $e->getCode()],
                                getEnv('DEBUG') === 'true' ? ['file' => $e->getFile() . ':' . $e->getLine()] : []
                            )
                        )
                    )
                );
            }
            //            if ($session->isLocked()) {
            //                $session->commit();
            //            }

            return $response;
        });
    }

    public static function getInstance(
        LoggerInterface $logger,
        MapperInterface $mapper,
        SessionFactory $sessionFactory,
        array $headers = null
    ): HTTPHandler {
        return new self($logger, $mapper, $sessionFactory, $headers);
    }

    /**
     * @return RequestHandler
     * @throws Exception
     */
    public function getHandler(): RequestHandler {
        return Middleware\stackMiddleware(
            $this->requestHandler,
            new SessionMiddleware(
                $this->sessionFactory,
                CookieAttributes::default()
                    ->withExpiry(new \DateTime('+' . (getEnv('SESSION_REDIS_TIMEOUT') ?? '604800') . ' seconds'))
                    ->withSecure()
                    ->withPath('/')
                    ->withSameSite('Lax')
            )
        );
    }

    /**
     * @return RequestHandler
     */
    public function getUploadHandler(): RequestHandler {
        return Middleware\stackMiddleware($this->uploadHandler, new SessionMiddleware($this->sessionFactory));
    }

    public function getControllerHandler(string $controllerName): RequestHandler {
        return Middleware\stackMiddleware(
            new ClosureRequestHandler(function (Request $request) use ($controllerName): Response {
                /** @var Session $session */
                $session = $request->getAttribute(Session::class);
                //                if (!$session->isLocked()) {
                //                    $session->lock();
                //                }
                try {
                    $this->logger->notice('HTTP Request', [$request->getHeaders()]);
                    $response = $this->getController($controllerName, $request, $this->mapper, $session)->getResponse();
                } catch (Throwable $e) {
                    $this->logger->warning('Exception', [
                        'exception' => $e::class,
                        'error' => $e->getMessage(),
                        'code' => $e->getCode(),
                    ]);
                    $response = new Response(
                        status: $e->getCode(),
                        headers: $headers ?? $this->mapper::getDefaultHeaders(),
                        body: $this->mapper::mapBody(
                            $this->mapper::error(
                                array_merge(
                                    ['message' => $e->getMessage(), 'code' => $e->getCode()],
                                    getEnv('DEBUG') === 'true'
                                        ? ['file' => $e->getFile() . ':' . $e->getLine(), 'trace' => $e->getTrace()]
                                        : []
                                )
                            )
                        )
                    );
                }
                //                if ($session->isLocked()) {
                //                    $session->commit();
                //                }

                return $response;
            }),
            new SessionMiddleware($this->sessionFactory)
        );
    }
}
