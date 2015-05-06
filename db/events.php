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