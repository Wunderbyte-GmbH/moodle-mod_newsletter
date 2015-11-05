<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Internal library of functions for module newsletter
 *
 * @package    mod_newsletter
 * @copyright  2013 Ivan Šakić <ivan.sakic3@gmail.com>
 * @copyright  2015 onwards David Bogner <info@edulabs.org>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__FILE__).'/renderable.php');
require_once(dirname(__FILE__).'/CssToInlineStyles/CssToInlineStyles.php');
require_once(dirname(__FILE__).'/classes/subscription/subscription_filter_form.php');

class mod_newsletter implements renderable {

    /** @var stdClass the newsletter record that contains the settings for this newsletter instance */
    private $instance = null;

    /** @var context the context of the course module for this newsletter instance (or just the course if we are
     creating a new one) */
    private $context = null;

    /** @var stdClass the course this newsletter instance belongs to */
    private $course = null;

    /** @var stdClass the course module for this assign instance */
    private $coursemodule = null;

    /** @var stdClass the admin config for all newsletter instances  */
    private $config = null;

    /** @var mod_newsletter_renderer the custom renderer for this module */
    private $renderer = null;
    
    /** @var integer the subscription id of $USER, if subscribed  */
    private $subscriptionid = null;
    
    /** @var array of objects containing data records of newsletter issues sorted be issueid */
    private $issues = array();

    /**
     * get a newsletter object by providing the id of the newsletter table (default: mdl_newsletter)
     * @param number $newsletterid
     * @param boolean $eagerload
     * @return mod_newsletter
     */
    public static function get_newsletter_by_instance($newsletterid, $eagerload = false) {
        $cm = get_coursemodule_from_instance('newsletter', $newsletterid);
        $context = context_module::instance($cm->id);
        return new mod_newsletter($context, $eagerload);
    }

    /**
     * get a newsletter object by providing the course module id (view.php?id=xxx)
     * @param number $cmid
     * @param boolean $eagerload
     * @return mod_newsletter
     */
    public static function get_newsletter_by_course_module($cmid, $eagerload = false) {
    	$context = context_module::instance($cmid);
        return new mod_newsletter($context, $eagerload);
    }

    /**
     * Constructor for the mod_newsletter class
     * 
     * @param context_module $context
     * @param bolean $eagerload
     */
    public function __construct($context, $eagerload = false) {
        $this->context = $context;
        if ($eagerload) {
            global $DB, $PAGE, $USER;
            $this->coursemodule = get_coursemodule_from_id('newsletter', $this->context->instanceid, 0, false, MUST_EXIST);
            $this->course = $DB->get_record('course', array('id' => $this->coursemodule->course), '*', MUST_EXIST);
            $this->instance = $DB->get_record('newsletter', array('id' => $this->get_course_module()->instance), '*', MUST_EXIST);
            $this->config = get_config('mod_newsletter');
        }
    }

    /**
     * Get context module
     *
     * @return context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get the current course module
     *
     * @return mixed stdClass|null The course module
     */
    public function get_course_module() {
        if (!$this->coursemodule) {
            if ($this->context && $this->context->contextlevel == CONTEXT_MODULE) {
                $this->coursemodule = get_coursemodule_from_id('newsletter', $this->context->instanceid, 0, false, MUST_EXIST);
            }
        }
        return $this->coursemodule;
    }

    /**
     * Get the settings for the current instance of this newsletter.
     *
     * @return stdClass The settings
     */
    public function get_instance() {
        global $DB;
        if (!$this->instance) {
            if ($this->get_course_module()) {
                $this->instance = $DB->get_record('newsletter', array('id' => $this->get_course_module()->instance), '*', MUST_EXIST);
            } else {
                throw new coding_exception('Improper use of the newsletter class. Cannot load the newsletter record.');
            }
        }
        return $this->instance;
    }
    
    /**
     * Get subscription id of user if $userid = 0 id of current user is returned
     *
     * @param integer userid
     * @return integer|boolean subscriptionid | false if no subscription is found
     */
    public function get_subid($userid = 0) {
    	global $DB, $USER;
    	if($userid === 0 && !$this->subscriptionid){
    		$this->subscriptionid = $DB->get_field('newsletter_subscriptions', 'id', array('userid' => $USER->id, 'newsletterid' => $this->get_instance()->id));
    		return $this->subscriptionid;
    	} else {
    		return $DB->get_field('newsletter_subscriptions', 'id', array('userid' => $userid, 'newsletterid' => $this->get_instance()->id));
    	}
    }

    /**
     * Get the current course
     *
     * @return mixed stdClass|null The course
     */
    public function get_course() {
        global $DB;
        if (!$this->course) {
            if ($this->context) {
                $this->course = $DB->get_record('course', array('id' => $this->get_instance()->course), '*', MUST_EXIST);
            }
        }
        return $this->course;
    }
    /**
     * Get the module renderer
     *
     * @return mixed stdClass|null The module renderer
     */
    public function get_renderer() {
        if (!$this->renderer) {
            global $PAGE;
            $this->renderer = $PAGE->get_renderer('mod_newsletter');
        }
        return $this->renderer;
    }

    /**
     * get global newsletter settings (admin settings)
     * @return stdClass configuration object
     */
    public function get_config() {
        if (!$this->config) {
            $this->config = get_config('mod_newsletter');
        }
        return $this->config;
    }
    
    /**
     * get all subscribed users for a newsletter instance
     * @return array of objects with subscription id as key
     */
    public function get_subscriptions(){
    	global $DB;
    	return $DB->get_records('newsletter_subscriptions', array('id' => $this->get_instance()->id));
    }

    public function reset_userdata($data) {
        global $CFG, $DB;

        $newsletterssql = "SELECT n.id
                             FROM {newsletter} n
                            WHERE n.course = :course";
        $params = array("course" => $data->courseid);

        $DB->delete_records_select('newsletter_subscriptions', "newsletterid IN ($newsletterssql)", $params);
        $status[] = array('component' => get_string('modulenameplural', 'mod_newsletter'),
                          'item' => get_string('delete_all_subscriptions','newsletter'),
                          'error' => false);
        return array();
    }

    private function get_js_module($strings = array()) {
        $jsmodule = array(
            'name' => 'mod_newsletter',
            'fullpath' => '/mod/newsletter/module.js',
            'requires' => array('node', 'event', 'node-screen', 'panel', 'node-event-delegate'),
            'strings' => $strings,
            );

        return $jsmodule;
    }
    
    
    /**
     * returns an array of localised subscription status names with according
     * key stored in database column "health"
     * 
     * @return array 
     */
    public function get_subscription_statuslist(){
    	return array(
	    	NEWSLETTER_SUBSCRIBER_STATUS_OK => get_string('health_0','mod_newsletter'),
	    	NEWSLETTER_SUBSCRIBER_STATUS_PROBLEMATIC => get_string('health_1','mod_newsletter'),
	    	NEWSLETTER_SUBSCRIBER_STATUS_BLACKLISTED => get_string('health_2','mod_newsletter'),
	    	NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED => get_string('health_4','mod_newsletter')
    	);
    }

    /**
     * Render the view according to passed parameters
     *
     * @return string rendered view
     */
    public function view($params) {
        switch ($params[NEWSLETTER_PARAM_ACTION]) {
        case NEWSLETTER_ACTION_VIEW_NEWSLETTER:
            require_capability('mod/newsletter:viewnewsletter', $this->context);
            $output = $this->view_newsletter($params);
            break;
        case NEWSLETTER_ACTION_CREATE_ISSUE:
            require_capability('mod/newsletter:createissue', $this->context);
            $output = $this->view_edit_issue_page($params);
            break;
        case NEWSLETTER_ACTION_EDIT_ISSUE:
            require_capability('mod/newsletter:editissue', $this->context);
            $output = $this->view_edit_issue_page($params);
            break;
        case NEWSLETTER_ACTION_READ_ISSUE:
            require_capability('mod/newsletter:readissue', $this->context);
            $output = $this->view_read_issue_page($params);
            break;
        case NEWSLETTER_ACTION_DELETE_ISSUE:
            require_capability('mod/newsletter:deleteissue', $this->context);
            $output = $this->view_delete_issue_page($params);
            break;
        case NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS:
            require_capability('mod/newsletter:managesubscriptions', $this->context);
            $output = $this->view_manage_subscriptions($params);
            break;
        case NEWSLETTER_ACTION_EDIT_SUBSCRIPTION:
            require_capability('mod/newsletter:editsubscription', $this->context);
            $output = $this->view_edit_subscription($params);
            break;
        case NEWSLETTER_ACTION_DELETE_SUBSCRIPTION:
            require_capability('mod/newsletter:deletesubscription', $this->context);
            $output = $this->view_delete_subscription($params);
            break;
        case NEWSLETTER_ACTION_SUBSCRIBE:
            require_capability('mod/newsletter:manageownsubscription', $this->context);
            $this->subscribe();
            $url = new moodle_url('/mod/newsletter/view.php', array('id' => $this->get_course_module()->id));
            redirect($url);
            break;
        case NEWSLETTER_ACTION_UNSUBSCRIBE:
            require_capability('mod/newsletter:manageownsubscription', $this->context);
            $this->unsubscribe($this->get_subid());
            $url = new moodle_url('/mod/newsletter/view.php', array('id' => $this->get_course_module()->id));
            redirect($url);
            break;
        case NEWSLETTER_ACTION_GUESTSUBSCRIBE:
        	require_capability('mod/newsletter:viewnewsletter', $this->context);
        	$output = $this->display_guest_subscribe_form($params);
        	break;
        default:
            print_error('Wrong ' . NEWSLETTER_PARAM_ACTION . ' parameter value: ' . $params[NEWSLETTER_PARAM_ACTION]);
            break;
        }

        return $output;
    }
    
    /**
     * display a subscription form for guest users. requires email auth plugin to be enabled
     * 
     * @param array $params url params passed as get variables
     * @return string html rendered guest subscription
     */
	private function display_guest_subscribe_form(array $params) {
		global $PAGE;
		$PAGE->requires->js_module($this->get_js_module());
		$authplugin = get_auth_plugin ( 'email' );
		if (! $authplugin->can_signup ()) {
			print_error ( 'notlocalisederrormessage', 'error', '', 'Sorry, you may not use this page.' );
		}
		$output = '';
		$renderer = $this->get_renderer ();

		require_once (dirname ( __FILE__ ) . '/guest_signup_form.php');
		
		$output .= $renderer->render ( new newsletter_header ( $this->get_instance (), $this->get_context (), false, $this->get_course_module ()->id ) );
		$mform = new mod_newsletter_guest_signup_form ( null, array (
				'id' => $this->get_course_module ()->id,
				NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_GUESTSUBSCRIBE 
		) );
		
		if ($mform->is_cancelled ()) {
			redirect ( new moodle_url ( 'view.php', array (
					'id' => $this->get_course_module ()->id,
					NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_VIEW_NEWSLETTER 
			) ) );
			return;
		} else if ($data = $mform->get_data ()) {
			$this->subscribe_guest ( $data->firstname, $data->lastname, $data->email );
			$a = $data->email;
			$output .= html_writer::div(get_string('guestsubscriptionsuccess', 'newsletter', $a));
			$url = new moodle_url ( '/mod/newsletter/view.php', array (
					'id' => $this->get_course_module ()->id 
			) );
			$output .= html_writer::link($url, get_string('continue'), array ('class' => 'btn mdl-align')) ;
			return $output;
		} else if ($this->get_config ()->allow_guest_user_subscriptions && (!isloggedin () || isguestuser())) {
			$output .= $renderer->render ( new newsletter_form ( $mform, null ) );
			$output .= $renderer->render_footer ();
			return $output;
		}
		
	}
	
    /**
     * Display all newsletter issues in a view. Display action links
     * according to capabilities
     * 
     * @param array $params
     * @return string html
     */
    private function view_newsletter(array $params) {
    	global $PAGE, $CFG;
    	$renderer = $this->get_renderer();

        $output = '';
        $output .= $renderer->render(
                new newsletter_header(
                        $this->get_instance(),
                        $this->get_context(),
                        false,
                        $this->get_course_module()->id));
        
        $output .= $renderer->render(new newsletter_main_toolbar(
                                $this->get_course_module()->id,
                                $params[NEWSLETTER_PARAM_GROUP_BY],
                                has_capability('mod/newsletter:createissue', $this->context),
                                has_capability('mod/newsletter:managesubscriptions', $this->context)));
        
        if (has_capability('mod/newsletter:manageownsubscription', $this->context) && $this->instance->subscriptionmode != NEWSLETTER_SUBSCRIPTION_MODE_FORCED) {
        	if (!$this->is_subscribed()) {
        		$url = new moodle_url('/mod/newsletter/view.php',
        				array(NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
        						NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_SUBSCRIBE));
        		$text = get_string('subscribe', 'mod_newsletter');
        		$output .= html_writer::link($url, $text);
        	} else {
        		$url = new moodle_url('/mod/newsletter/view.php',
        				array(NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
        						NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_UNSUBSCRIBE));
        		$text = get_string('unsubscribe', 'mod_newsletter');
        		$output .= html_writer::link($url, $text);
        	}
        } else {

        	if (!empty($CFG->registerauth) AND is_enabled_auth('email')) {
        		$guestsignup_possible = true;
        	} else {
        		$guestsignup_possible = false;
        	}
        	if ($this->get_config()->allow_guest_user_subscriptions && (!isloggedin() || isguestuser()) && $guestsignup_possible) {
        		$url = new moodle_url('/mod/newsletter/view.php',
        				array(NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
        						NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_GUESTSUBSCRIBE));
        		$text = get_string('subscribe', 'mod_newsletter');
        		$output .= html_writer::link($url, $text, array( 'class' => 'btn'));
        	}
        }
        
        $issuelist = $this->prepare_issue_list('', $params[NEWSLETTER_PARAM_GROUP_BY]);
        if ($issuelist) {
            $output .= $renderer->render($issuelist);
        } else {
            $output .= '<h2>' . get_string('no_issues', 'mod_newsletter') . '</h2>';
        }

        $output .= $renderer->render_footer();
        return $output;
    }

    /**
     * Prepare newsletter issue for reading
     * 
     * @param array $params
     * @return string html of newsletter issue
     */
    private function view_read_issue_page(array $params) {
        global $CFG;
        if(!(has_capability('mod/newsletter:editissue', $this->get_context())) && $this->get_issue($params[NEWSLETTER_PARAM_ISSUE])->publishon > time()){
        	require_capability('mod/newsletter:editissue', $this->get_context());
        }
        
        $renderer = $this->get_renderer();

        $output = $renderer->render(
                new newsletter_header(
                        $this->get_instance(),
                        $this->get_context(),
                        false,
                        $this->get_course_module()->id));
        $currentissue = $this->get_issue($params[NEWSLETTER_PARAM_ISSUE]);
        $navigation_bar = new newsletter_navigation_bar(
                                $currentissue,
                                $this->get_first_issue($currentissue),
                                $this->get_previous_issue($currentissue),
                                $this->get_next_issue($currentissue),
                                $this->get_last_issue($currentissue));
        $output .= $renderer->render($navigation_bar);
        
        
        // generate table of content
        require_once $CFG->dirroot . "/mod/newsletter/classes/issue_parser.php";
        $toc = new \mod_newsletter\mod_newsletter_issue_parser($currentissue);
        $currentissue->htmlcontent = $toc->get_toc_and_doc();
        		
        //render the html with inline css based on the used stylesheet
        $currentissue->htmlcontent = file_rewrite_pluginfile_urls($currentissue->htmlcontent, 'pluginfile.php', $this->get_context()->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_ISSUE, $params[NEWSLETTER_PARAM_ISSUE],  mod_newsletter_issue_form::editor_options($this->get_context(), $params[NEWSLETTER_PARAM_ISSUE]) );
        $currentissue->htmlcontent = $this->inline_css($currentissue->htmlcontent, $currentissue->stylesheetid);
        

        $output .= $renderer->render(new newsletter_issue($currentissue));

        $fs = get_file_storage();
        $files = $fs->get_area_files($this->get_context()->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_ATTACHMENT, $currentissue->id, "", false);
        foreach ($files as $file) {
            $file->link = file_encode_url($CFG->wwwroot.'/pluginfile.php', '/'.$this->get_context()->id.'/mod_newsletter/attachment/'.$currentissue->id.'/'.$file->get_filename());
        }

        $output .= $renderer->render(new newsletter_attachment_list($files));
        $output .= $renderer->render($navigation_bar);
        $output .= $renderer->render_footer();
        
        $params = array(
        		'context' => $this->get_context(),
        		'objectid' => $params[NEWSLETTER_PARAM_ISSUE],
        		'other' => array(
        				'newsletterid' => $this->get_instance()->id,
        		)
        );

        $event = \mod_newsletter\event\issue_viewed::create($params);
        $event->trigger();
        
        return $output;
    }

    /**
     * delete a single issue of a newsletter
     * can only be done when not yet sent
     * 
     * @param array $params
     * @return string rendered html
     */
    private function view_delete_issue_page(array $params) {
    	global $OUTPUT;
        if (!$params[NEWSLETTER_PARAM_ISSUE] || !$this->check_issue_id($params[NEWSLETTER_PARAM_ISSUE])) {
            print_error('Wrong ' . NEWSLETTER_PARAM_ISSUE . ' parameter value: ' . $params[NEWSLETTER_PARAM_ISSUE]);
        }

        if ($params[NEWSLETTER_PARAM_CONFIRM] != NEWSLETTER_CONFIRM_UNKNOWN) {
            $url = new moodle_url('/mod/newsletter/view.php', array(NEWSLETTER_PARAM_ID => $this->get_course_module()->id));
            if ($params[NEWSLETTER_PARAM_CONFIRM] == NEWSLETTER_CONFIRM_YES) {
                $this->delete_issue($params[NEWSLETTER_PARAM_ISSUE]);
                redirect($url);
            } else if ($params[NEWSLETTER_PARAM_CONFIRM] == NEWSLETTER_CONFIRM_NO) {
                redirect($url);
            } else {
                print_error("Wrong confirm!");
            }
        }

        $renderer = $this->get_renderer();
        $output = '';
        $output .= $renderer->render(
                    new newsletter_header(
                                $this->get_instance(),
                                $this->get_context(),
                                false,
                                $this->get_course_module()->id));
        $url = new moodle_url('/mod/newsletter/view.php',
                              array(NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
                                    NEWSLETTER_PARAM_ISSUE => $params[NEWSLETTER_PARAM_ISSUE],
                                    NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_DELETE_ISSUE));
        $output .=  $OUTPUT->confirm(get_string('delete_issue_question', 'mod_newsletter'),
                new moodle_url($url, array(NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_YES)),
                new moodle_url($url, array(NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_NO)));
        $output .= $renderer->render_footer();
        return $output;
    }

    /**
     * Display newsletter issue in editing mode
     * 
     * @param array $params
     * @return string rendered HTML
     */
    private function view_edit_issue_page(array $params) {
        global $CFG, $PAGE;
        if (!$this->check_issue_id($params[NEWSLETTER_PARAM_ISSUE])) {
            print_error('Wrong ' . NEWSLETTER_PARAM_ISSUE . ' parameter value: ' . $params[NEWSLETTER_PARAM_ISSUE]);
        }
		
        $context = $this->get_context();
        $issue = $this->get_issue($params[NEWSLETTER_PARAM_ISSUE]);
        $newsletterconfig = $this->get_config();
        if(is_null($issue)){
        	$issue = new stdClass();
        	$issue->htmlcontent = '';
        	$issue->id = 0;
        	$issue->title = '';
        	$issue->publishon = null;
        	$issue->toc = 0;
        	$issue->stylesheetid = NEWSLETTER_DEFAULT_STYLESHEET;
        	$issue->delivered = NEWSLETTER_DELIVERY_STATUS_UNKNOWN;
        }
        // publishon can not be altered, if delivery has already started or been completed
        $deliverystartedorcompleted = 'yes';
        if ($issue->delivered == NEWSLETTER_DELIVERY_STATUS_UNKNOWN || $issue->delivered == NEWSLETTER_DELIVERY_STATUS_FAILED){
        	$deliverystartedorcompleted = 'no';
        }
        
        $issue->messageformat = editors_get_preferred_format();
        $issue->newsletterid = $this->get_instance()->id;
		
        
        $fs = get_file_storage();
        $files = $fs->get_area_files($context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_STYLESHEET, $this->get_instance()->id, 'filename', false);
        $options = array();
        $options[NEWSLETTER_DEFAULT_STYLESHEET] = "{$CFG->wwwroot}/mod/newsletter/reset.css";
        foreach ($files as $file) {
            $url = "{$CFG->wwwroot}/pluginfile.php/{$file->get_contextid()}/mod_newsletter/" . NEWSLETTER_FILE_AREA_STYLESHEET;
            $options[$file->get_id()] = $url . $file->get_filepath() . $file->get_itemid() . '/' . $file->get_filename();
        }

        $PAGE->requires->js_module($this->get_js_module());
        $PAGE->requires->js_init_call('M.mod_newsletter.init_tinymce', array($options, $issue->id ? $issue->stylesheetid : NEWSLETTER_DEFAULT_STYLESHEET));

        require_once(dirname(__FILE__).'/classes/issue_form.php');
        $mform = new mod_newsletter_issue_form(null, array(
        		'newsletter' => $this,
        		'issue' => $issue,
        		'context' => $context
        ));
        
        $draftitemid = file_get_submitted_draft_itemid('attachments');
        file_prepare_draft_area($draftitemid, $context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_ATTACHMENT, empty($issue->id)?null:$issue->id, mod_newsletter_issue_form::attachment_options($newsletterconfig));
        
        $issueid = empty($issue->id) ? null : $issue->id;
        $draftid_editor = file_get_submitted_draft_itemid('htmlcontent');
        $currenttext = file_prepare_draft_area($draftid_editor, $context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_ISSUE, $issueid, mod_newsletter_issue_form::editor_options($context, $issueid), $issue->htmlcontent);
		$mform->set_data ( array (
				'attachments' => $draftitemid,
				'title' => $issue->title,
				'htmlcontent' => array (
						'text' => $currenttext,
						'format' => empty ( $issue->messageformat ) ? editors_get_preferred_format () : $issue->messageformat,
						'itemid' => $draftid_editor 
				),
				'deliverystarted' => $deliverystartedorcompleted,
				'toc' => $issue->toc,
				'publishon' => $issue->publishon,
				'stylesheetid' => $issue->stylesheetid
		) );
        		
        if ($data = $mform->get_data()) {
            if (!$data->issue) {
                $this->add_issue($data);
            } else {
                $this->update_issue($data);
            }
            $url = new moodle_url('/mod/newsletter/view.php', array('id' => $this->get_course_module()->id));
            redirect($url);
        }

        $output = '';
        $renderer = $this->get_renderer();

        $output .= $renderer->render(
                    new newsletter_header(
                                $this->get_instance(),
                                $this->get_context(),
                                false,
                                $this->get_course_module()->id));
        //TODO: remove ugly config hack and provide js for atto
        $texteditors = $CFG->texteditors;
        $CFG->texteditors = 'tinymce,textarea';
        $output .= $renderer->render(new newsletter_form($mform, get_string('edit_issue_title', 'mod_newsletter')));
        $CFG->texteditors = $texteditors;
        $output .= $renderer->render_footer();
        return $output;
    }

    /**
     * Display a list of users with statusses and action links in order to manage single subscriptions
     * @param array $params
     * @return string rendered html
     */
    private function view_manage_subscriptions(array $params) {
        global $DB, $OUTPUT;
        
        $url = new moodle_url('/mod/newsletter/view.php', array(
        		'id' => $this->get_course_module()->id,
        		'action' => NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS));
        
        list($filtersql, $filterparams) = $this->get_filter_sql($params);
        if ($params['resetbutton'] !== '') {
        	redirect($url);
        }
        $from = $params[NEWSLETTER_PARAM_FROM];
        $count = $params[NEWSLETTER_PARAM_COUNT];
        $subscriptions = $DB->get_records_sql($filtersql, $filterparams, $from, $count);
        
        $sqlparams = array('newsletterid' => $this->get_instance()->id);
        $total = $DB->count_records('newsletter_subscriptions', $sqlparams);
        list ($countsql, $countparams) = $this->get_filter_sql($params, true);
        $totalfiltered = $DB->count_records_sql($countsql, $countparams);
        
        $pages = $this->calculate_pages($totalfiltered, $from, $count);

        $columns = array(NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_EMAIL,
                         NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_NAME,
                         NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_HEALTH,
                  		 NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_TIMESUBSCRIBED,
                         NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_ACTIONS);
        
        $filterform = new \mod_newsletter\subscription\mod_newsletter_subscription_filter_form('view.php', array('newsletter' => $this),
        'get', '', array('id' => 'filterform'));
        $filterform->set_data(array('search' => $params['search'], 'status' => $params['status'], 'count' => $params['count'], 'orderby' => $params['orderby']));

        $renderer = $this->get_renderer();
        $output = '';
        $output .= $renderer->render(
                    new newsletter_header(
                                $this->get_instance(),
                                $this->get_context(),
                                false,
                                $this->get_course_module()->id));
        $newurl = $url;
        $newurl->params($params);
        
        require_once(dirname(__FILE__).'/classes/subscription/subscriptions_admin_form.php');
        $mform = new \mod_newsletter\subscription\mod_newsletter_subscriptions_admin_form(null, array(
        		'id' => $this->get_course_module()->id,
        		'course' => $this->get_course()));
        
        if ($data = $mform->get_data()) {
        	if(isset($data->subscribe)) {
        		foreach ($data->cohorts as $cohortid) {
        			$this->subscribe_cohort($cohortid);
        		}
        	} else if(isset($data->unsubscribe)) {
        		foreach ($data->cohorts as $cohortid) {
        			$this->unsubscribe_cohort($cohortid);
        		}
        	} else {
        		print_error("Wrong submit!");
        	}
        	redirect($url);
        }
        
        require_once(dirname(__FILE__).'/classes/subscription/newsletter_user_subscription.php');
        $subscriberselector = new \mod_newsletter\subscription\mod_newsletter_potential_subscribers('subsribeusers', array('newsletterid' => $this->get_instance()->id));
        $subscribedusers = new \mod_newsletter\subscription\mod_newsletter_existing_subscribers('subscribedusers', array('newsletterid' => $this->get_instance()->id, 'newsletter' => $this));
        
        if(optional_param('add', false, PARAM_BOOL) && confirm_sesskey()){
        	$userstosubscribe = $subscriberselector->get_selected_users();
        	if (!empty($userstosubscribe)) {
        		foreach ($userstosubscribe as $user){
        			$this->subscribe($user->id, false, NEWSLETTER_SUBSCRIBER_STATUS_OK);
        		}
        	}
        	$subscriberselector->invalidate_selected_users();
        	$subscribedusers->invalidate_selected_users();
        }
        
        if(optional_param('unsubscribe', false, PARAM_BOOL) && confirm_sesskey()){
        	$userstoremove = $subscribedusers->get_selected_users();
        	if (!empty($userstoremove)) {
        		foreach ($userstoremove as $user){
        			$this->unsubscribe($user->subid, $user->id);
        		}
        	}
        	$subscriberselector->invalidate_selected_users();
        	$subscribedusers->invalidate_selected_users();
        }
        
        if(optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()){
        	$userstoremove = $subscribedusers->get_selected_users();
        	if (!empty($userstoremove)) {
        		foreach ($userstoremove as $user){
        			$this->delete_subscription($user->subid,$user->id);
        		}
        	}
        	$subscriberselector->invalidate_selected_users();
        	$subscribedusers->invalidate_selected_users();
        }
        
        require_once(dirname(__FILE__).'/classes/subscription/subscriber_selector_form.php');
        $subscriber_form = new \mod_newsletter\subscription\mod_newsletter_subscriber_selector_form(null, array(
        		'id' => $this->get_course_module()->id,
        		'course' => $this->get_course(),
        		'existing' => $subscribedusers,
        		'potential' => $subscriberselector,
        		'leftarrow' => $OUTPUT->larrow(),
        		'rightarrow' => $OUTPUT->rarrow()
        ));
        
        $output .= $renderer->render(new newsletter_form($subscriber_form, null));
        $output .= $renderer->render(new newsletter_form($mform, null));
        $output .= $renderer->render(new newsletter_form($filterform));
        $output .= $renderer->render(new newsletter_pager($newurl, $from, $count, $pages, $total, $totalfiltered));
        $output .= $renderer->render(new newsletter_subscription_list($this->get_course_module()->id, $subscriptions, $columns));
        $output .= $renderer->render_footer();

        $logparams = array(
        		'context' => $this->get_context(),
        		'objectid' => $this->get_instance()->id
        );
        $event = \mod_newsletter\event\subscriptions_viewed::create($logparams);
        $event->trigger();
        
        return $output;
    }

    /**
     * Display edit form for editing the status of a single subscription of a single user
     * 
     * @param array $params
     * @return string rendered html with form
     */
    private function view_edit_subscription(array $params) {
        global $DB;
        $subscription = $DB->get_record('newsletter_subscriptions', array('id' => $params[NEWSLETTER_PARAM_SUBSCRIPTION]));
        require_once(dirname(__FILE__).'/classes/subscription/subscription_form.php');
        $mform = new \mod_newsletter\subscription\mod_newsletter_subscription_form(null, array(
                'newsletter' => $this,
                'subscription' => $subscription));
        
        if ($mform->is_cancelled()) {
        	redirect(new moodle_url('view.php',	array('id'=>$this->get_course_module()->id, NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS)));
        	return;
        } else if ($data = $mform->get_data()) {
            $this->update_subscription($data);
            $url = new moodle_url('/mod/newsletter/view.php',
                    array(NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
                          NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS));
            redirect($url);
        }

        $output = '';
        $renderer = $this->get_renderer();

        $output .= $renderer->render(
                    new newsletter_header(
                                $this->get_instance(),
                                $this->get_context(),
                                false,
                                $this->get_course_module()->id));
        $output .= $renderer->render(new newsletter_form($mform, get_string('edit_subscription_title', 'mod_newsletter')));
        $output .= $renderer->render_footer();
        return $output;
    }

    /**
     * Display deletion dialogue for deleting a single subscription of a user
     * and delete user upon confirmation
     * 
     * @param array $params
     * @return string rendered html
     */
    private function view_delete_subscription(array $params) {
        global $OUTPUT;

        if ($params[NEWSLETTER_PARAM_CONFIRM] != NEWSLETTER_CONFIRM_UNKNOWN) {
            $redirecturl = new moodle_url('/mod/newsletter/view.php',
                    array(NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
                          NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS));
            if ($params[NEWSLETTER_PARAM_CONFIRM] == NEWSLETTER_CONFIRM_YES) {
                $this->delete_subscription($params[NEWSLETTER_PARAM_SUBSCRIPTION]);
                redirect($redirecturl);
            } else if ($params[NEWSLETTER_PARAM_CONFIRM] == NEWSLETTER_CONFIRM_NO) {
                redirect($redirecturl);
            } else {
                print_error("Wrong confirm!");
            }
        }

        $renderer = $this->get_renderer();
        $output = $renderer->render(
                new newsletter_header(
                        $this->get_instance(),
                        $this->get_context(),
                        false,
                        $this->get_course_module()->id));

        $url = new moodle_url('/mod/newsletter/view.php',
                              array(NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
                                    NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_DELETE_SUBSCRIPTION,
                                    NEWSLETTER_PARAM_SUBSCRIPTION => $params[NEWSLETTER_PARAM_SUBSCRIPTION]));
        $output .=  $OUTPUT->confirm(get_string('delete_subscription_question', 'mod_newsletter'),
                new moodle_url($url, array(NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_YES)),
                new moodle_url($url, array(NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_NO)));
        $output .= $renderer->render_footer();
        return $output;
    }

    /**
     * Display  the overview of all newsletter issues as a list
     * //TODO: implement issue navigation from a point of time to a point of time
     * 
     * @param unknown $heading
     * @param unknown $groupby
     * @return NULL|newsletter_section_list
     */
    private function prepare_issue_list($heading, $groupby) {
        global $DB;
        // TODO: Add first day of the week check

        $editissue = has_capability('mod/newsletter:editissue', $this->get_context());
        $deleteissue = has_capability('mod/newsletter:deleteissue', $this->get_context());

        $issues = $this->get_issues();

        if (empty($issues)) {
            return null;
        }
            
        $firstissue = reset($issues);
        $firstdayofweek = (int) get_string('firstdayofweek', 'langconfig');
        switch ($groupby) {
        case NEWSLETTER_GROUP_ISSUES_BY_YEAR:
            $from = strtotime("first day of this year", $firstissue->publishon);
            $to = strtotime("next year", $from);
            $dateformat = "%Y";
            break;
        case NEWSLETTER_GROUP_ISSUES_BY_MONTH:
            $from = strtotime("first day of this month", $firstissue->publishon);
            $to = strtotime("next month", $from);
            $dateformat = "%B %Y";
            break;
        case NEWSLETTER_GROUP_ISSUES_BY_WEEK:
            $from = strtotime(date('o-\\WW', $firstissue->publishon));
            $to = strtotime("next monday", $from);
            $dateformat = "Week %W of year %Y";
            $datefromto = "%d. %B %Y";
            break;
        }

        $sectionlist = new newsletter_section_list($heading);
        $currentissuelist = new newsletter_issue_summary_list();
        foreach ($issues as $issue) {
            while ($issue->publishon < $from) {
                $from = $to;
                switch ($groupby) {
                case NEWSLETTER_GROUP_ISSUES_BY_YEAR:
                    $to = strtotime("next year", $from);
                    break;
                case NEWSLETTER_GROUP_ISSUES_BY_MONTH:
                    $to = strtotime("next month", $from);
                    break;
                case NEWSLETTER_GROUP_ISSUES_BY_WEEK:
                    $to = strtotime("next monday", $from);
                    break;
                }
            }
            if ($issue->publishon < $to) {
            	// do not display issues that are not yet published
            	if (!($issue->publishon > time() && !$editissue)){
            		$currentissuelist->add_issue_summary(new newsletter_issue_summary($issue, $editissue, $deleteissue));
            	}
            } else {
                if ($groupby == NEWSLETTER_GROUP_ISSUES_BY_WEEK) {
                    $heading = userdate($from, $dateformat) . ' (' . userdate($from, $datefromto) . ' - ' . userdate(strtotime('yesterday', $to), $datefromto) . ')';
                } else {
                    $heading = userdate($from, $dateformat);
                }
                while ($issue->publishon < $from || $issue->publishon > $to) {
                    $from = $to;
                    switch ($groupby) {
                    case NEWSLETTER_GROUP_ISSUES_BY_YEAR:
                        $to = strtotime("next year", $from);
                        break;
                    case NEWSLETTER_GROUP_ISSUES_BY_MONTH:
                        $to = strtotime("next month", $from);
                        break;
                    case NEWSLETTER_GROUP_ISSUES_BY_WEEK:
                        $to = strtotime("next monday", $from);
                        break;
                    }
                }
                $sectionlist->add_issue_section(new newsletter_section($heading, $currentissuelist));
                $currentissuelist = new newsletter_issue_summary_list();
                // do not display issues that are not yet published
                if (!($issue->publishon > time() && !$editissue)){
                	$currentissuelist->add_issue_summary(new newsletter_issue_summary($issue, $editissue, $deleteissue));
                }
            }
        }
        if (!empty($currentissuelist->issues)) {
            if ($groupby == NEWSLETTER_GROUP_ISSUES_BY_WEEK) {
                $heading = userdate($from, $dateformat) . ' (' . userdate($from, $datefromto) . ' - ' . userdate(strtotime('yesterday', $to), $datefromto) . ')';
            } else {
                $heading = userdate($from, $dateformat);
            }
            $sectionlist->add_issue_section(new newsletter_section($heading, $currentissuelist));
        }
        return $sectionlist;
    }
	
    /**
     * calculate number of pages for displaying subscriptions
     * @param number $total
     * @param number $from
     * @param number $count
     * @return multitype:number |multitype:unknown number
     */
    private function calculate_pages($total, $from, $count) {
        $pages = array();
        $pagenum = 1;

        if ($total == 0) {
            $pages[0] = $pagenum;
            return $pages;
        }

        if ($from % $count !== 0) {
            $pages[0] = $pagenum;
            $pagenum++;
        }

        for ($i = $from % $count; $i < $total; $i += $count) {
            $pages[$i] = $pagenum;
            $pagenum++;
        }

        return $pages;
    }

    private function check_issue_id($issueid) {
        global $DB;

        return !$issueid || $DB->get_field('newsletter_issues', 'newsletterid',
                array('id' => $issueid, 'newsletterid' => $this->get_instance()->id));
    }

    private function add_issue(stdClass $data) {
        global $DB;
        $context = $this->get_context();
        
        $issue = new stdClass();
        $issue->id = 0;
        $issue->newsletterid = $this->get_instance()->id;
        $issue->title = $data->title;
        $issue->htmlcontent = '';
        $issue->publishon = $data->publishon;
        $issue->stylesheetid = $data->stylesheetid;
		$issue->toc = $data->toc;
        $issue->id = $DB->insert_record('newsletter_issues', $issue);
        
        $issue->htmlcontent = file_save_draft_area_files($data->htmlcontent['itemid'], $context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_ISSUE, $issue->id, mod_newsletter_issue_form::editor_options($context, $issue->id), $data->htmlcontent['text']);
		
        $DB->set_field('newsletter_issues', 'htmlcontent', $issue->htmlcontent, array ('id' => $issue->id ));
        
        $fileoptions = array('subdirs' => NEWSLETTER_FILE_OPTIONS_SUBDIRS,
                         'maxbytes' => 0,
                         'maxfiles' => -1);

        if ($data && $data->attachments) {
            file_save_draft_area_files($data->attachments, $context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_ATTACHMENT, $issue->id, $fileoptions);
        }
        
        $params = array(
        		'context' => $context,
        		'objectid' => $issue->id,
        		'other' => array(
        				'newsletterid' => $issue->newsletterid,
        		)
        );
        $event = \mod_newsletter\event\issue_created::create($params);
        $event->trigger();

        return $issue->id;
    }

    /**
     * Update an existing newsletter issue.
     * 
     * @param stdClass $data
     */
    private function update_issue(stdClass $data) {
        global $DB;
		
        $context = $this->get_context();
        $oldissue = $this->get_issue($data->issue);
        $deliverystartedorcompleted = true;
        if ($oldissue->delivered == NEWSLETTER_DELIVERY_STATUS_UNKNOWN || $oldissue->delivered == NEWSLETTER_DELIVERY_STATUS_FAILED){
        	$deliverystartedorcompleted = false;
        }
        
        $issue = new stdClass();
        $issue->id = $data->issue;
        $issue->title = $data->title;
        $issue->newsletterid = $this->get_instance()->id;
        $issue->htmlcontent = file_save_draft_area_files($data->htmlcontent['itemid'], $context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_ISSUE, $issue->id, mod_newsletter_issue_form::editor_options($context, $issue->id), $data->htmlcontent['text']);
        
        // the publishon on date must not be altered after newsletter was sent
        if(!$deliverystartedorcompleted){
        	$issue->publishon = $data->publishon;
        }
        
        $issue->stylesheetid = $data->stylesheetid;
		$issue->toc = $data->toc;
        $fileoptions = array('subdirs' => NEWSLETTER_FILE_OPTIONS_SUBDIRS,
                         'maxbytes' => 0,
                         'maxfiles' => -1);

        if ($data && $data->attachments) {
            file_save_draft_area_files($data->attachments, $context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_ATTACHMENT, $issue->id, $fileoptions);
        }

        $DB->update_record('newsletter_issues', $issue);
    }

    /**
     * delete a newsletter issue
     * 
     * @param number $issueid
     */
    private function delete_issue($issueid) {
        global $DB;
        $DB->delete_records('newsletter_issues', array('id' => $issueid));
    }

    /**
     * given the cohortid retrieve all userids of the cohort members
     * and subscribe every single user of the cohort to the newsletter
     * in a course only enrolled users who are members of the cohort will be subscribed
     * 
     * @param number $cohortid
     * @param boolean $resubscribe_unsubscribed
     */
    function subscribe_cohort($cohortid,$resubscribe_unsubscribed = false) {
        global $DB;
        $instanceid = $this->get_instance()->id;
        list($enrolled_sql, $enrolled_params) = get_enrolled_sql($this->get_context());
        
        $already_subscribed_sql = "SELECT cm.userid AS cmuserid
        		FROM {cohort_members} cm
        		JOIN {newsletter_subscriptions} ns ON (cm.userid = ns.userid)
        		WHERE cm.cohortid = :cohortid
        		AND ns.newsletterid = :newsletterid";
        $sql = "SELECT cm.userid
		        FROM {cohort_members} cm
		        WHERE cm.cohortid = :cohortid
		        AND cm.userid IN ($enrolled_sql)";
        $params = array('cohortid' => $cohortid, 'newsletterid' => $instanceid);
        $params = array_merge($params,$enrolled_params);
        $users = $DB->get_fieldset_sql($sql, $params);
        foreach ($users as $userid) {
           	$this->subscribe($userid, true,NEWSLETTER_SUBSCRIBER_STATUS_OK,$resubscribe_unsubscribed);
        }
    }
    
    /**
     * unsubscribes members of a cohort from newsletter
     * 
     * @param integer $cohortid
     */
    function unsubscribe_cohort($cohortid) {
        global $DB;
        $newsletterid =$this->get_instance()->id;
        $sql = "SELECT ns.id, ns.userid
                 FROM {newsletter_subscriptions} ns
        		 JOIN {cohort_members} cm ON (cm.userid = ns.userid AND ns.newsletterid = :newsletterid)
                 WHERE cm.cohortid = :cohortid"; 
        $params = array('cohortid' => $cohortid, 'newsletterid' => $newsletterid);
        $usersubscriptions = $DB->get_records_sql($sql, $params);

        foreach ($usersubscriptions as $subscription) {
            $this->unsubscribe($subscription->id, $subscription->userid);
        }
    }

    /**
     * Get the database records of newsletterissues from a range of publishing dates.
     * The range is specified as $from timestamp and $to timestamp
     * 
     * @param number $from UTC timestamp
     * @param number $to UTC timestamp
     * @return multitype: db records of newsletter issues
     */
    private function get_issues($from = 0, $to = 0) {
        global $DB;
        //$total = $DB->count_records('newsletter_subscriptions', array('newsletterid' => $this->get_instance()->id));
        $total = $DB->count_records_select('newsletter_subscriptions', 'newsletterid = '.$this->get_instance()->id . ' AND health < 2');

        $query = "SELECT i.*
                    FROM {newsletter_issues} i
                   WHERE i.newsletterid = :newsletterid
                     AND " . ($from ? " i.publishon > :from" : "1") .
                   " AND " . ($to ? " i.publishon > :to" : "1") .
              " ORDER BY i.publishon ASC";
        $params = array('newsletterid' => $this->get_instance()->id,
                                'from' => $from,
                                  'to' => $to);
        $records = $DB->get_records_sql($query, $params);
        foreach ($records as $record) {
            $record->cmid = $this->get_course_module()->id;
            $record->numsubscriptions = $total;
            if ($record->delivered == NEWSLETTER_DELIVERY_STATUS_DELIVERED || $record->delivered == NEWSLETTER_DELIVERY_STATUS_INPROGRESS) {
                $record->numnotyetdelivered = $DB->count_records('newsletter_deliveries', array( 'issueid' => $record->id, 'delivered' => 0) );
            	$record->numdelivered = $DB->count_records('newsletter_deliveries', array( 'issueid' => $record->id, 'delivered' => 1) );
            } else {
            	$record->numdelivered = 0;
            }
        }
        return $records;
    }
	
	/**
	 * returns the database record of a newsletter issue as an object
	 *
	 * @param number $issueid        	
	 * @return NULL|mixed returns null if no issueid given otherwise the object of $DB->get_reccord
	 */
	private function get_issue($issueid) {
		global $DB;
		if ($issueid == 0) {
			return null;
		}
		if (isset ( $this->issues [$issueid] )) {
			return $this->issues [$issueid];
		} else {
			$record = $DB->get_record ( 'newsletter_issues', array (
					'id' => $issueid,
					'newsletterid' => $this->get_instance ()->id 
			) );
			if ($record) {
				$record->cmid = $this->get_course_module ()->id;
				$record->context = $this->get_context ()->id;
			}
			return $record;
		}
    }

    /**
     * Given a stylesheet id return the file as array with id as key
     * If no id is given, return all stylesheets available in the newsletter instance
     * 
     * @param number $id
     * @return array of stored_file or empty array
     */
    public function get_stylesheets($id = 0) {
        $fs = get_file_storage();
        $context = $this->get_context();
        $files = $fs->get_area_files($context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_STYLESHEET, $this->get_instance()->id, 'filename', false);
        if($id === 0) {
            return $files;
        } else {
            foreach($files as $file) {
                if($file->get_id() == $id) {
                    return array ($id => $file);
                }
            }
        }
        return array();
    }

    /**
     * Convert CSS from stylesheet to inlinecss and return html with inlinecss
     * 
     * @param string $htmlcontent
     * @param integer $stylesheetid
     * @param boolean $fulldocument
     * @return Ambigous <string, void, mixed>
     */
    public function inline_css($htmlcontent, $stylesheetid, $fulldocument = false) {
        global $CFG;
        $cssfile = $this->get_stylesheets($stylesheetid);
        $basecss = file_get_contents(dirname(__FILE__) . '/' . NEWSLETTER_BASE_STYLESHEET_PATH);
        $toccss = file_get_contents(dirname(__FILE__) . '/' . 'toc.css');
        $css = $basecss.$toccss;
        if(!empty($cssfile)){
        	foreach ($cssfile as $storedstylefile){
        		$css .= ($cssfile ? ('\n' . $storedstylefile->get_content()) : '');
        	}
        }

        $converter = new CssToInlineStyles();
        $converter->setHTML(mb_convert_encoding($htmlcontent, 'HTML-ENTITIES', 'UTF-8' )); 
        $converter->setCSS($css);
        $html = $converter->convert();
        
        if (!$fulldocument) {
        	if (preg_match('/.*?<html.*?style="([^"]*?)"[^>]*?>.*?<body.*?style="([^"]*?)"[^>]*?>(.+)<\/body>.*/msi', $html)) {
        		$html = preg_replace('/.*?<html.*?style="([^"]*?)"[^>]*?>.*?<body.*?style="([^"]*?)"[^>]*?>(.+)<\/body>.*/msi', '<div style="$1 $2">$3</div>', $html);
        	} else if (preg_match('/.*?<html[^>]*?>.*?<body.*?style="([^"]*?)"[^>]*?>(.+)<\/body>.*/msi', $html)) {
        		$html = preg_replace('/.*?<html[^>]*?>.*?<body.*?style="([^"]*?)"[^>]*?>(.+)<\/body>.*/msi', '<div style="$1">$2</div>', $html);
        	} else if (preg_match('/.*?<html.*?style="([^"]*?)"[^>]*?>.*?<body[^>]*?>(.+)<\/body>.*/msi', $html)) {
        		$html = preg_replace('/.*?<html.*?style="([^"]*?)"[^>]*?>.*?<body[^>]*?>(.+)<\/body>.*/msi', '<div style="$1">$2</div>', $html);
        	} else if (preg_match('/.*?<html[^>]*?>.*?<body[^>]*?>(.+)<\/body>.*/msi', $html)) {
        		$html = preg_replace('/.*?<html[^>]*?>.*?<body[^>]*?>(.+)<\/body>.*/msi', '<div>$1</div>', $html);
        	} else {
        		$html = '';
        	}
        }
        return $html;
    }

    /**
     * returns the previous issue of a newsletter instance
     * 
     * @param stdClass $issue
     * @return Ambigous <NULL, mixed>
     */
    private function get_previous_issue($issue) {
        global $DB;
        $query = "SELECT *
                    FROM {newsletter_issues} i
                   WHERE i.newsletterid = :newsletterid
                     AND i.publishon < :publishon
                ORDER BY i.publishon DESC";
        $params = array('newsletterid' => $issue->newsletterid, 'publishon' => $issue->publishon);
        $results = $DB->get_records_sql($query, $params);
        return empty($results) ? null : reset($results);
    }

    /**
     * returns the next issue of a newsletter
     * 
     * @param stdClass $issue
     * @return Ambigous <NULL, mixed>
     */
    private function get_next_issue($issue) {
        global $DB;
        $query = "SELECT *
                    FROM {newsletter_issues} i
                   WHERE i.newsletterid = :newsletterid
                     AND i.publishon > :publishon
                ORDER BY i.publishon ASC";
        $params = array('newsletterid' => $issue->newsletterid, 'publishon' => $issue->publishon);
        $results = $DB->get_records_sql($query, $params);
        return empty($results) ? null : reset($results);
    }

    /**
     * returns the first issue of a newsletter instance
     * 
     * @param stdClass $issue
     * @return Ambigous <NULL, mixed>
     */
    private function get_first_issue($issue) {
        global $DB;
        $query = "SELECT *
                    FROM {newsletter_issues} i
                   WHERE i.newsletterid = :newsletterid
                     AND i.publishon < :publishon
                     AND i.id != :id
                ORDER BY i.publishon ASC";
        $params = array('newsletterid' => $issue->newsletterid, 'publishon' => $issue->publishon, 'id' => $issue->id);
        $results = $DB->get_records_sql($query, $params);
        return empty($results) ? null : reset($results);
    }
	
    /**
     * Retrieve the last issue of a newsletter
     * 
     * @param object $issue (get_record object)
     * @return object <NULL, mixed> last issue as object or null
     */
    private function get_last_issue($issue) {
        global $DB;
        $query = "SELECT *
                    FROM {newsletter_issues} i
                   WHERE i.newsletterid = :newsletterid
                     AND i.publishon > :publishon
                     AND i.id != :id
                ORDER BY i.publishon DESC";
        $params = array('newsletterid' => $issue->newsletterid, 'publishon' => $issue->publishon, 'id' => $issue->id);
        $results = $DB->get_records_sql($query, $params);
        return empty($results) ? null : reset($results);
    }

    /**
     *  subscribe a user to a newsletter and return the subscription id if successful
     *  When user status is unsubscribed and $resubscribed_unsubscribed is true, user will be subscribed as active again
     *  When user is already subscribed and status is other than unsubscribed, the subscription status remains unchanged
     * 
     * @param number $userid
     * @param boolean $bulk set to true if multiple users are subscribed
     * @param string $status
     * @param boolean $resubscribe_unsubscribed true to resubscribe unsubscribed users
     * @param integer $instanceid only needed when newsletter instance is created
     * @return boolean|newid <boolean, number> 
     * subscriptionid for new subscription
     * false when user is subscribed and status remains unchanged
     * true when changed from unsubscribed to NEWSLETTER_SUBSCRIBER_STATUS_OK
     */
    public function subscribe($userid = 0, $bulk = false, $status = NEWSLETTER_SUBSCRIBER_STATUS_OK,$resubscribe_unsubscribed = false,$instanceid = 0) {
        global $DB, $USER;
        $now = time();

        if ($userid == 0) {
            $userid = $USER->id;
        }
        if($instanceid == 0){
        	$instanceid = $this->get_instance()->id;
        } 
        if ($sub = $DB->get_record("newsletter_subscriptions", array("userid" => $userid, "newsletterid" => $instanceid))) {
            if($sub->health == NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED && $resubscribe_unsubscribed) {
            	$sub->health = NEWSLETTER_SUBSCRIBER_STATUS_OK;
            	$sub->timestatuschanged = $now;
            	$sub->subscriberid = $USER->id;
            	return $DB->update_record('newsletter_subscriptions', $sub);
            } else {
            	return false;
            }
        } else {
        	$sub = new stdClass();
        	$sub->userid  = $userid;
        	$sub->newsletterid = $instanceid;
        	$sub->health = $status;
        	$sub->timesubscribed = $now;
        	$sub->timestatuschanged = $now;
        	$sub->subscriberid = $USER->id;
        	$result = $DB->insert_record("newsletter_subscriptions", $sub, true, $bulk);
        	if ($result){
        		$params = array(
        				'context' => $this->get_context(),
        				'objectid' => $result,
        				'relateduserid' => $userid,
        				'other' => array('newsletterid' => $sub->newsletterid),
        		
        		);
        		$event  = \mod_newsletter\event\subscription_created::create($params);
        		$event->trigger();
        	}
        	return $result;
        }
    }

    /**
     * updates health status for a subscription 
     * 
     * @param stdClass $data (id, health status and userid)
     */
    private function update_subscription(stdClass $data) {
        global $DB, $USER;

        if($data->health == NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED && $this->get_subscription_status($data->subscription) != NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED){
        	$this->unsubscribe($data->subscription);
        } else {
        	$subscription = new stdClass();
        	$subscription->id = $data->subscription;
        	$subscription->health = $data->health;
        	$subscription->timestatuschanged = time();
        	$DB->update_record('newsletter_subscriptions', $subscription);
        	
        	$params = array(
        			'context' => $this->get_context(),
        			'objectid' => $data->subscription,
        			'relateduserid' => $data->userid,
        			'other' => array('newsletterid' => $this->get_instance()->id, 'status' => $data->health),
        	
        	);
        	$event  = \mod_newsletter\event\subscription_statuschanged::create($params);
        	$event->trigger();
        }
    }

    /**
     * given the id of newsletter_subscriptions deletes the subscription completely (including health status)
     * 
     * @param integer $subid
     * @param integer $userid //used only for log data
     * @return boolean
     */
    public function delete_subscription($subid, $userid = 0) {
        global $DB;
        if ($userid == 0) {
        	$userid = $DB->get_field('newsletter_subscriptions', 'userid', array('id' => $subid));
        }
        $result = $DB->delete_records("newsletter_subscriptions", array('id' => $subid));
        
        $params = array(
        		'context' => $this->get_context(),
        		'objectid' => $subid,
        		'relateduserid' => $userid,
        		'other' => array('newsletterid' => $this->get_instance()->id),
        
        );
        $event  = \mod_newsletter\event\subscription_deleted::create($params);
        $event->trigger();
        
        return $result;
    }
    
	/**
	 * set health status to "unsubscribed" for this instance 
	 * of the newsletter
	 * 
	 * @param number $subscriptionid
	 * @param number $userid // only used for log data
	 * @return boolean
	 */
    public function unsubscribe($subid, $userid = 0) {
        global $DB, $USER;
        if ($userid == 0) {
        	$userid = $DB->get_field('newsletter_subscriptions', 'userid', array('id' => $subid));
        }
        
        $sub = new stdClass();
        $sub->id = $subid;
        $sub->userid = $userid;
        $sub->health = NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED;
        $sub->timestatuschanged = time();
        $sub->unsubscriberid = $USER->id;
        
        $result = $DB->update_record('newsletter_subscriptions', $sub);
        
        $params = array(
        		'context' => $this->get_context(),
        		'objectid' => $subid,
        		'relateduserid' => $userid,
        		'other' => array('newsletterid' => $this->get_instance()->id),
        
        );
        $event  = \mod_newsletter\event\subscription_unsubscribed::create($params);
        $event->trigger();
                
        return $result;
    }
	
    /**
     * Return true/false if user is subscribed to a newsletter
     * 
     * @param number $userid
     * @return boolean
     */
    public function is_subscribed($userid = 0) {
        global $DB, $USER;
        if (!$userid) {
            $userid = $USER->id;
        }
        return $DB->record_exists_select("newsletter_subscriptions", "userid = :userid AND newsletterid = :newsletterid AND health <> :health",
                                        array("userid" => $userid, "newsletterid" => $this->get_instance()->id, "health" => NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED));
    }

    /**
     * Return the subscription status (health) of a given subscription id
     *
     * @param number $subid
     * @return boolean
     */
    public function get_subscription_status($subid) {
    	global $DB;
    	return $DB->get_field("newsletter_subscriptions", 'health',	array("id" => $subid));
    }    
    
    /**
     * return the e-mail address that receives bounce mails
     * 
     * The bounce e-mail address is used to collect all bounces
     * that will be fetched and processed by the bounce processor
     */
    public function get_bounceemail_address() {
    	if (!$this->config) {
    		$settings = $this->get_config();
    	} else {
    		$config = $this->config;
    	}
    	
    	if ($this->config->enablebounce == '1' && filter_var($this->config->bounceemail, FILTER_VALIDATE_EMAIL)){
    		return $this->config->bounceemail;
    	} else {
    		return $CFG->noreplyaddress;
    	}
    }

    /**
     * Creates a new user and subscribes the user to the newsletter
     * 
     * TODO there are unsufficient checks for creating the user
     * TODO check if email already exists for another user if yes, then display message to login in order to subscribe
     * TODO unsufficient checks if user is already subscribed and has status "unsubscribed", in this case 
     * e-mail confirmation should be required
     * 
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @return boolean true when confirm mail was successfully sent to user, false when not
     */
    public function subscribe_guest($firstname, $lastname, $email) {
        global $DB, $CFG;
        require_once($CFG->dirroot.'/user/profile/lib.php');
        require_once($CFG->dirroot.'/user/lib.php');
		    
        if (empty($CFG->registerauth)) {
        	print_error('notlocalisederrormessage', 'error', '', 'Sorry, you may not use this page.');
        }
        $authplugin = get_auth_plugin($CFG->registerauth);
        
        if (!$authplugin->can_signup()) {
        	print_error('notlocalisederrormessage', 'error', '', 'Sorry, you may not use this page.');
        }
        
        //generate username and if it already exists try to find another username, repeat until new username found
        $cfirstname = preg_replace('/[^a-zA-Z]+/', '', iconv('UTF-8', 'US-ASCII//TRANSLIT', $firstname));
        $clastname = preg_replace('/[^a-zA-Z]+/', '', iconv('UTF-8', 'US-ASCII//TRANSLIT', $lastname));
        $username = strtolower(substr($cfirstname, 0, 1) . $clastname);
        $i = 0;
        do {
            $newusername = $username . ($i != 0 ? $i : '');
            $i++;
            $olduser = get_complete_user_data('username', $newusername);
        } while (!empty($olduser));
		
        $usercreated = false;
        
        $usernew = new stdClass();
        $usernew->username    = $newusername;
        $usernew->email       = $email;
        $usernew->firstname   = $firstname;
        $usernew->lastname    = $lastname;
        $usernew->auth = 'email';
        $usernew->confirmed = 0;
        $usernew->deleted = 0;
        $usernew->password    = $password = generate_password();
        $usernew->mailformat  = 1;
        $usernew->confirmed   = 0;
        $usernew->lang        = current_language();
        $usernew->firstaccess = time();
        $usernew->timecreated = time();
        $usernew->timemodified = time();
        $usernew->secret      = $secret = random_string(15);
       	$usernew->mnethostid = $CFG->mnet_localhost_id; // Always local user.
       	$usernew->timecreated = time();
       	$usernew->password = hash_internal_user_password($usernew->password);
       	$usernew->courseid = $this->get_course()->id;
        $usernew->id = user_create_user($usernew, false, false);
        /// Save any custom profile field information
        // profile_save_data($user);

        $user = $DB->get_record('user', array('id'=>$usernew->id));
        \core\event\user_created::create_from_userid($user->id)->trigger();
		
        $this->subscribe($user->id, false, NEWSLETTER_SUBSCRIBER_STATUS_OK);

        $cm = $this->get_course_module();
        $newslettername = $DB->get_field('newsletter', 'name', array('id' => $cm->instance));

        $data = "{$secret}-{$user->id}-{$cm->instance}";
        $activateurl = new moodle_url('/mod/newsletter/confirm.php', array(NEWSLETTER_PARAM_DATA => $data));

        $site = get_site();
        $a = array(
            'fullname' => fullname($user),
            'newslettername' => $newslettername,
            'sitename' => format_string($site->fullname),
            'email' => $email,
            'username' => $user->username,
            'password' => $password,
            'link' => $activateurl->__toString(),
            'admin' => generate_email_signoff());

        $htmlcontent = text_to_html(get_string('new_user_subscribe_message', 'newsletter', $a));

        if (!email_to_user($user, "newsletter", "Welcome", '', $htmlcontent)) {
            return false;
        }
        return true;
    }
    
    /**
     * Obtains WHERE clause to filter results by defined search for view managesubscriptions
     *
     * @return array Two-element array with SQL and params for WHERE clause
     */
    protected function get_filter_sql(array $getparams, $count = false) {
    	global $DB;
    	$allnamefields = user_picture::fields('u',null,'userid');
    	$extrafields = get_extra_user_fields($this->get_context());
    	if($count) {
    		$sql = "SELECT COUNT(*)
    		FROM {newsletter_subscriptions} ns
    		INNER JOIN {user} u ON ns.userid = u.id
    		WHERE ns.newsletterid = :newsletterid AND ";
    	} else {
    		$sql = "SELECT ns.*, $allnamefields
    		FROM {newsletter_subscriptions} ns
    		INNER JOIN {user} u ON ns.userid = u.id
    		WHERE ns.newsletterid = :newsletterid AND ";
    	}

    	$params = array('newsletterid' => $this->get_instance()->id);
    	
    	// Search condition (search for username)
	    list($usersql, $userparams) = users_search_sql($getparams['search'], 'u', true, $extrafields);
	    $sql .= $usersql;
	    $params += $userparams;
    	
    	// Status condition.
    	if($getparams['status'] != 10) {
    		$sql .= " AND ns.health = :status";
    		$params += array('status' => $getparams['status']);
    	} 
    	if($getparams['orderby'] != ''){
    		$sql .= " ORDER BY u." . $getparams['orderby']; 
    	}
    
    	return array($sql, $params);
    }
    
    /**
     * Saves a new instance of the newsletter into the database
     *
     * Given an object containing all the necessary data,
     * (defined by the form in mod_form.php) this function
     * will create a new instance and return the id number
     * of the new instance.
     *
     * @param object $newsletter An object from the form in mod_form.php
     * @param mod_newsletter_mod_form $mform
     * @return int The id of the newly inserted newsletter record
     */
    function add_instance(stdClass $newsletter, mod_newsletter_mod_form $mform = null) {
    	global $DB;
    	$now = time();
    	$newsletter->timecreated = $now;
    	$newsletter->timemodified = $now;
    	$newsletter->id = $DB->insert_record('newsletter', $newsletter);
    
    	$fileoptions = array('subdirs' => NEWSLETTER_FILE_OPTIONS_SUBDIRS,
    			'maxbytes' => 0,
    			'maxfiles' => -1);
    
    	$context = $this->get_context();
    
    	if ($mform && $mform->get_data() && $mform->get_data()->stylesheets) {
    		file_save_draft_area_files($mform->get_data()->stylesheets, $context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_STYLESHEET, $newsletter->id, $fileoptions);
    	}
    
    	if ($newsletter->subscriptionmode == NEWSLETTER_SUBSCRIPTION_MODE_OPT_OUT ||
    			$newsletter->subscriptionmode == NEWSLETTER_SUBSCRIPTION_MODE_FORCED) {
    				$users = get_enrolled_users($context,null,null,'u.id');
    				foreach ($users as $user) {
    					$this->subscribe($user->id, true, NEWSLETTER_SUBSCRIBER_STATUS_OK, false, $newsletter->id);
    				}
    			}
    	return $newsletter->id;
    }
    
    /**
     * Update this instance in the database.
     *
     * @param stdClass $formdata - the data submitted from the form
     * @return bool false if an error occurs
     */
    public function update_instance($data,$mform) {
    	global $DB;
    	
    	$now = time();
    	$data->timemodified = $now;
    	$data->id = $data->instance;
    	
    	$fileoptions = array('subdirs' => NEWSLETTER_FILE_OPTIONS_SUBDIRS,
    			'maxbytes' => 0,
    			'maxfiles' => -1);
    	
    	$context = $this->get_context();
    	
    	if ($mform && $mform->get_data() && $mform->get_data()->stylesheets) {
    		file_save_draft_area_files($mform->get_data()->stylesheets, $context->id, 'mod_newsletter', NEWSLETTER_FILE_AREA_STYLESHEET, $data->id, $fileoptions);
    	}
    	
    	if ($data->subscriptionmode == NEWSLETTER_SUBSCRIPTION_MODE_OPT_OUT ||
    			$data->subscriptionmode == NEWSLETTER_SUBSCRIPTION_MODE_FORCED) {
    				$users = get_enrolled_users($context,null,null,'u.id');
    				foreach ($users as $user) {
    					$this->subscribe($user->id, true);
    				}
    			}
    	return $DB->update_record('newsletter', $data);
    }
}
