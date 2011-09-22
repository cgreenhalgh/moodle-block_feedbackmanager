<?php 

// Manage the Organisational data definitions, i.e. imported curriculum definitions.
// (based on structure of rss_client/managefeeds.php)

require_once('../../config.php');
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
echo '<hr>';
echo '<div>';

// nice to show properly scoped counts...

// year
$orgyear = null;
if ($yearid) {
	$orgyear = $DB->get_record('block_feedbackmgr_orgtime', array('id'=>$yearid));
	if (!$orgyear)
		print_error('yearunknown', 'feedbackmgr');
	$urlparams2 = $urlparams; // clone
	unset($urlparams2['year']);
	$url = new moodle_url('/blocks/feedbackmgr/manageorgdata.php', $urlparams2);		
	echo '<p>Year: '.$year->year.' (<a href="'.$url.'">any year</a>)</p>';
} else {
	echo '<p>Years/sessions:</p>';
	echo '<ul>';
	$orgyears = $DB->get_records_select('block_feedbackmgr_orgtime','term IS NULL',null,'year ASC');
	foreach ($orgyears as $orgyear) {
		// nice to scope by site/unit if specified...
		$ncourses = $DB->count_records('block_feedbackmgr_orgcourse',array('year'=>$orgyear->year));
		$urlparams2 = $urlparams; // clone
		$urlparams2['year'] = $orgyear->id;
		$url = new moodle_url('/blocks/feedbackmgr/manageorgdata.php', $urlparams2);		
		echo '<li><a href="'.$url.'">'.$orgyear->year.' ('.$ncourses.' courses)</a></li>';
	}
	echo '</ul>';
}

// school
$orgunit = null;
if ($unitid) {
	$orgunit = $DB->get_record('block_feedbackmgr_orgunit', array('id'=>$unitid));
	if (!$orgunit)
		print_error('unitunknown', 'feedbackmgr');
	$urlparams2 = $urlparams; // clone
	unset($urlparams2['unit']);
	$url = new moodle_url('/blocks/feedbackmgr/manageorgdata.php', $urlparams2);		
	echo '<p>School/department: '.$orgunit->title.' (<a href="'.$url.'">any school/department</a>)</p>';
} else {
	echo '<p>Schools/departments:</p>';
	echo '<ul>';
	$orgunits = $DB->get_records('block_feedbackmgr_orgunit',null,'title ASC');
	foreach ($orgunits as $orgunit) {
		//$ncourses = $DB->count_records('block_feedbackmgr_orgcourse',array('orgcourseid'=>$orgunit->id));
		//echo '<li>'.$orgunit->title.' ('.$ncourses.' courses, sourcedid='.$orgunit->sourcedid.')</li>';
		$urlparams2 = $urlparams; // clone
		$urlparams2['unit'] = $orgunit->id;
		$url = new moodle_url('/blocks/feedbackmgr/manageorgdata.php', $urlparams2);		
		echo '<li><a href="'.$url.'">'.$orgunit->title.' (sourcedid='.$orgunit->sourcedid.')</a></li>';
	}
	echo '</ul>';
}

// site
$orgiste = null;
if ($siteid) {
	$orgsite = $DB->get_record('block_feedbackmgr_orgsite', array('id'=>$siteid));
	if (!$orgsite)
		print_error('siteunknown', 'feedbackmgr');
	$urlparams2 = $urlparams; // clone
	unset($urlparams2['site']);
	$url = new moodle_url('/blocks/feedbackmgr/manageorgdata.php', $urlparams2);
	echo '<p>Site: '.$orgsite->code.' (<a href="'.$url.'">any site</a>)</p>';
} else {
	echo '<p>Sites:</p>';
	echo '<ul>';
	$orgsites = $DB->get_records('block_feedbackmgr_orgsite',null,'title ASC');
	foreach ($orgsites as $orgsite) {
		$urlparams2 = $urlparams; // clone
		$urlparams2['site'] = $orgsite->id;
		$url = new moodle_url('/blocks/feedbackmgr/manageorgdata.php', $urlparams2);		
		echo '<li><a href="'.$url.'">'.$orgsite->code.'</a></li>';
	}
	echo '</ul>';
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
