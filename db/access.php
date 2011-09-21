<?php

$capabilities = array(

	// Manage organisation/curriculum data and its mapping to Moodle categories and courses
    'block/feedbackmgr:manageorgdata' => array(

        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'manager' => CAP_ALLOW
		)
	)

	// Manage feedback in a single course (context of block?!)
// (phase 2)
//    'block/feedbackmgr:manageorgcourse' => array(
//
//        'captype' => 'write',
//        'contextlevel' => CONTEXT_BLOCK,
//        'archetypes' => array(
//            'teacher' => CAP_ALLOW,
//            'editingteacher' => CAP_ALLOW,
//            'manager' => CAP_ALLOW
//		)
//	)

);

