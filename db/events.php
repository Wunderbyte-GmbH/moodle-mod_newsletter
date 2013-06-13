<?php
$handlers = array (
    'role_assigned' => array (
        'handlerfile'      => '/mod/newsletter/lib.php',
        'handlerfunction'  => 'newsletter_role_assigned',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),

    'user_unenrolled' => array (
        'handlerfile'      => '/mod/newsletter/lib.php',
        'handlerfunction'  => 'newsletter_user_unenrolled',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),

    'user_enrolled' => array (
        'handlerfile'      => '/mod/newsletter/lib.php',
        'handlerfunction'  => 'newlsetter_user_enrolled',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),

    'user_created' => array (
        'handlerfile'      => '/mod/newsletter/lib.php',
        'handlerfunction'  => 'newsletter_user_created',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),

    'user_deleted' => array (
        'handlerfile'      => '/mod/newsletter/lib.php',
        'handlerfunction'  => 'newsletter_user_deleted',
        'schedule'         => 'instant',
        'internal'         => 1,
    ),
);
