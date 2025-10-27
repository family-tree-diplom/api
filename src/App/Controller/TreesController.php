<?php

namespace OpenCCK\App\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;

use Doctrine\DBAL\Exception;
use OpenCCK\App\Helper\Helper;
use OpenCCK\Domain\Entity\User;
use OpenCCK\Domain\Repository\TreeRepository;
use OpenCCK\Domain\Repository\UserRepository;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Model\TreeModel;
use Throwable;

class TreesController extends AbstractController {
    private Helper $helper;

    public function __construct(Request $request, MapperInterface $mapper, Session $session, ?array $headers = null) {
        parent::__construct($request, $mapper, $session, $headers);

        $this->helper = new Helper();
    }

    /**
     * @return array
     * @throws Exception
     * @throws Throwable
     */
    public function default(): array {
        $repository = new TreeRepository();
        $model = new TreeModel();
        $trees = $repository->read([], null, 0, []);
        return $trees;
    }

    public function getTree(Input $input) {
        $repository = new TreeRepository();
        $tree = $repository->read(['slug' => $input->get('slug', '', Input\Filter::STR)], null, 0, []);
        return $tree;
    }
}
