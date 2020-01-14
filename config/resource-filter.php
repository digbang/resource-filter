<?php

return [
    'resources' => [
        Name\Space\To\Entity::class => [
            'access-list-provider' => Name\Space\To\AccessListProvider\Implementation::class,
            // Can be null if relation is one-to-one|one-to-many|many-to-one|many-to-many|
        ],
        Name\Space\To\OtherEntity::class => [
            'access-list-provider' => null,
            // Can be null if relation is one-to-one|one-to-many|many-to-one|many-to-many|
        ]
    ],
    'users' => [
        'always-allow' => [
            //id, id, id
        ],
    ],
];
