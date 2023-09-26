<?php
return [
	
    'mysql' => array(
        'username'  => 'root',
        'password'      => 'root',
        // Optional parameters
        'label'     => 'MySQL',
        'driver'    => 'server',
        'databases' => array(
            'db_simpul' => 'db_simpul',
        ),
    ),
    'pgsql' => array(
        'username'  => 'root',
        'password'      => 'root',
        'label'     => 'PostgreSQL',
        'driver'    => 'pgsql',
        'databases' => array(
            'hedwig' => 'hedwig',
        ),
    ),
];