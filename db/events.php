<?php
$observers = array (
		
		array (
				'eventname' => '\core\event\user_created',
				'callback' => 'mod_newsletter_observer::user_created' 
		),
		array (
				'eventname' => '\core\event\role_assigned',
				'callback' => 'mod_newsletter_observer::role_assigned' 
		),
		array (
				'eventname' => '\core\event\user_deleted',
				'callback' => 'mod_newsletter_observer::user_deleted' 
		),
		array (
				'eventname' => '\core\event\user_enrolment_deleted',
				'callback' => 'mod_newsletter_observer::user_enrolment_deleted' 
		) 
)
;

/**
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
 */