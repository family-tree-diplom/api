<?php

namespace OpenCCK\App\Controller\User;

use Amp\ByteStream\BufferException;
use Amp\ByteStream\StreamException;
use Amp\Http\Client\HttpException;
use Amp\Http\Server\FormParser\Form;
use Amp\Http\Server\FormParser\FormParser;
use Amp\Http\Server\Request;
use Amp\Http\Server\Router;
use Amp\Http\Server\Session\Session;
use Doctrine\DBAL\Exception;
use OpenCCK\App\Controller\AbstractController;
use OpenCCK\App\Service\MultifactorService;
use OpenCCK\App\Service\UserService;
use OpenCCK\Domain\Entity\User;
use OpenCCK\Domain\Repository\UserRepository;
use OpenCCK\Infrastructure\API\App;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\API\JWT;
use OpenCCK\Infrastructure\Mapper\DefaultMapper;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Mapper\RequestMapper;
use Throwable;
use function OpenCCK\getEnv;
use function OpenCCK\parseBytes;
use function OpenCCK\parseUrlQuery;

/**
 * @see UsersControllerTest
 */
class AuthController extends AbstractController {
    private UserService $service;
    protected Session $session;

    /**
     * @param Request $request
     * @param MapperInterface $mapper
     * @param Session $session
     * @param ?array $headers
     * @throws Throwable
     */
    public function __construct(Request $request, MapperInterface $mapper, Session $session, ?array $headers = null) {
        // при обращении к методу через url испольуется RequestMapper
        $args = $request->getAttribute(Router::class);
        $mapper = isset($args['method']) ? new RequestMapper() : $mapper;
        parent::__construct($request, $mapper, $session, $headers);

        $user = null;
        if ($userId = $this->session->get('user')) {
            $repository = new UserRepository();
            $user = $repository->get(['id' => (int) $userId]);
        }
        $this->service = new UserService($user);
    }

    /**
     * @return mixed
     * @throws Throwable
     */
    public function default(): array {
        return [
            'session' => $this->session->getId(),
            'user' => $this->service->getUser(),
        ];
    }

    /**
     * @param Input $params
     * @return mixed
     * @throws Throwable
     * @throws BufferException
     * @throws StreamException
     * @throws HttpException
     */
    public function login(Input $params): mixed {
        $user = $this->service->checkLogin(username: $params->get('username'), password: $params->get('password'));
        return (new MultifactorService())->request(
            $user->getEmail(),
            $user->getId(),
            (getEnv('SITE_BASE_URL') ?? 'http://localhost:3000') . '/api/user/auth/multifactor'
        );
    }

    public function test()
    {
        $repository = new UserRepository();
        /** @var User $user */
        $user = $repository->get(['id'=> 1]);
        $user->setPassword('ztysw3GzQXd9agsegppX');
        $repository->update($user);
        return true;
    }

    /**
     * @return mixed
     * @throws Throwable
     */
    public function multifactor(): bool {
        $query = parseURLQuery($this->request->getUri()->getQuery());
        $form = Form::fromRequest($this->request, new FormParser(parseBytes(ini_get('post_max_size'))));
        $accessToken = $form->getValue('accessToken');
        if (!$accessToken) {
            throw new \Exception('Bad JWT token');
        }
        $payload = JWT::getPayload($accessToken, getEnv('MULTIFACTOR_PASS'));
        $sub = $payload->get('sub', '', Input\Filter::STR);

        App::getLogger()->info('Multifactor auth: ' . $accessToken);
        $repository = new UserRepository();
        /** @var User $user */
        $user =
            $repository->get(['id' => $payload->get('id', 0, Input\Filter::INT)]) ??
            ($repository->get(['email' => $payload->get('identity', '', Input\Filter::STR)]) ??
                $repository->get(['email' => $sub]));
        if (!$user) {
            App::getLogger()->notice('Multifactor auth: user not found');
            throw new \Exception('Не удалось найти пользователя ' . $sub);
        }

        $this->session->lock();
        $this->session->set('user', (string) $user->getId());
        $this->session->commit();

        $this->redirect($query['callback'] ?? '/');
        return true;
    }

    /**
     * @return bool
     */
    public function logout(): bool {
        $this->session->lock();
        $this->session->destroy();
        return true;
    }
}
