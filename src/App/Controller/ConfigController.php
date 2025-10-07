<?php

namespace OpenCCK\App\Controller;

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

class ConfigController extends AbstractController {
    /**
     * @throws Throwable
     * @throws Exception
     */
    public function default() {
        $repository = new ConfigRepository();
        $response = $repository->get([]);
        return json_decode($response->data);
    }
}
