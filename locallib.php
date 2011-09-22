<?php 
/* Local (to feedback manager) useful functions. */

defined('MOODLE_INTERNAL') || die;

/** 
 * Print current choice of or chooser for year, unit and site.
 * 
 * @param string $urlpath
 * @param array(name=>value) $urlparams
 * @param int $yearid
 * @param int $unitid
 * @param int $siteid
 */
function print_orgselector($urlpath, $urlparams, $yearid, $unitid, $siteid) {
// nice to show properly scoped counts...
	global $DB;

	// year
	$orgyear = null;
	if ($yearid) {
		$orgyear = $DB->get_record('block_feedbackmgr_orgtime', array('id'=>$yearid));
		if (!$orgyear)
		print_error('yearunknown', 'feedbackmgr');
		$urlparams2 = $urlparams; // clone
		unset($urlparams2['year']);
		$url = new moodle_url($urlpath, $urlparams2);
		echo '<p>Year: '.$orgyear->year.' (<a href="'.$url.'">any year</a>)</p>';
	} else {
		echo '<p>Years/sessions:</p>';
		echo '<ul>';
		$orgyears = $DB->get_records_select('block_feedbackmgr_orgtime','term IS NULL',null,'year ASC');
		foreach ($orgyears as $orgyear) {
			// nice to scope by site/unit if specified...
			$ncourses = $DB->count_records('block_feedbackmgr_orgcourse',array('year'=>$orgyear->year));
			$urlparams2 = $urlparams; // clone
			$urlparams2['year'] = $orgyear->id;
			$url = new moodle_url($urlpath, $urlparams2);
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
		$url = new moodle_url($urlpath, $urlparams2);
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
			$url = new moodle_url($urlpath, $urlparams2);
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
		$url = new moodle_url($urlpath, $urlparams2);
		echo '<p>Site: '.$orgsite->code.' (<a href="'.$url.'">any site</a>)</p>';
	} else {
		echo '<p>Sites:</p>';
		echo '<ul>';
		$orgsites = $DB->get_records('block_feedbackmgr_orgsite',null,'title ASC');
		foreach ($orgsites as $orgsite) {
			$urlparams2 = $urlparams; // clone
			$urlparams2['site'] = $orgsite->id;
			$url = new moodle_url($urlpath, $urlparams2);
			echo '<li><a href="'.$url.'">'.$orgsite->code.'</a></li>';
		}
		echo '</ul>';
	}
}

/**
 * Create default activities for all defined assessments in scope specified...
 * 
 * @param unknown_type $yearid
 * @param unknown_type $unitid
 * @param unknown_type $siteid
 */
function create_missing_activities($yearid, $unitid, $siteid) {
	global $DB;

	$where = array('available'=>1);
	if ($yearid) {
		$orgyear = $DB->get_record('block_feedbackmgr_orgtime', array('id'=>$yearid));
		$where['year'] = $orgyear->year;
	}
	if ($unitid)
		$where['orgunitid'] = $unitid;
	if ($siteid)
		$where['orgsiteid'] = $siteid;

	$orgcourses = $DB->get_records('block_feedbackmgr_orgcourse',$where,'year ASC, orgsiteid ASC, code ASC');

	foreach ($orgcourses as $orgcourse) {

		$orgassesses = $DB->get_records('block_feedbackmgr_orgassess',array('orgcourseid'=>$orgcourse->id), 'type ASC');
		foreach ($orgassesses as $orgassess) {
			
			$name = $orgcourse->code.' '.$orgassess->type.' ('.$orgcourse->year.')';
			
			$weightpercent = 0;
			$notpercent = 0;
			$activities = $DB->get_records('block_feedbackmgr_activity', array('orgassessid'=>$orgassess->id));
			foreach ($activities as $activity) {
				if ($activity->weightpercent===null)
					$notpercent = 1;
				else 
					$weightpercent = $weightpercent+$activity->weightpercent;
			}
			if ($notpercent) {
				echo '<p>Note: '.$name.' has activity(s) with undefined weight</p>';
			} else if ($weightpercent<100) {
				create_default_activity($orgcourse, $orgassess, 100-$weightpercent);				
			} 
			if ($weightpercent>100) {
				echo '<p>Note: '.$name.' has activities with weight > 100%</p>';		
			}
		}
		if (count($orgassesses)==0) {
			// none?!
		}
	}
	
}

/**
 * Create a default activity for an org.assessment with specified weight.
 * 
 * @param unknown_type $orgcourse
 * @param unknown_type $orgassess
 * @param unknown_type $weightpercent
 */
function create_default_activity($orgcourse, $orgassess, $weightpercent) {
	global $DB;

	$name = $orgcourse->code.' '.$orgassess->type.' ('.$orgcourse->year.')';
	echo '<p>Create default activity ('.$weightpercent.'%) for '.$name.'</p>';
	// TODO
	
}
