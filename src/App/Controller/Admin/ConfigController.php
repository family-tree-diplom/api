<?php

namespace OpenCCK\App\Controller\Admin;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;

use Doctrine\DBAL\Exception;
use OpenCCK\App\Controller\AdminController;
use OpenCCK\Domain\Repository\ConfigRepository;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Model\ConfigModel;
use OpenCCK\Message;
use Throwable;

class ConfigController extends AdminController {
    public function __construct(Request $request, MapperInterface $mapper, Session $session, ?array $headers = null) {
        parent::__construct($request, $mapper, $session, $headers);

        if (!in_array($this->user->getRole(), ['superadmin'])) {
            throw new Exception(Message::USER_ACCESS_ERROR, HttpStatus::FORBIDDEN);
        }

        $this->repository = new ConfigRepository();
        $this->model = new ConfigModel();
    }

    /**
     * @param Input $params
     * @return array
     * @throws Exception|Throwable
     */
    public function get(Input $params): array {
        $config = $this->repository->get(['id' => $params->get('id', 'generic', Input\Filter::STR)]);
        if (!$config) {
            $config = $this->repository->create();
            $this->repository->save($config);
            return $config->toArray();
        }
        return $config->toArray();
    }

    /**
     * @param Input $params
     * @return bool
     * @throws Exception
     * @throws Throwable
     */
    public function update(Input $params): bool {
        $config = $this->repository->get(['id' => $params->get('id', 'generic', Input\Filter::STR)]);
        return $this->repository->patch($config, (array) $params);
    }
}
