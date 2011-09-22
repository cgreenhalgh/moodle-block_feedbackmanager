<?php 

// Simple view of Organisational data definitions

require_once('../../config.php');
require_once('locallib.php');
//require_once($CFG->libdir . '/tablelib.php');

require_login();

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$yearid = optional_param('year', 0, PARAM_INTEGER);
$siteid = optional_param('site', 0, PARAM_INTEGER);
$unitid = optional_param('unit', 0, PARAM_INTEGER);

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
$baseurl = new moodle_url('/blocks/feedbackmgr/orgindex.php', $urlparams);
$PAGE->set_url($baseurl);

// system-wide setting(s)
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);

// no security!

// standard page & heading, etc
$strtitle = get_string('orgindex', 'block_feedbackmgr');

$PAGE->set_pagelayout('standard');
$PAGE->set_title($strtitle);
$PAGE->set_heading($strtitle);

echo $OUTPUT->header();

// page main content...
echo '<div>';

print_orgselector($urlpath, $urlparams, $yearid, $unitid, $siteid);

$orgyear = null;
if ($yearid) {
	$orgyear = $DB->get_record('block_feedbackmgr_orgtime', array('id'=>$yearid));
}
$orgunit = null;
if ($unitid) {
	$orgunit = $DB->get_record('block_feedbackmgr_orgunit', array('id'=>$unitid));
}
$orgiste = null;
if ($siteid) {
	$orgsite = $DB->get_record('block_feedbackmgr_orgsite', array('id'=>$siteid));
}


// courses
if ($orgyear && $orgunit) {
	echo '<p>Courses:</p>';
	echo '<table>';
	echo '<tr><th>Course Code</th><th>Course Title</th><th>Taught</th><th>Assessment</th></tr>';
	if ($orgsite)
		$orgcourses = $DB->get_records('block_feedbackmgr_orgcourse',array('available'=>1,'year'=>$orgyear->year,'orgunitid'=>$orgunit->id,'orgsiteid'=>$orgsite->id),'term ASC, code ASC');
	else
		$orgcourses = $DB->get_records('block_feedbackmgr_orgcourse',array('available'=>1,'year'=>$orgyear->year,'orgunitid'=>$orgunit->id),'term ASC, code ASC');
	//$term = null;
	foreach ($orgcourses as $orgcourse) {
		//if ($orgcourse->term != $term) {
		//	if ($term)
		//		echo '</ul>';
		//	$term = $orgcourse->term;
		//	echo '<li>'.$term.'</li><ul>';
		//}
		$orgassesses = $DB->get_records('block_feedbackmgr_orgassess',array('orgcourseid'=>$orgcourse->id), 'type ASC');
		foreach ($orgassesses as $orgassess) {		
			echo '<tr><td>'.$orgcourse->code.'</td><td>'.$orgcourse->title.'</td><td>'.$orgcourse->term.'</td><td>'.$orgassess->type.'</td>';
		}
		if (count($orgassesses)==0)
			echo '<tr><td>'.$orgcourse->code.'</td><td>'.$orgcourse->title.'</td><td>'.$orgcourse->term.'</td><td>NONE SPECIFIED</td>';
	}
	//if ($term)
	//	echo '</ul>';
	echo '</table>';
} 

echo '</div>';

// standard page footer
if ($returnurl) {
	echo '<div class="backlink">' . html_writer::link($returnurl, get_string('back')) . '</div>';
}

echo $OUTPUT->footer();
