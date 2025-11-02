<?php

namespace OpenCCK\App\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;

use Cycle\ORM\Service\Implementation\EntityFactory;
use Doctrine\DBAL\Exception;
use Dom\Entity;
use OpenCCK\App\Helper\Helper;
use OpenCCK\Domain\Entity\User;
use OpenCCK\Domain\Factory\PeopleFactory;
use OpenCCK\Domain\Factory\PeopleToTreeFactory;
use OpenCCK\Domain\Repository\PeopleRepository;
use OpenCCK\Domain\Repository\PeopleToTreeRepository;
use OpenCCK\Domain\Repository\RelationRepository;
use OpenCCK\Domain\Repository\TreeRepository;
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
    public function default(Input $input) {
        $relationRepository = new RelationRepository();
        $model = new PeopleModel();
        $peoples = $model->getPeoplesByTree($input->get('trees_id', 0, Input\Filter::INT));
        $relations = $relationRepository->read([], null, 0, []);
        $peoples = array_map(static function (array $p) {
            $p['id'] = (int) $p['id'];
            return (object) $p;
        }, $peoples);
        foreach ($peoples as $people) {
            foreach ($relations as $relation) {
                //                return [$people->id, $relation->peoples_id_from];
                if ($people->id === $relation->peoples_id_from) {
                    $people->relations[] = [
                        'type' => $relation->type,
                        'id' => $relation->peoples_id_to,
                    ];
                }
            }
        }
        return $peoples;
    }

    /**
     * @throws Throwable
     * @throws Exception
     */
    public function save(Input $input): bool {
        $peoples = $input->get('peoples', [], Input\Filter::ARRAY);
        $treeId = $input->get('treeId', 1, Input\Filter::INT);

        $peoples = json_decode(json_encode($peoples), true); // гарантовано переводимо у масив

        $repository = new PeopleRepository();
        $repositoryTree = new PeopleToTreeRepository();

        $factory = new PeopleFactory();
        $treeFactory = new PeopleToTreeFactory();

        foreach ($peoples as $people) {
            $person = $factory->create($people);
            $id = $repository->save($person, true);

            $tree = $treeFactory->create([
                'peoples_id' => $id,
                'trees_id' => $treeId,
            ]);

            $repositoryTree->save($tree, true);
        }

        return true;
    }

    public function deletePerson(Input $input) {
        $ids = $input->get('selectedIds', [], Input\Filter::ARRAY);
        $treeId = $input->get('treeId', 1, Input\Filter::INT);

        $repository = new PeopleRepository();
        $repositoryTree = new PeopleToTreeRepository();

        foreach ($ids as $id) {
            $trees = $repositoryTree->read(['peoples_id'=>$id]);
            if (count($trees)>1){
                $tree = $repositoryTree->get([
                    'trees_id'=>$treeId,
                    'peoples_id'=>$id,
                ]);
                $repositoryTree->delete($tree);
            }elseif (count($trees)<=1){
                $people = $repository->get(['id'=>$id]);
                $repository->delete($people);
            }
        }
        return true;
    }
}
