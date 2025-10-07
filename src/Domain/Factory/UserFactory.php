<?php

namespace OpenCCK\Domain\Factory;

use OpenCCK\Domain\Entity\EntityInterface;
use OpenCCK\Domain\Entity\User;
use OpenCCK\App\Response\UserResponse;
use OpenCCK\App\Response\UserResponseInterface;
use OpenCCK\Infrastructure\API\Input;

final class UserFactory implements FactoryInterface {
    /**
     * @param array $data
     * @return User
     */
    public function create(array $data = []): EntityInterface {
        $item = new Input($data);
        return new User(
            id: $item->get('id', 0, Input\Filter::INT),
            state: $item->get('state', 'active', Input\Filter::STR),
            username: $item->get('username', '', Input\Filter::STR),
            password: $item->get('password', '', Input\Filter::STR),
            sold: $item->get('sold', '', Input\Filter::STR),
            email: $item->get('email', '', Input\Filter::STR),
            role: $item->get('role', 'user', Input\Filter::STR),
            name: $item->get('name', '', Input\Filter::STR),
            date_create: \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $item->get('date_create', \date_format(new \DateTime(), 'Y-m-d H:i:s'), Input\Filter::STR)
            ),
            date_modify: \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i:s',
                $item->get('date_modify', \date_format(new \DateTime(), 'Y-m-d H:i:s'), Input\Filter::STR)
            )
        );
    }
}
