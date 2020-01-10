<?php

return [
    'resources' => [
        'aggregator' => Name\Space\To\Entity::class,
        'access-list-provider' => Name\Space\To\Concrete\Implementation::class, // Can be null if relation is one-to-one|one-to-many|many-to-one|many-to-many|
    ],
    'users' => [
        'always-allow' => [
            //id, id, id
        ],
    ],
];
