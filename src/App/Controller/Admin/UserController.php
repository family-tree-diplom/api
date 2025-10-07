<?php

namespace OpenCCK\App\Controller\Admin;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;

use Doctrine\DBAL\Exception;
use OpenCCK\App\Controller\AdminController;
use OpenCCK\Domain\Entity\User;
use OpenCCK\Domain\Repository\UserRepository;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Model\UserModel;
use OpenCCK\Message;
use Throwable;

class UserController extends AdminController {
    public function __construct(Request $request, MapperInterface $mapper, Session $session, ?array $headers = null) {
        parent::__construct($request, $mapper, $session, $headers);

        if (!in_array($this->user->getRole(), ['admin', 'superadmin'])) {
            throw new Exception(Message::USER_ACCESS_ERROR, HttpStatus::FORBIDDEN);
        }

        $this->repository = new UserRepository();
        $this->model = new UserModel();
    }

    /**
     * @param Input $params
     * @return bool
     * @throws Throwable
     */
    public function setPassword(Input $params): bool {
        /** @var User $user */
        $user = $this->repository->get(['id' => $params->get('id', 0, Input\Filter::INT)]);
        $user->setPassword($params->get('password', '', Input\Filter::STR));

        if ($this->user->getRole() != 'superadmin' && in_array($user->getRole(), ['admin', 'superadmin'])) {
            throw new Exception(Message::USER_ACCESS_ERROR, HttpStatus::FORBIDDEN);
        }

        return !!$this->repository->update($user);
    }

    //    public function read(Input $params): array {
    //        return parent::read($params);
    //    }

    public function create(Input $params): int {
        /** @var User $user */
        $user = $this->repository->create((array) $params);
        if ($this->user->getRole() != 'superadmin' && in_array($user->getRole(), ['admin', 'superadmin'])) {
            throw new Exception(Message::USER_ACCESS_ERROR, HttpStatus::FORBIDDEN);
        }
        return parent::create($params);
    }

    //    public function get(Input $params): array {
    //        return parent::get($params);
    //    }

    public function update(Input $params): bool {
        /** @var User $user */
        $user = $this->repository->get(['id' => $params->get('id', 0, Input\Filter::INT)]);
        if (
            $this->user->getRole() != 'superadmin' &&
            in_array($params->get('role', 'user', Input\Filter::STR), ['admin', 'superadmin'])
        ) {
            throw new Exception(Message::USER_ACCESS_ERROR, HttpStatus::FORBIDDEN);
        }
        return parent::update($params);
    }

    public function delete(Input $params): bool {
        $ids = $params->get('ids', [], Input\Filter::ARRAY);
        foreach ($ids as $id) {
            /** @var User $user */
            $user = $this->repository->get(['id' => $id]);
            if ($this->user->getRole() != 'superadmin' && in_array($user->getRole(), ['admin', 'superadmin'])) {
                throw new Exception(Message::USER_ACCESS_ERROR, HttpStatus::FORBIDDEN);
            }
            $this->repository->delete($this->repository->get(['id' => $id]));
        }
        return true;
    }
}
