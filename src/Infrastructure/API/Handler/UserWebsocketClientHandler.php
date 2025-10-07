<?php

namespace OpenCCK\Infrastructure\API\Handler;

use Amp\ByteStream\StreamException;
use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;
use Amp\Websocket\WebsocketCloseCode;
use Amp\Websocket\WebsocketClosedException;
use Amp\Websocket\Server\WebsocketClientHandler;
use Amp\Websocket\Server\WebsocketGateway;
use Amp\Websocket\WebsocketClient;
use Exception;

use OpenCCK\App\Controller\AbstractController;
use OpenCCK\App\Event\ChatSaveEvent;
use OpenCCK\Infrastructure\API\App;
use OpenCCK\Infrastructure\API\WebsocketClientGateway;
use OpenCCK\Domain\Channel\ChatChannel;
use OpenCCK\Infrastructure\EventDispatcher\EventDispatcher;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use function Amp\async;
use function Amp\Future\await;
use function OpenCCK\getEnv;

final class UserWebsocketClientHandler implements WebsocketClientHandler {
    private EventDispatcher $dispatcher;
    //    /**
    //     * @var AbstractChannel[]
    //     */
    //    private array $channels;

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly MapperInterface $mapper,
        private readonly WebsocketGateway $gateway = new WebsocketClientGateway()
    ) {
        $this->dispatcher = App::getInstance()->getDispatcher();
        $this->dispatcher->addEventListener(
            ChatSaveEvent::class,
            ChatChannel::getInstance(mapper: $this->mapper)->getEventHandler()
        );
    }

    /**
     * @throws WebsocketClosedException
     * @throws StreamException
     * @throws Exception
     */
    public function handleClient(WebsocketClient $client, Request $request, Response $response): void {
        $this->logger->notice('handleClient', [$client->getId(), $request->getHeaders()]);

        //$chatChannel = $this->channels[ChatChannel::class];
        //$chatChannel->addClient($client);
        $request->setAttribute(WebsocketClient::class, $client);

        $client->onClose(function () use ($client) {
            //$this->dispatcher->removeEventListener(ChatSaveEvent::class, $chatChannel->getEventHandler());
            $this->logger->notice('closeClient', [$client->getCloseInfo()->getReason(), $client->getRemoteAddress()]);
        });

        /** @var Session $session */
        $session = $request->getAttribute(Session::class);
        if (!$session->isLocked()) {
            $session->lock();
        }
        if (!$session->get('user')) {
            $client->close(WebsocketCloseCode::NORMAL_CLOSE, 'User session required');
            $session->destroy();
            return;
        }
        $session->commit();

        $this->gateway->addClient($client);

        while ($data = $client->receive()) {
            $executions = [];
            $controllers = [];
            if (!$session->isLocked()) {
                $session->lock();
            }
            if ($json = json_decode($data->buffer())) {
                if (is_iterable($json)) {
                    foreach ($json as $jsonRequest) {
                        $parts = explode('.', $jsonRequest->method);
                        $controllers[$parts[0]] ??= $this->getController($request, $session, $parts[0]);
                        $executions[$parts[0]][] = $jsonRequest;
                    }
                    $executions = array_map(
                        fn($controllerName, $data) => $this->mapper::getExecutions(
                            $controllers[$controllerName],
                            $data,
                            'default'
                        ),
                        array_keys($executions),
                        $executions
                    );
                    $executions = array_merge(...$executions);
                }
            } else {
                $executions[] = [
                    'id' => null,
                    'closure' => fn() => $this->mapper::result(
                        $this->getController($request, $session)->execute('default')
                    ),
                ];
            }

            $results = await(
                array_map(
                    fn($execution) => async($execution['closure'])->catch(
                        fn(Throwable $e) => $this->mapper::error(
                            array_merge(
                                ['message' => $e->getMessage(), 'code' => $e->getCode()],
                                getEnv('DEBUG') === 'true'
                                    ? ['file' => $e->getFile() . ':' . $e->getLine(), 'trace' => $e->getTrace()]
                                    : []
                            ),
                            $execution['id']
                        )
                    ),
                    $executions
                )
            );
            $session->commit();

            $executions = null;
            $controllers = null;
            ksort($results);
            $client->sendText($this->mapper::mapBody($results));
            // $this->gateway->broadcast($message);
        }
    }

    /**
     * @param Request $request
     * @param Session $session
     * @param string $controllerName
     * @return AbstractController
     * @throws Exception
     */
    protected function getController(
        Request $request,
        Session $session,
        string $controllerName = 'users'
    ): AbstractController {
        $className = '\\OpenCCK\\App\\Controller\\' . ucfirst($controllerName) . 'Controller';
        if (!class_exists($className)) {
            throw new Exception('Controller not found', HttpStatus::NOT_FOUND);
        }
        return new $className($request, $this->mapper, $session);
    }
}
