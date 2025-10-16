<?php

namespace OpenCCK\App\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;

use Doctrine\DBAL\Exception;
use OpenCCK\App\Helper\Helper;
use OpenCCK\Domain\Entity\User;
use OpenCCK\Domain\Repository\PeopleRepository;
use OpenCCK\Domain\Repository\RelationRepository;
use OpenCCK\Domain\Repository\UserRepository;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Model\PeopleModel;
use Throwable;

class PeoplesController extends AbstractController {
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
        $repository = new PeopleRepository();
        $relationRepository = new RelationRepository();
        $model = new PeopleModel();
        $peoples = $repository->read([], null, 0, []);
        $relations = $relationRepository->read([], null, 0, []);
        foreach ($peoples as $people) {
            foreach($relations as $relation){
                if($people->id === $relation->peoples_id_from){
                    $people->relations[] = [
                        'type'=>$relation->type,
                        'id'=>$relation->peoples_id_to
                    ];
                }
            }
        }
        return $peoples;
    }
}
