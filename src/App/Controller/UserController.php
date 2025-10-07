<?php

namespace OpenCCK\App\Controller;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;

use OpenCCK\App\Helper\Helper;
use OpenCCK\Domain\Entity\EntityInterface;
use OpenCCK\Domain\Entity\User;
use OpenCCK\Domain\Repository\AbstractRepository;
use OpenCCK\Domain\Repository\UserRepository;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Model\AbstractModel;
use OpenCCK\Message;

use Throwable;
use Exception;

use function OpenCCK\dbg;
use function React\Async\await;

abstract class UserController extends AbstractController {
    protected Session $session;
    protected User $user;

    protected Helper $helper;

    /**
     * @param Request $request
     * @param MapperInterface $mapper
     * @param Session $session
     * @param ?array $headers
     * @throws Throwable
     */
    public function __construct(Request $request, MapperInterface $mapper, Session $session, ?array $headers = null) {
        parent::__construct($request, $mapper, $session, $headers);

        $this->helper = new Helper();

        if ($userId = $this->session->get('user')) {
            $repository = new UserRepository();
            /** @var User $user */
            $user = $repository->get(['id' => (int) $userId]);
            if (!in_array($user->getRole(), ['user', 'manager', 'admin', 'superadmin'])) {
                throw new Exception(Message::USER_ACCESS_ERROR, HttpStatus::FORBIDDEN);
            }
            $this->user = $user;
        } else {
            throw new Exception(Message::USER_ACCESS_ERROR, HttpStatus::FORBIDDEN);
        }
    }
}
