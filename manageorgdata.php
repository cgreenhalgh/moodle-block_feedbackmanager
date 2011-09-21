<?php 

// Manage the Organisational data definitions, i.e. imported curriculum definitions.
// (based on structure of rss_client/managefeeds.php)

require_once('../../config.php');
//require_once($CFG->libdir . '/tablelib.php');

require_login();

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);

$urlparams = array();
if ($returnurl) {
	$urlparams['returnurl'] = $returnurl;
}
$baseurl = new moodle_url('/blocks/feedbackmgr/manageorgdata.php', $urlparams);
$PAGE->set_url($baseurl);

// system-wide setting(s)
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);

require_capability('block/feedbackmgr:manageorgdata', $context);


// standard page & heading, etc
$strmanage = get_string('manageorgdata', 'block_feedbackmgr');

$PAGE->set_pagelayout('standard');
$PAGE->set_title($strmanage);
$PAGE->set_heading($strmanage);

$settingsurl = new moodle_url('/admin/settings.php?section=blocksettingfeedbackmgr');
$manageorgdataurl = new moodle_url('/blocks/feedbackmgr/manageorgdata.php'); //, $urlparams);
$PAGE->navbar->add(get_string('blocks'));
$PAGE->navbar->add(get_string('pluginname', 'block_feedbackmgr'), $settingsurl);
$PAGE->navbar->add(get_string('manageorgdata', 'block_feedbackmgr'), $manageorgdataurl);
echo $OUTPUT->header();

// page main content...

// import? 
$importurl = new moodle_url('/blocks/feedbackmgr/importorgdata.php');
echo '<div class="maincontent">' . html_writer::link($importurl, get_string('importorgdata', 'block_feedbackmgr')) . '</div>';


// standard page footer
if ($returnurl) {
	echo '<div class="backlink">' . html_writer::link($returnurl, get_string('back')) . '</div>';
}

echo $OUTPUT->footer();
