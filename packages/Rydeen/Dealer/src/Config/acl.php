<?php

return [
    [
        'key'   => 'rydeen',
        'name'  => 'Rydeen',
        'route' => 'admin.rydeen.dealers.index',
        'sort'  => 9,
    ],
    [
        'key'   => 'rydeen.dealers',
        'name'  => 'Dealers',
        'route' => 'admin.rydeen.dealers.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'rydeen.dealers.view',
        'name'  => 'View',
        'route' => 'admin.rydeen.dealers.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'rydeen.dealers.approve',
        'name'  => 'Approve / Reject',
        'route' => 'admin.rydeen.dealers.approve',
        'sort'  => 2,
    ],
    [
        'key'   => 'rydeen.dealers.impersonate',
        'name'  => 'Login as Dealer',
        'route' => 'admin.rydeen.dealers.impersonate',
        'sort'  => 3,
    ],
    [
        'key'   => 'rydeen.orders',
        'name'  => 'Dealer Orders',
        'route' => 'admin.rydeen.orders.index',
        'sort'  => 2,
    ],
    [
        'key'   => 'rydeen.orders.view',
        'name'  => 'View',
        'route' => 'admin.rydeen.orders.index',
        'sort'  => 1,
    ],
    [
        'key'   => 'rydeen.orders.approve',
        'name'  => 'Approve',
        'route' => 'admin.rydeen.orders.approve',
        'sort'  => 2,
    ],
    [
        'key'   => 'rydeen.orders.hold',
        'name'  => 'Hold',
        'route' => 'admin.rydeen.orders.hold',
        'sort'  => 3,
    ],
    [
        'key'   => 'rydeen.orders.cancel',
        'name'  => 'Cancel',
        'route' => 'admin.rydeen.orders.cancel',
        'sort'  => 4,
    ],
];
