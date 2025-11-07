<?php

namespace OpenCCK\App\Controller;

use Amp\Http\Server\Request;
use Amp\Http\Server\Session\Session;

use Doctrine\DBAL\Exception;
use OpenCCK\App\Helper\Helper;
use OpenCCK\Domain\Entity\User;
use OpenCCK\Domain\Factory\RelationFactory;
use OpenCCK\Domain\Repository\TreeRepository;
use OpenCCK\Domain\Repository\RelationRepository;
use OpenCCK\Infrastructure\API\Input;
use OpenCCK\Infrastructure\Mapper\MapperInterface;
use OpenCCK\Infrastructure\Model\TreeModel;
use Throwable;

class RelationsController extends AbstractController {
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

    public function addRelation(Input $input) {
        $repository = new RelationRepository();
        $factory = new RelationFactory();

        $peoples = $input->get('peoples', [], Input\Filter::ARRAY);
        if (count($peoples) < 2 || empty($peoples) || count($peoples) >3) {
            return false;
        } elseif (count($peoples) === 2) {
            $relation = $factory->create([
                'type'=>$input->get('type', 'unknown', Input\Filter::STR),
                'peoples_id_from'=>$peoples[0],
                'peoples_id_to'=>$peoples[1],
                'tree_id'=>$input->get('tree_id', 0,Input\Filter::INT),
            ]);
            $repository->save($relation);
        } elseif(count($peoples) === 3) {
            $relation = $factory->create([
                'type'=>'marriage',
                'peoples_id_from'=>$peoples[0],
                'peoples_id_to'=>$peoples[1],
                'tree_id'=>$input->get('tree_id', 0,Input\Filter::INT),
            ]);
            $repository->save($relation);
            $relation = $factory->create([
                'type'=>$input->get('type', 'unknown', Input\Filter::STR),
                'peoples_id_from'=>$peoples[0],
                'peoples_id_to'=>$peoples[2],
                'tree_id'=>$input->get('tree_id', 0,Input\Filter::INT),
            ]);
            $repository->save($relation);
            $relation = $factory->create([
                'type'=>$input->get('type', 'unknown', Input\Filter::STR),
                'peoples_id_from'=>$peoples[1],
                'peoples_id_to'=>$peoples[2],
                'tree_id'=>$input->get('tree_id', 0,Input\Filter::INT),
            ]);
            $repository->save($relation);
        }

    }
}
