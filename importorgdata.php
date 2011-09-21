<?php 

// Form to import organisation definitions (curriculum/assessments)
// (partly based on blocks/rss_client/editfeed.php for usage)

require_once(dirname(__FILE__) . '/../../config.php');
require_once($CFG->libdir . '/formslib.php');

class importorgdata_form extends moodleform {

	// cons
	function __construct($actionurl) {
		parent::moodleform($actionurl);
	}
	
	// form definition (overrided/specified)
	function definition() {
		$mform =& $this->_form;
		
		$maxbytes = 1000000;
		//$mform->addElement('file', 'importfile', get_string('importfile', 'block_feedbackmgr'));
		$mform->addElement('filepicker', 'importfile', get_string('importfile', 'block_feedbackmgr'), null, 
							array('maxbytes' => $maxbytes, 'accepted_types' => '*.json'));
		$mform->addRule('importfile', null, 'required');
		
		$mform->addElement('checkbox', 'replaceexisting', get_string('replaceexisting', 'block_feedbackmgr'));
		$mform->setDefault('replaceexisting', 0);
		$mform->addHelpButton('replaceexisting', 'replaceexisting', 'block_feedbackmgr');
		
		$mform->addElement('text', 'limityear', get_string('limityear', 'block_feedbackmgr'), array('size' => 10));
		$mform->setType('limityear', PARAM_TEXT);
		// optional $mform->addRule('url', null, 'required');

		$mform->addElement('text', 'limitsitecode', get_string('limitsitecode', 'block_feedbackmgr'), array('size' => 10));
		$mform->setType('limitsitecode', PARAM_TEXT);
		
		$this->add_action_buttons(true, get_string('import', 'block_feedbackmgr'));		
	}	
}

$returnurl = optional_param('returnurl', '', PARAM_LOCALURL);
$urlparams = array();
if ($returnurl) {
	$urlparams['returnurl'] = $returnurl;
}
$baseurl = new moodle_url('/blocks/feedbackmgr/importorgdata.php', $urlparams);
$PAGE->set_url($baseurl);

// system-wide setting(s)
$context = get_context_instance(CONTEXT_SYSTEM);
$PAGE->set_context($context);

require_capability('block/feedbackmgr:manageorgdata', $context);

// base page 
$PAGE->set_pagelayout('standard');

$manageorgdataurl = new moodle_url('/blocks/feedbackmgr/manageorgdata.php', $urlparams);

// form
$mform = new importorgdata_form($PAGE->url);
// no persistent data? one day we could persist the form settings as defaults...
$data = new stdClass;
$mform->set_data($data);

if ($mform->is_cancelled()) {

	//debugging('cancelled');
	
	redirect($manageorgdataurl);

} else {
	
	//debugging('show form');
	
	// form view
	$strimport = get_string('importorgdata', 'block_feedbackmgr');
	
	$PAGE->set_title($strimport);
	$PAGE->set_heading($strimport);
	
	$settingsurl = new moodle_url('/admin/settings.php?section=blocksettingfeedbackmgr');
	$PAGE->navbar->add(get_string('blocks'));
	$PAGE->navbar->add(get_string('pluginname', 'block_feedbackmgr'), $settingsurl);
	$PAGE->navbar->add(get_string('manageorgdata', 'block_feedbackmgr'), $manageorgdataurl);
	$PAGE->navbar->add($strimport);
	
	echo $OUTPUT->header();
	echo $OUTPUT->heading($strimport, 2);
	
	if ($data = $mform->get_data()) {
	
		echo '<div class="output">';
		echo '<p>Processing uploaded file...'.var_export($data,true).'</p>';
		
		$replace = 0;
		if (isset($data->replaceexisting))
			$replace = $data->replaceexisting;
		$limityear = $data->limityear;
		$limitsite = $data->limitsitecode;
		
		// this is the textual content of the file...
		$content = $mform->get_file_content('importfile');
		//debugging('Submitted '.$content);
		$json = json_decode($content);
		if (empty($json)) 
			print_error('errorinvalidjson', 'block_feedbackmgr', $baseurl);
		try {
			$header = $json->header;
			$modules = $json->modules;
			if (empty($header) || empty($modules))
				print_error('errorunknownjson', 'block_feedbackmgr', $baseurl);
			
			foreach ($modules as $module) {
				//debugging('module '.$module->code);
				
				// ensure that we have the specified Organisational Unit
				$orgunitsourcedid = $module->org->code;

				// match on string...
				$orgunit = $DB->get_record_select('block_feedbackmgr_orgunit', $DB->sql_compare_text('sourcedid')." = ?", array($orgunitsourcedid));
				if ($orgunit) {
					if ($orgunit->title!=$module->org->descr)
						if ($replace) {
							echo '<p>Update orgunit '.$orgunit->sourcedid.' from title '.$orgunit->title.' to '.$module->org->descr.'</p>';
							$orgunit->title= $module->org->descr;
							$DB->update_record('block_feedbackmgr_orgunit', $orgunit);
						} else {
							print_error('errorreplaceunit', 'block_feedbackmgr', $baseurl, array('sourcedid'=>$orgunitsourcedid));
						}
				}
				else {
					// add
					$orgunit = new stdClass();
					$orgunit->sourcedid = $orgunitsourcedid;
					$orgunit->title = $module->org->descr;
					$orgunit->id = $DB->insert_record('block_feedbackmgr_orgunit', $orgunit);
					echo '<p>Added orgunit '.$orgunit->sourcedid.' title '.$orgunit->title.'</p>';
				}
				
				// ensure that we have the specified Site
				$site_code = $module->site_code;
				$orgsiteid = null;
				if ($site_code) {
					// excluded?
					if (!empty($limitsite) && $limitsite!=$site_code) {
						echo '<p>Skip module '.$module->code.' at site '.$site_code.' (only considering '.$limitsite.')';
						continue;
					}
					// match on string...
					$orgsite = $DB->get_record_select('block_feedbackmgr_orgsite',  $DB->sql_compare_text('code')." = ?", array($site_code));
					if (!$orgsite) {
						$orgsite = new stdClass();
						$orgsite->code = $site_code;
						$orgsiteid = $DB->insert_record('block_feedbackmgr_orgsite', $orgsite);
						echo '<p>Added orgsite '.$orgsite->code.'</p>';			
					}
					else
						$orgsiteid = $orgsite->id;
				}
				
				// check orgtime(s) - year & term (semester)
				$year = $module->taught->year;
				if (!empty($limityear) && $limityear!=$year) {
					echo '<p>Skip module '.$module->code.' in year '.$year.' (only considering '.$limityear.')</p>';
					continue;						
				}
				$term = $module->taught->semester;
				$orgyear = $DB->get_record_select('block_feedbackmgr_orgtime',  $DB->sql_compare_text('year').' = ? AND term IS NULL', array($year));
				if (!$orgyear) {
					$orgyear = new stdClass();
					$orgyear->year= $year;
					$orgyear->id = $DB->insert_record('block_feedbackmgr_orgtime', $orgyear);
					echo '<p>Added year/session '.$orgyear->year.'</p>';			
				}
				if (!empty($term)) {
					$orgterm = $DB->get_record_select('block_feedbackmgr_orgtime',  $DB->sql_compare_text('year').' = ? AND '.$DB->sql_compare_text('term').' = ?', array($year,$term));
					if (!$orgterm) {
						$orgterm = new stdClass();
						$orgterm->year= $year;
						$orgterm->term = $term;
						$orgterm->id = $DB->insert_record('block_feedbackmgr_orgtime', $orgterm);
						echo '<p>Added term/semester '.$term.' '.$year.'</p>';			
					}
				}						
		
				// now the orgcourse...
				$sourcedid = $module->id;
				$neworgcourse = new stdClass();
				$neworgcourse->sourcedid = $module->id;
				$neworgcourse->orgunitid = $orgunit->id;
				if (!empty($orgsiteid))
					$neworgcourse->orgsiteid = $orgsiteid;
				$neworgcourse->title = $module->title;
				$neworgcourse->code = $module->code;
				$neworgcourse->available = $module->status=='Live' ? 1 : 0;
				$neworgcourse->status = $module->status;
				//$neworgcourse->timemodified = time();
				if (isset($header->timestamp))
					$neworgcourse->sourcetime = $header->timestamp;
				$neworgcourse->year = $year;
				$neworgcourse->term = $term;
				
				$orgcourse = $DB->get_record_select('block_feedbackmgr_orgcourse',  $DB->sql_compare_text('sourcedid').' = ?', array($sourcedid));
				if ($orgcourse) {
					if ($replace) {
						$neworgcourse->id = $orgcourse->id;
						$neworgcourse->timemodified = time();
						$DB->update_record('block_feedbackmgr_orgcourse', $neworgcourse);
						$orgcourse = $DB->get_record_select('block_feedbackmgr_orgcourse',  $DB->sql_compare_text('sourcedid').' = ?', array($sourcedid));
						echo '<p>Updated course '.$orgcourse->sourcedid.' ('.$orgcourse->code.' at '.$site_code.', '.$term.' '.$year.')</p>';
					}
					else {
						echo '<p>Ignored existing course '.$orgcourse->sourcedid.' ('.$orgcourse->code.' at '.$site_code.', '.$term.' '.$year.')</p>';
						continue;
					}
				}				
				else {
					// add
					debugging('add orgcourse '.var_export($neworgcourse, true));
					$orgcourse = $neworgcourse;
					$orgcourse->timemodified = time();
					$orgcourse->id = $DB->insert_record('block_feedbackmgr_orgcourse', $orgcourse);
					echo '<p>Added course '.$orgcourse->sourcedid.': '.$orgcourse->code.' at '.$site_code.', '.$term.' '.$year.'</p>';
				}
				
				// existing/previous assessments
				$oldassesses = $DB->get_records('block_feedbackmgr_orgassess', array('orgcourseid'=>$orgcourse->id));
				//debugging('old assessments: '.var_export($oldassesses,true));
				
				// assessments...
				foreach ($module->assess as $assess) {
					// check existing by type (ok for nottingham...)
					$newassess = new stdClass();
					$newassess->orgcourseid = $orgcourse->id;
					$newassess->type = $assess->type;
					$newassess->status = $assess->status;
					$newassess->weightpercent = $assess->percent;
					
					$assess = $DB->get_record_select('block_feedbackmgr_orgassess', 'orgcourseid = ? AND '.$DB->sql_compare_text('type').' = ?', array($orgcourse->id, $assess->type));
					if ($assess) {
						unset($oldassesses[$assess->id]);
						$newassess->id = $assess->id;
						// other fields... (later)
						$assess = $newassess;
						$DB->update_record('block_feedbackmgr_orgassess', $newassess);
						echo '<p>Updated assessment '.$assess->type.'</p>';
					} else {
						$assess = $newassess;
						$assess->id = $DB->insert_record('block_feedbackmgr_orgassess', $assess);
						echo '<p>Added assessment '.$assess->type.'</p>';
					}
				}
				
				foreach ($oldassesses as $oldassess) {
					echo '<p>Delete old assessment '.$oldassess->type.'</p>';
						
					$DB->delete_records('block_feedbackmgr_orgassess', array('id'=>$oldassess->id));
				}
				
			}
			
			echo '</div>';
				
		}
		catch(Exception $e) {
			debugging('handling upload: '.$e->getMessage().' - '.$e->getTraceAsString());
			while($e->getPrevious()) {
				$e = $e->getPrevious();
				debugging('caused by: '.$e->getMessage().' - '.$e->getTraceAsString());
			}
			print_error('errorprocessingjson', 'block_feedbackmgr', $baseurl, array('msg' => $e->getMessage()));
		}	
		//redirect($manageorgdataurl);
	
	} else {
	
		//debugging('show form');
	}
		
	// form view
	$mform->display();
	
    echo $OUTPUT->footer();
}
