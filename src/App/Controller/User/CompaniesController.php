<?php

namespace OpenCCK\App\Controller\User;

use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;
use OpenCCK\App\Controller\AbstractController;
use OpenCCK\App\Controller\UserController;
use OpenCCK\App\Helper\Helper;
use OpenCCK\Domain\Repository\CompanyRepository;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Model\CompanyModel;
use OpenCCK\Infrastructure\Storage\RedisCacheStorage;
use Throwable;

class CompaniesController extends UserController {
    private CompanyRepository $repository;
    private CompanyModel $model;

    public function __construct(Request $request, MapperInterface $mapper, Session $session, ?array $headers = null) {
        parent::__construct($request, $mapper, $session, $headers);

        $this->repository = new CompanyRepository();
        $this->model = new CompanyModel();
    }

    /**
     * @param Input $params
     * @return array
     * @throws Throwable
     */
    public function default(Input $params): array {
        $cache = RedisCacheStorage::getInstance();
        $params->set('companies', true);
        $hash = $this->helper->generateHash((array) $params);
        if (!($result = $cache->getData($hash))) {
            $result = $this->repository->getWithCounts(['a.state' => true]);
            $cache->setData($hash, $result, 60);
        }
        return $result;
    }
}
