<?php

namespace OpenCCK\App\Service;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Client;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpException;
use Amp\Http\Client\Response;
use Amp\Http\HttpStatus;

use Amp\Http\Tunnel\Https1TunnelConnector;
use Amp\Socket\ClientTlsContext;
use Monolog\Logger;
use OpenCCK\App\Helper\Helper;
use OpenCCK\Domain\Entity\User;
use OpenCCK\Domain\Repository\RepositoryInterface;
use OpenCCK\Infrastructure\API\App;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\API\JWT;
use OpenCCK\Infrastructure\API\Server;
use OpenCCK\Message;

use Exception;
use React\Promise\PromiseInterface;
use Throwable;

use function OpenCCK\dbg;
use function OpenCCK\genPassword;
use function OpenCCK\getEnv;
use function React\Async\await;
use function React\Async\delay;

final class MultifactorService implements ServiceInterface {
    protected HttpClient $httpClient;
    private string $baseURL = 'https://api.multifactor.ru';
    private ?Logger $logger;
    private Helper $helper;

    /**
     * @throws Throwable
     */
    public function __construct(private ?string $user = null, private ?string $password = null) {
        $this->user = $user ?? (getEnv('MULTIFACTOR_USER') ?? '');
        $this->password = $password ?? (getEnv('MULTIFACTOR_PASS') ?? '');

        $this->httpClient = (new HttpClientBuilder())->build();

        $this->logger = App::getLogger();
        $this->helper = new Helper();
    }

    /**
     * @param string $url
     * @param array $params
     * @param string $method
     * @return mixed
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     * @throws Exception
     */
    protected function httpRequest(string $url, array $params = [], string $method = 'GET'): mixed {
        $this->logger->notice($url, [$params]);

        $request = new Client\Request($url, $method);
        $request->setInactivityTimeout(60);
        $request->setTransferTimeout(60);
        $request->setTlsHandshakeTimeout(60);
        $request->setTcpConnectTimeout(60);

        if ($method === 'POST') {
            $request->setBody(json_encode($params));
        } else {
            $request->setQueryParameters($params);
        }

        $request->setHeader('Authorization', 'Basic ' . base64_encode($this->user . ':' . $this->password));
        $request->setHeader('Content-Type', 'application/json');
        $response = $this->httpClient->request($request);
        $data = $response->getBody()->buffer();
        $responseData = json_decode($data);

        return is_null($responseData) ? $data : $responseData;
    }

    /**
     * @param string $identity
     * @param int $id
     * @param string $callbackURL
     * @return mixed
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    public function request(
        string $identity,
        int $id,
        string $callbackURL = 'http://localhost:8080/api/user/auth/multifactor'
    ): mixed {
        $response = $this->httpRequest(
            $this->baseURL . '/access/requests',
            [
                'identity' => $identity,
                'claims' => ['id' => $id, 'identity' => $identity],
                'callback' => ['action' => $callbackURL, 'target' => '_self'],
            ],
            'POST'
        );

        $this->logger->notice('request', [$response]);

        return $response;
    }
}
