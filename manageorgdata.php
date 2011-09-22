<?php 

// Manage the Organisational data definitions, i.e. imported curriculum definitions.
// (based on structure of rss_client/managefeeds.php)

require_once('../../config.php');
require_once('locallib.php');
//require_once($CFG->libdir . '/tablelib.php');

require_login();

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$yearid = optional_param('year', 0, PARAM_INTEGER);
$siteid = optional_param('site', 0, PARAM_INTEGER);
$unitid = optional_param('unit', 0, PARAM_INTEGER);
$createactivities = optional_param('createactivities', 0, PARAM_INTEGER);

$urlparams = array();
if ($returnurl) {
	$urlparams['returnurl'] = $returnurl;
}
if ($yearid)
	$urlparams['year'] = $yearid;
if ($siteid)
	$urlparams['site'] = $siteid;
if ($unitid)
	$urlparams['unit'] = $unitid;
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
echo '<div>';

// actions...?
if ($createactivities && confirm_sesskey()) {
	echo '<p>Create missing activities...</p>';
	// TODO
	create_missing_activities($yearid, $unitid, $siteid);
}

echo '</div>';

echo '<hr>';
// import? 
$importurl = new moodle_url('/blocks/feedbackmgr/importorgdata.php');
echo '<div>' . html_writer::link($importurl, get_string('importorgdata', 'block_feedbackmgr')) . '</div>';

$viewurl = new moodle_url('/blocks/feedbackmgr/orgindex.php');
echo '<div>' . html_writer::link($viewurl, get_string('orgindex', 'block_feedbackmgr')) . '</div>';

// create missing activities...
if ($yearid) {
	$urlparams2 = $urlparams;
	$urlparams2['createactivities'] = 1;
	$urlparams2['sesskey'] = sesskey();
	$createactivitiesurl = new moodle_url('/blocks/feedbackmgr/manageorgdata.php', $urlparams2);
	echo '<div>' . html_writer::link($createactivitiesurl, get_string('createactivities', 'block_feedbackmgr')) . '</div>';
} else {
	echo '<div>Select a year to have the option of creating missing activities</div>';
}

echo '<hr>';
echo '<div>';

print_orgselector('/blocks/feedbackmgr/manageorgdata.php', $urlparams, $yearid, $unitid, $siteid);

echo '</div>';

// standard page footer
if ($returnurl) {
	echo '<div class="backlink">' . html_writer::link($returnurl, get_string('back')) . '</div>';
}

echo $OUTPUT->footer();
