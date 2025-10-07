<?php

namespace OpenCCK\App\Controller;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;
use Amp\Http\Server\Session\SessionFactory;
use Doctrine\DBAL\Exception;
use OpenCCK\App\Controller\AbstractController;
use OpenCCK\Domain\Entity\User;
use OpenCCK\Domain\Repository\AsyncRepository;
use OpenCCK\Domain\Repository\UserRepository;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\API\JWT;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Task\MailTask;
use OpenCCK\Infrastructure\Task\MigrationsTask;
use OpenCCK\App\Service\UserService;
use PDO;
use Revolt\EventLoop;
use Throwable;
use function Amp\async;
use function Amp\Parallel\Worker\createWorker;

use Cycle\ORM;
use Cycle\ORM\Mapper\Mapper;
use Cycle\Schema;
use Cycle\Annotated;
use Cycle\Database;
use Cycle\Database\Config;
use function OpenCCK\parseUrlQuery;
use function React\Async\await;
use function React\Async\parallel;

/**
 * @see UsersControllerTest
 */
class UsersController extends AbstractController {
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
        parent::__construct($request, $mapper, $session, $headers);

        $user = null;
        if ($userId = $this->session->get('user')) {
            $repository = new UserRepository();
            $user = $repository->get(['id' => (int) $userId]);
        }
        $this->service = new UserService($user);
    }

    /**
     * @return array
     * @throws Throwable
     * @see UsersControllerTest::testAuth()
     */
    public function auth(): array {
        $session = $this->session;
        return ['session' => $session->getId(), 'user' => $this->service->getUser()];
    }

    /**
     * @param Input $params
     * @return User
     * @throws Throwable
     * @see UsersControllerTest::testLogin()
     */
    public function login(Input $params): User {
        $query = parseURLQuery($this->request->getUri()->getQuery());
        if (isset($query['token']) && $this->request->getMethod() == 'GET') {
            $user = $this->service->checkLoginToken($query['token']);
            $this->redirect('/profile');
        } else {
            $user = $this->service->checkLogin(username: $params->get('username'), password: $params->get('password'));
        }
        $this->session->lock();
        $this->session->set('user', (string) $user->getId());
        $this->session->commit();
        return $user;
    }

    /**
     * @return bool
     */
    public function logout(): bool {
        $this->session->lock();
        $this->session->destroy();
        return true;
    }

    /**
     * @throws Throwable
     * @throws Exception
     * @see UsersControllerTest::testRegister()
     */
    public function register(Input $params): User {
        $user = $this->service->register(username: $params->get('username'), password: $params->get('password'));
        $this->session->lock();
        $this->session->set('user', (string) $user->getId());
        $this->session->commit();
        return $user;
    }

    /**
     * @param Input $params
     * @return bool
     * @throws Exception
     * @throws Throwable
     * @see UsersControllerTest::testReset()
     */
    public function reset(Input $params): bool {
        $query = parseURLQuery($this->request->getUri()->getQuery());

        if (isset($query['token']) && $this->request->getMethod() == 'GET') {
            $this->redirect('/profile/login');
            return $this->service->doReset($query['token']);
        } else {
            return $this->service->reset($params->get('username'));
        }

        //        try {
        //
        //        } catch (\Throwable $e) {
        //            throw new \Exception('asd');
        //        }
    }

    public function password(): array {
        return [];
    }
}
