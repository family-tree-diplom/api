<?php

namespace OpenCCK\App\Controller\User;

use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;
use OpenCCK\App\Controller\AbstractController;
use OpenCCK\App\Controller\UserController;
use OpenCCK\App\Helper\Helper;
use OpenCCK\Domain\Repository\PointRepository;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Model\PointModel;
use OpenCCK\Infrastructure\Storage\RedisCacheStorage;
use Throwable;

class PointsController extends UserController {
    private PointRepository $repository;
    private PointModel $model;

    public function __construct(Request $request, MapperInterface $mapper, Session $session, ?array $headers = null) {
        parent::__construct($request, $mapper, $session, $headers);

        $this->repository = new PointRepository();
        $this->model = new PointModel();
    }

    /**
     * @param Input $params
     * @return array
     * @throws Throwable
     */
    public function default(Input $params): array {
        $cache = RedisCacheStorage::getInstance();
        $hash = $this->helper->generateHash((array) $params);
        if (!($result = $cache->getData($hash))) {
            $result = $this->repository->getPoints();
            $cache->setData($hash, $result, 60);
        }
        return $result;
    }
}
