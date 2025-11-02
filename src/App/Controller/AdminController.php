<?php

namespace OpenCCK\App\Controller;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;
use Amp\Websocket\WebsocketClient;

use OpenCCK\App\Helper\Helper;
use OpenCCK\Domain\Entity\EntityInterface;
use OpenCCK\Domain\Entity\User;
use OpenCCK\Domain\Repository\AbstractRepository;
use OpenCCK\Domain\Repository\UserRepository;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Model\AbstractModel;
use OpenCCK\Infrastructure\Model\CountryModel;
use OpenCCK\Message;

use Throwable;
use Exception;

use function OpenCCK\dbg;
use function React\Async\await;

abstract class AdminController extends AbstractController {
    protected Session $session;
    protected User $user;
    protected ?WebsocketClient $client;

    protected Helper $helper;
    protected AbstractRepository $repository;
    protected AbstractModel $model;

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

        if ($request->hasAttribute(WebsocketClient::class)) {
            $this->client = $request->getAttribute(WebsocketClient::class);
        }

        if ($userId = $this->session->get('user')) {
            $repository = new UserRepository();
            /** @var User $user */
            $user = $repository->get(['id' => (int) $userId]);
            if (!in_array($user->getRole(), ['manager', 'admin', 'superadmin'])) {
                throw new Exception(Message::USER_ACCESS_ERROR, HttpStatus::FORBIDDEN);
            }
            $this->user = $user;
        } else {
            throw new Exception(Message::USER_ACCESS_ERROR, HttpStatus::FORBIDDEN);
        }
    }

    /**
     * @param Input $params
     * @return array
     * @throws Throwable
     * @throws \Doctrine\DBAL\Exception|Throwable
     */
    public function read(Input $params): array {
        $limit = $params->get('itemsPerPage', 100, Input\Filter::INT);
        $page = $params->get('page', 1, Input\Filter::INT);
        $offset = ($page - 1) * $limit;
        $order = $this->helper->generateSortOrderMap($params, 'sortBy');
        $search = $params->get('search', '', Input\Filter::STR);
        $where = array_filter($this->helper->extractPropertiesByKey((array) $params), fn(string $val) => strlen($val));

        return [
            'items' => $this->model
                ->getList(where: $where, search: $search, order: $order, limit: $limit, offset: $offset)
                ->toArray(),
            'count' => $this->model->getListCount(where: $where, search: $search),
        ];
    }

    /**
     * @param Input $params
     * @return array
     * @throws Throwable
     * @throws \Doctrine\DBAL\Exception|Throwable
     */
    public function get(Input $params): array {
        $id = $params->get('id', 0, Input\Filter::INT);
        return $id ? $this->repository->get(['id' => $id])->toArray() : $this->repository->create()->toArray();
    }

    /**
     * @param Input $params
     * @return int
     * @throws Throwable
     */
    public function create(Input $params): int {
        return $this->repository->save($this->repository->create((array) $params), true);
    }

    /**
     * @param Input $params
     * @return bool
     * @throws Exception
     * @throws Throwable
     */
    public function update(Input $params): bool {
        $entity = $this->repository->get(['id' => $params->get('id', 0, Input\Filter::INT)]);
        return $this->repository->patch($entity, (array) $params);
    }

    /**
     * @param Input $params
     * @return bool
     * @throws Exception
     * @throws Throwable
     */
    public function delete(Input $params): bool {
        $ids = $params->get('ids', [], Input\Filter::ARRAY);
        foreach ($ids as $id) {
            $this->repository->delete($this->repository->get(['id' => $id]));
        }
        return true;
    }
}
