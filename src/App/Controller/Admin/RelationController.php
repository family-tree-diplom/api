<?php

namespace OpenCCK\App\Controller\Admin;

use Amp\Http\HttpStatus;
use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;

use Doctrine\DBAL\Exception;
use OpenCCK\App\Controller\AdminController;
use OpenCCK\Domain\Repository\RelationRepository;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Model\RelationModel;
use OpenCCK\Message;
use Throwable;

class RelationController extends AdminController {
    public function __construct(Request $request, MapperInterface $mapper, Session $session, ?array $headers = null) {
        parent::__construct($request, $mapper, $session, $headers);

        $this->repository = new RelationRepository();
        $this->model = new RelationModel();
    }
}
