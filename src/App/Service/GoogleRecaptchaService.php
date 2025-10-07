<?php

namespace OpenCCK\App\Service;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Client;
use Amp\Http\Client\HttpClient;
use Amp\Http\Client\HttpClientBuilder;
use Amp\Http\Client\HttpException;

use Monolog\Logger;

use OpenCCK\Infrastructure\API\App;
use Throwable;

use function OpenCCK\dbg;
use function OpenCCK\getEnv;

final class GoogleRecaptchaService implements ServiceInterface {
    protected HttpClient $httpClient;
    private string $baseURL = 'https://www.google.com/recaptcha/api/siteverify';
    private ?Logger $logger;

    /**
     * @throws Throwable
     */
    public function __construct(private ?string $secret = null) {
        $this->secret = $secret ?? (getEnv('GOOGLE_RECAPTCHA_SECRET_KEY') ?? '');
        $this->httpClient = (new HttpClientBuilder())->build();
        $this->logger = App::getLogger();
    }

    /**
     * @param string $token
     * @return bool
     * @throws BufferException
     * @throws HttpException
     * @throws StreamException
     */
    public function check(string $token): bool {
        $request = new Client\Request($this->baseURL, 'POST');
        $request->setInactivityTimeout(60);
        $request->setTransferTimeout(60);
        $request->setTlsHandshakeTimeout(60);
        $request->setTcpConnectTimeout(60);

        $form = new Client\Form();
        $form->addField('secret', $this->secret);
        $form->addField('response', $token);
        $request->setBody($form);

        $response = $this->httpClient->request($request);
        $data = $response->getBody()->buffer();
        $responseData = json_decode($data);
        if (is_null($responseData)) {
            return false;
        }

        $this->logger->debug($this->baseURL, [$token]);

        return (bool) $responseData->success;
    }
}
