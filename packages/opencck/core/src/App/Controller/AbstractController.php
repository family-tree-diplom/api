<?php

namespace OpenCCK\App\Controller;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Response;
use Amp\Http\Server\Session\Session;

use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\Mapper\MapperInterface;

use Throwable;
use Exception;
use function OpenCCK\dbg;

abstract class AbstractController implements AbstractControllerInterface {
    private int $httpStatus = HttpStatus::OK;

    /**
     * @param Request $request
     * @param MapperInterface $mapper
     * @param Session $session
     * @param ?array $headers
     * @throws Throwable
     */
    public function __construct(
        protected Request $request,
        protected MapperInterface $mapper,
        protected Session $session,
        protected ?array $headers = null
    ) {
    }

    public function execute(string $method, array|object $params = []): mixed {
        return call_user_func([$this, $method], new Input((array) $params));
    }

    /**
     * @throws Exception
     */
    public function __call(string $method, array $arguments = []): mixed {
        throw new Exception('Method ' . $method . ' not found', HttpStatus::METHOD_NOT_ALLOWED);
    }

    /**
     * @return Response
     */
    public function getResponse(): Response {
        $body = $this->mapper::map($this);
        $headers = $this->headers ?? $this->mapper::getDefaultHeaders();
        return new Response(status: $this->httpStatus, headers: $headers, body: $body);
    }

    public function getRequest(): Request {
        return $this->request;
    }

    public function setHeaders(array $headers): AbstractController {
        $this->headers = $headers;
        return $this;
    }

    public function redirect(string $url, bool $permanently = false): void {
        $this->httpStatus = $permanently ? HttpStatus::MOVED_PERMANENTLY : HttpStatus::SEE_OTHER;
        $this->headers = array_merge($this->headers ?? [], ['location' => $url]);
    }

    /**
     * @return string
     */
    public function getBaseURL(): string {
        $schemePort = ['http' => 80, 'https' => 443];
        return $this->request->getUri()->getScheme() .
            '://' .
            $this->request->getUri()->getHost() .
            ($schemePort[$this->request->getUri()->getScheme()] !== $this->request->getUri()->getPort()
                ? ':' . $this->request->getUri()->getPort()
                : '');
    }
}
