<?php

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

	$link ='<a href="'.$CFG->wwwroot.'/blocks/feedbackmgr/manageorgdata.php">'.get_string('manageorgdata', 'block_feedbackmgr').'</a>';
	$settings->add(new admin_setting_heading('block_feedbackmgr_manageorgdataheading', '', $link));
	
}

