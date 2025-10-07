<?php

namespace OpenCCK\App\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;

use Doctrine\DBAL\Exception;
use OpenCCK\App\Helper\Helper;
use OpenCCK\Domain\Entity\User;
use OpenCCK\Domain\Repository\ConfigRepository;
use OpenCCK\Domain\Repository\UserRepository;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use Throwable;

class MetaController extends AbstractController {
    private Helper $helper;

    public function __construct(Request $request, MapperInterface $mapper, Session $session, ?array $headers = null) {
        parent::__construct($request, $mapper, $session, $headers);

        $this->helper = new Helper();
    }

    /**
     * @return object
     * @throws Exception
     * @throws Throwable
     */
    public function default(): object {
        $repository = new ConfigRepository();
        return json_decode($repository->get(['id' => 'generic'])->data);
    }
}
