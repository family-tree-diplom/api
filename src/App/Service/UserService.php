<?php

namespace OpenCCK\App\Service;

use Amp\Http\HttpStatus;

use OpenCCK\Domain\Entity\User;
use OpenCCK\Domain\Repository\AsyncRepository;
use OpenCCK\Domain\Repository\AsyncRepositoryInterface;
use OpenCCK\Domain\Repository\UserRepository;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\API\JWT;
use OpenCCK\Infrastructure\API\Server;
use OpenCCK\Message;

use Exception;
use Throwable;

use function OpenCCK\genPassword;
use function React\Async\await;

final class UserService implements ServiceInterface {
    private AsyncRepositoryInterface $repository;

    /**
     * @throws Throwable
     */
    public function __construct(private ?User $user = null) {
        $this->repository = new AsyncRepository(new UserRepository());
    }

    /**
     * @return ?User
     * @throws Exception
     */
    public function getUser(): ?User {
        return $this->user;
    }

    /**
     * @param string $username
     * @param string $password
     * @return User
     * @throws Throwable
     * @see UsersControllerTest::testLogin()
     */
    public function checkLogin(string $username, string $password): User {
        /** @var User $user */
        $user = await($this->repository->get(['username' => $username]));
        if (!$user) {
            $user = await($this->repository->get(['email' => $username]));
            if (!$user) {
                throw new Exception(Message::USER_NOT_FOUND, HttpStatus::NOT_FOUND);
            }
        }
        if (!$user->checkPassword($password)) {
            throw new Exception(Message::USER_NOT_FOUND, HttpStatus::NOT_FOUND);
        }

        return $this->user = $user;
    }

    /**
     * @param string $token
     * @return User
     * @throws Throwable
     * @throws \Doctrine\DBAL\Exception
     */
    public function checkLoginToken(string $token): User {
        $payload = JWT::getPayload($token);

        /** @var User $user */
        $user = await($this->repository->get(['id' => $payload->get('id', 0, Input\Filter::INT)]));
        if (!$user) {
            throw new Exception(Message::USER_NOT_FOUND, HttpStatus::NOT_FOUND);
        }
        if (!self::checkUserToken($user, $token)) {
            throw new Exception(Message::TOKEN_ERROR);
        }

        return $this->user = $user;
    }

    /**
     * @param string $username
     * @param string $password
     * @return User
     * @throws Throwable
     * @throws \Doctrine\DBAL\Exception
     */
    public function register(string $username, string $password): User {
        $checkUser = await($this->repository->get(['username' => $username]));
        if ($checkUser) {
            throw new Exception(Message::USER_EXIST);
        }
        if (\strlen($username) < 3) {
            throw new Exception(\sprintf(Message::USER_USERNAME_SHORT, '3'));
        }

        /** @var User $user */
        $user = $this->repository->create(['username' => $username]);
        $user->setPassword($password);

        if (!($user = await($this->repository->save($user)))) {
            throw new Exception(Message::DB_ERROR);
        }

        return $this->user = $user;
    }

    /**
     * @param User $user
     * @param ?int $expiration
     * @return string
     */
    public static function getUserToken(User $user, ?int $expiration = 3600): string {
        return JWT::getToken(
            array_merge(
                [
                    'id' => $user->getId(),
                    'hash' => md5($user->getDateModify()->format('Y-m-d H:i:s')),
                ],
                $expiration ? ['exp' => time() + $expiration] : []
            )
        );
    }

    /**
     * @param User $user
     * @param string $token
     * @return bool
     * @throws Exception
     */
    public static function checkUserToken(User $user, string $token): bool {
        $payload = JWT::getPayload($token);
        $hash = $payload->get('hash', '', Input\Filter::STR);
        if ($hash) {
            $expectedHash = md5($user->getDateModify()->format('Y-m-d H:i:s'));
            if ($hash != $expectedHash) {
                throw new Exception(Message::TOKEN_ERROR);
            }
        }

        return true;
    }

    /**
     * @param string $username
     * @return bool
     * @throws Throwable
     * @throws \Doctrine\DBAL\Exception
     * @see UsersControllerTest::testReset()
     */
    public function reset(string $username): bool {
        /** @var User $user */
        $user = await($this->repository->get(['username' => $username]));
        if (!$user) {
            throw new Exception(Message::USER_NOT_FOUND, HttpStatus::NOT_FOUND);
        }

        $noticeService = new NoticeService($user);
        $token = self::getUserToken($user);
        $link = Server::$baseURL . '/api/users/reset?token=' . $token;
        $noticeService->notice(
            subject: Message::NOTICE_RESET_SUBJECT,
            body: sprintf(Message::NOTICE_RESET_TEXT, $user->getUsername(), $link),
            options: ['url' => $link]
        );

        return true;
    }

    /**
     * @param string $token
     * @return bool
     * @throws Throwable
     * @throws \Doctrine\DBAL\Exception
     */
    public function doReset(string $token): bool {
        $payload = JWT::getPayload($token);
        /** @var User $user */
        $user = await($this->repository->get(['id' => $payload->get('id', 0, Input\Filter::INT)]));
        if (!$user) {
            throw new Exception(Message::USER_NOT_FOUND, HttpStatus::NOT_FOUND);
        }
        if (!self::checkUserToken($user, $token)) {
            throw new Exception(Message::TOKEN_ERROR);
        }

        $newPassword = genPassword();
        $user->setPassword($newPassword);

        if (await($this->repository->update($user))) {
            $noticeService = new NoticeService($user);
            $noticeService->notice(
                subject: Message::NOTICE_DO_RESET_SUBJECT,
                body: sprintf(Message::NOTICE_DO_RESET_TEXT, $user->getUsername(), $newPassword),
                options: ['url' => Server::$baseURL . '/api/users/login?token=' . self::getUserToken($user)]
            );
        }

        return true;
    }
}
