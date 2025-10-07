<?php

namespace OpenCCK\App\Controller;

use Amp\ByteStream\StreamException;
use Amp\Http\Client;
use Amp\Redis\RedisException;
use Doctrine\DBAL\Exception;
use OpenCCK\App\Service\UserService;
use OpenCCK\AsyncTest;
use OpenCCK\Domain\Entity\User;
use OpenCCK\Domain\Repository\UserRepository;
use OpenCCK\Infrastructure\API\JWT;
use OpenCCK\Infrastructure\API\Server;
use Revolt\EventLoop;
use Throwable;
use function Amp\delay;
use function React\Async\await;

/**
 * @covers UsersController
 */
final class UsersControllerTest extends AsyncTest {
    private const CRED = [
        'username' => 'testUser',
        'password' => 'jQpAYKEaspx8qRsn2i4J',
    ];

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

    /**
     * @covers UsersController::auth()
     * @throws StreamException
     */
    function testAuth() {
        $data = $this->getResponseData($this->httpRequest('users', 'auth'));

        $this->assertIsString($data->result->session, 'Failed getting visitor session');
        $this->assertObjectHasAttribute('user', $data->result);
    }

    private function checkCookieSession(Client\Response $response) {
        $headers = $response->getHeaders();
        $setCookie = $headers['set-cookie'][0];
        $session = explode(';', substr($setCookie, 8))[0];
        $this->assertGreaterThan(0, strlen($session));
    }

    private function checkHeaderLocation(Client\Response $response, $location) {
        $headers = $response->getHeaders();
        $this->assertEquals($headers['location'][0], $location);
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    private function getUser(): ?User {
        $userRepository = new UserRepository();
        return await($userRepository->get(['username' => self::CRED['username']]));
    }

    /**
     * @covers UsersController::register()
     * @throws StreamException
     * @throws Throwable
     */
    function testRegister() {
        $userRepository = new UserRepository();
        $user = $this->getUser();
        if ($user) {
            $userRepository->delete($user);
        }

        $response = $this->httpRequest('users', 'register', self::CRED);
        $data = $this->getResponseData($response);

        $this->assertIsString($data->result->username, 'Failed getting username');
        $this->checkCookieSession($response);
    }

    /**
     * @covers UsersController::login()
     * @covers UserService::checkLogin()
     * @throws StreamException
     */
    function testLogin() {
        $response = $this->httpRequest('users', 'login', self::CRED);
        $data = $this->getResponseData($response);

        $this->assertIsString($data->result->username, 'Failed getting username');
        $this->checkCookieSession($response);
    }

    /**
     * @covers UsersController::login()
     * @covers UserService::checkLoginToken()
     * @throws StreamException
     * @throws Throwable
     */
    function testLoginToken() {
        $user = $this->getUser();
        $response = $this->httpRequest(
            'users',
            'login',
            [],
            JWT::getToken([
                'id' => $user->getId(),
                'hash' => md5($user->getDateModify()->format('Y-m-d H:i:s')),
                'exp' => time() + 60,
            ]),
            'GET'
        );
        $data = $this->getResponseData($response);

        $this->assertIsString($data->result->username, 'Failed getting username');
        $this->checkCookieSession($response);
        $this->checkHeaderLocation($response, '/profile');
        $this->assertEquals(303, $response->getStatus());
    }

    /**
     * @throws Exception
     * @throws StreamException
     * @throws Throwable
     * @covers UsersController::reset()
     * @covers UserService::reset()
     */
    function testReset() {
        $userRepository = new UserRepository();
        await($userRepository->update($this->getUser()->setEmail(self::CRED['username'] . '@kwinta.net')));

        $user = $this->getUser();
        $response = $this->httpRequest('users', 'reset', ['username' => self::CRED['username']]);
        $data = $this->getResponseData($response);

        $this->assertTrue($data->result);
    }

    /**
     * @throws Exception
     * @throws StreamException
     * @throws Throwable
     * @covers UsersController::reset()
     * @covers UserService::doReset()
     */
    function testResetToken() {
        $user = $this->getUser();
        $response = $this->httpRequest(
            'users',
            'reset',
            [],
            JWT::getToken([
                'id' => $user->getId(),
                'hash' => md5($user->getDateModify()->format('Y-m-d H:i:s')),
                'exp' => time() + 60,
            ]),
            'GET'
        );
        $data = $this->getResponseData($response);

        $this->assertTrue($data->result);
        $this->checkHeaderLocation($response, '/profile/login');
        $this->assertEquals(303, $response->getStatus());
    }
}
