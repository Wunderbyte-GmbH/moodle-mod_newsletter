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

namespace mod_newsletter;

use coding_exception;
use context_module;
use core\event\user_created;
use core_user;
use editor_tiny\editor;
use editor_tiny\manager;
use mod_newsletter_instance_store;
use newsletter_section_list;
use renderable;
use renderer_base;
use stdClass;
use context;
use moodle_url;
use html_writer;
use moodle_exception;
use repository;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;
use user_picture;
use lib;

defined('MOODLE_INTERNAL') || die();

require_once(dirname(__DIR__) . '/vendor/autoload.php');
require_once(dirname(__DIR__) . '/renderable.php');
//require_once(dirname(__DIR__) . '/CssToInlineStyles/CssToInlineStyles.php');
require_once(dirname(__FILE__) . '/subscription/subscription_filter_form.php');
require_once(dirname(__DIR__) . '/guest_signup_form.php');
require_once(dirname(__DIR__) . '/resubscribe_form.php');
require_once($CFG->dirroot.'/repository/lib.php');



class newsletter implements renderable {

    /** @var stdClass the newsletter record that contains the settings for this newsletter instance */
    private $instance = null;

    /** @var context of the course module for this newsletter instance (or just the course if we are creating a new one) */
    private $context;

    /** @var stdClass the course this newsletter instance belongs to */
    private $course = null;

    /** @var ?stdClass the course module for this assign instance */
    private ?stdClass $coursemodule = null;

    /** @var ?stdClass the admin config for all newsletter instances  */
    private ?stdClass $config = null;

    /** @var ?renderer_base the custom renderer for this module */
    private ?renderer_base $renderer = null;

    /** @var ?int the subscription id of $USER, if subscribed  */
    private ?int $subscriptionid = null;

    /** @var array of objects containing data records of newsletter issues sorted be issueid */
    private array $issues = array();

    /**
     * get a newsletter object by providing the id of the newsletter table (default: mdl_newsletter)
     *
     * @param int $newsletterid
     * @param bool $eagerload
     * @return newsletter the newsletter instance
     */
    public static function get_newsletter_by_instance(int $newsletterid, bool $eagerload = false): newsletter {
        $cm = get_coursemodule_from_instance('newsletter', $newsletterid);
        $context = context_module::instance($cm->id);
        return self::create_newsletter_instance($context, $eagerload);
    }

    /**
     * get a newsletter object by providing the course module id (view.php?id=xxx)
     *
     * @param int $cmid
     * @param bool $eagerload
     * @return newsletter
     */
    public static function get_newsletter_by_course_module(int $cmid, bool $eagerload = false): newsletter {
        $context = context_module::instance($cmid);
        return self::create_newsletter_instance($context, $eagerload);
    }

    /**
     * When not cached create the newsletter instance otherwise return it from the cache
     *
     * @param context_module $context
     * @param bool $eagerload
     * @return newsletter $nl the newsletter instance
     */
    private static function create_newsletter_instance(context_module $context, bool $eagerload): newsletter {
        $cmid = $context->id;
        if (!$nl = mod_newsletter_instance_store::instance($cmid, 'newsletter')) {
            $nl = new newsletter($context, $eagerload);
            mod_newsletter_instance_store::register($cmid, 'newsletter', $nl);
        }
        return $nl;
    }

    /**
     * Constructor for the newsletter class. Always use get_newsletter_by_instance
     * or get_newsletter_by_course_module to get a new instance Do not use
     * $nl = new \newsletter($context)!!!
     *
     * @param context_module $context
     * @param boolean $eagerload
     */
    public function __construct(context_module $context, bool $eagerload = false) {
        global $DB;
        $this->context = $context;
        if ($eagerload) {
            $this->coursemodule = get_coursemodule_from_id(
                'newsletter',
                $this->context->instanceid,
                0,
                false,
                MUST_EXIST
            );
            $this->course = $DB->get_record(
                'course',
                array('id' => $this->coursemodule->course),
                '*',
                MUST_EXIST
            );
            $this->instance = $DB->get_record(
                'newsletter',
                array('id' => $this->get_course_module()->instance),
                '*',
                MUST_EXIST
            );
            $this->config = get_config('mod_newsletter');
            $this->context = $context;
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
     * @return ?stdClass The course module
     */
    public function get_course_module(): ?stdClass {
        if (!$this->coursemodule) {
            if ($this->context && $this->context->contextlevel == CONTEXT_MODULE) {
                $this->coursemodule = get_coursemodule_from_id(
                    'newsletter',
                    $this->context->instanceid,
                    0,
                    false,
                    MUST_EXIST
                );
            }
        }
        return $this->coursemodule;
    }

    /**
     * Get the settings for the current instance of this newsletter.
     *
     * @return object The settings
     */
    public function get_instance(): object {
        global $DB;
        if (!$this->instance) {
            if ($this->get_course_module()) {
                $this->instance = $DB->get_record(
                    'newsletter',
                    array('id' => $this->get_course_module()->instance),
                    '*',
                    MUST_EXIST
                );
            } else {
                throw new coding_exception(
                    'Improper use of the newsletter class. Cannot load the newsletter record.'
                );
            }
        }
        return $this->instance;
    }

    /**
     * Get subscription id of user if $userid = 0 id of current user is returned
     *
     * @param int $userid
     * @return int|boolean subscriptionid | false if no subscription is found
     */
    public function get_subid(int $userid = 0) {
        global $DB, $USER;
        if ($userid === 0 && !$this->subscriptionid) {
            $this->subscriptionid = $DB->get_field(
                'newsletter_subscriptions',
                'id',
                array('userid' => $USER->id, 'newsletterid' => $this->get_instance()->id)
            );
            return $this->subscriptionid;
        } else {
            return $DB->get_field(
                'newsletter_subscriptions',
                'id',
                array('userid' => $userid, 'newsletterid' => $this->get_instance()->id)
            );
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
                $this->course = $DB->get_record(
                    'course',
                    array('id' => $this->get_instance()->course),
                    '*',
                    MUST_EXIST
                );
            }
        }
        return $this->course;
    }

    /**
     * Get the module renderer
     *
     * @return renderer_base The module renderer
     */
    public function get_renderer(): renderer_base {
        global $PAGE;
        if (!$this->renderer) {
            $this->renderer = $PAGE->get_renderer('mod_newsletter');
        }
        return $this->renderer;
    }

    /**
     * get global newsletter settings (admin settings)
     *
     * @return object configuration object
     */
    public function get_config(): object {
        if (!$this->config) {
            $this->config = get_config('mod_newsletter');
        }
        return $this->config;
    }

    /**
     * Reset user data
     *
     * @param \stdClass $data
     * @return array
     */
    public function reset_userdata($data) {
        global $DB;
        $componentstr = get_string('modulenameplural', 'mod_newsletter');
        $status = array();
        $newsletterssql = "SELECT n.id
                             FROM {newsletter} n
                            WHERE n.course = :course";
        $params = array("course" => $data->courseid);
        $nlids = $DB->get_fieldset_sql($newsletterssql, $params);
        list($sql, $inparams) = $DB->get_in_or_equal($nlids);

        $DB->delete_records_select('newsletter_subscriptions', "newsletterid $sql", $inparams);
        $DB->delete_records_select('newsletter_deliveries', "newsletterid $sql", $inparams);
        $status[] = array(
            'component' => $componentstr,
            'item' => get_string('delete_all_subscriptions', 'newsletter'), 'error' => false
        );
        return $status;
    }

    /**
     * returns an array of localised subscription status names with according key stored in database column "health"
     *
     * @return array
     */
    public function get_subscription_statuslist() {
        return array(
            NEWSLETTER_SUBSCRIBER_STATUS_OK => get_string('health_0', 'mod_newsletter'),
            NEWSLETTER_SUBSCRIBER_STATUS_PROBLEMATIC => get_string('health_1', 'mod_newsletter'),
            NEWSLETTER_SUBSCRIBER_STATUS_BLACKLISTED => get_string('health_2', 'mod_newsletter'),
            NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED => get_string('health_4', 'mod_newsletter')
        );
    }

    /**
     * Render the view according to passed parameters
     *
     * @return string rendered view
     */
    public function view($params): string {
        $output = '';
        switch ($params[NEWSLETTER_PARAM_ACTION]) {
            case NEWSLETTER_ACTION_VIEW_NEWSLETTER:
                require_capability('mod/newsletter:viewnewsletter', $this->context);
                $output = $this->view_newsletter($params);
                break;
            case NEWSLETTER_ACTION_CREATE_ISSUE:
                require_capability('mod/newsletter:createissue', $this->context);
                $output = $this->view_edit_issue_page($params);
                break;
            case NEWSLETTER_ACTION_DUPLICATE_ISSUE:
                require_capability('mod/newsletter:createissue', $this->context);
                $this->duplicate_issue($params['issue']);
                $output = $this->view_newsletter($params);
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
                if ($this->get_subscription_status($this->get_subid()) == NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED) {
                    $output = $this->display_resubscribe_form($params);
                } else {
                    $this->subscribe();
                    $url = $this->get_url();
                    redirect($url);
                }
                break;
            case NEWSLETTER_ACTION_UNSUBSCRIBE:
                require_capability('mod/newsletter:manageownsubscription', $this->context);
                $this->unsubscribe($this->get_subid());
                $url = $this->get_url();
                redirect($url);
                break;
            case NEWSLETTER_ACTION_GUESTSUBSCRIBE:
                require_capability('mod/newsletter:viewnewsletter', $this->context);
                $output = $this->display_guest_subscribe_form($params);
                break;
            default:
                throw new moodle_exception (
                    'Wrong ' . NEWSLETTER_PARAM_ACTION . ' parameter value: ' . $params[NEWSLETTER_PARAM_ACTION]
                );
        }
        return $output;
    }

    /**
     * display a subscription form for guest users.
     * requires email auth plugin to be enabled
     *
     * @param array $params url params passed as get variables
     * @return string html rendered guest subscription
     */
    private function display_guest_subscribe_form(array $params): string {
        global $PAGE;
        $authplugin = get_auth_plugin('email');
        if (!$authplugin->can_signup()) {
            throw new moodle_exception ('notlocalisederrormessage', 'error', '', 'Sorry, you may not use this page.');
        }
        $output = '';
        $renderer = $this->get_renderer();
        $output .= $renderer->render(
                new \newsletter_header(
                        $this->get_instance(),
                        $this->get_context(),
                        false,
                        $this->get_course_module()->id,
                        $params['embed']
                )
        );
        $mform = new \mod_newsletter_guest_signup_form(
            null,
            array(
                'id' => $this->get_course_module()->id,
                NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_GUESTSUBSCRIBE,
                'embed' => $params['embed']
            )
        );
        if ($mform->is_cancelled()) {
            redirect(new moodle_url('view.php', array(
                'id' => $this->get_course_module()->id,
                NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_VIEW_NEWSLETTER,
                    'embed' => $params['embed']
            )));
        } else if ($data = $mform->get_data()) {
            $this->subscribe_guest($data->firstname, $data->lastname, $data->email);
            $a = $data->email;
            $output .= html_writer::div(get_string('guestsubscriptionsuccess', 'newsletter', $a));
            $url = $this->get_url();
            if(isset($data->embed)) {
                $url->params(['embed' => $data->embed]);
            }
            $output .= html_writer::link(
                $url,
                get_string('continue'),
                array('class' => 'btn mdl-align', 'target' => '_top')
            );
            return $output;
        } else if ($this->get_instance()->allowguestusersubscriptions && (!isloggedin() || isguestuser())) {
            $output .= $renderer->render(new \newsletter_form($mform, null));
            $output .= $renderer->render_footer();
            return $output;
        }
        return $output;
    }

    /**
     * display a resubscription form for users who are unsubscribed and want to subscribe again.
     *
     * @param array $params url params passed as get variables
     * @return string html rendered resubscription
     */
    private function display_resubscribe_form(array $params): string {
        global $PAGE;
        $output = '';
        $renderer = $this->get_renderer();

        $output .= $renderer->render(
            new \newsletter_header(
                $this->get_instance(),
                $this->get_context(),
                false,
                $this->get_course_module()->id
            )
        );
        $mform = new \mod_newsletter_resubscribe_form(
            null,
            array(
                'id' => $this->get_course_module()->id,
                NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_SUBSCRIBE
            )
        );

        if ($mform->is_cancelled()) {
            redirect(
                new moodle_url(
                    'view.php',
                    array(
                        'id' => $this->get_course_module()->id,
                        NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_VIEW_NEWSLETTER
                    )
                )
            );
        } else if ($data = $mform->get_data()) {
            if ($data->resubscribe_confirmation) {
                $this->subscribe(0, false, NEWSLETTER_SUBSCRIBER_STATUS_OK, true, 0);
                $output .= html_writer::div('&nbsp;');
                $output .= html_writer::div(get_string('resubscriptionsuccess', 'mod_newsletter'));
                $output .= html_writer::div('&nbsp;');
                $url = $this->get_url();
                $output .= html_writer::link(
                    $url,
                    get_string('continue'),
                    array('class' => 'btn mdl-align')
                );
                $output .= $renderer->render_footer();
                return $output;
            } else {
                redirect(
                    new moodle_url(
                        'view.php',
                        array(
                            'id' => $this->get_course_module()->id,
                            NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_VIEW_NEWSLETTER
                        )
                    )
                );
            }
        } else {
            $output .= $renderer->render(new \newsletter_form($mform, null));
            $output .= $renderer->render_footer();
            return $output;
        }
        return $output;
    }

    /**
     * Display all newsletter issues in a view. Display action links according to capabilities
     *
     * @param array $params
     * @return string html
     */
    private function view_newsletter(array $params): string {
        global $CFG;
        $renderer = $this->get_renderer();

        $output = '';
        $output .= $renderer->render(
            new \newsletter_header(
                $this->get_instance(),
                $this->get_context(),
                false,
                $this->get_course_module()->id
            )
        );
        $str = file_rewrite_pluginfile_urls(
            $this->get_instance()->intro,
            'pluginfile.php',
            $this->get_context()->id,
            'mod_newsletter',
            'intro',
            $this->get_course_module()->id
        );
        $output .= $renderer->render(
            new \newsletter_main_toolbar(
                $this->get_course_module()->id,
                $params[NEWSLETTER_PARAM_GROUP_BY],
                has_capability('mod/newsletter:createissue', $this->context),
                has_capability('mod/newsletter:managesubscriptions', $this->context)
            )
        );

        if (
            has_capability('mod/newsletter:manageownsubscription', $this->context) &&
            $this->instance->subscriptionmode != NEWSLETTER_SUBSCRIPTION_MODE_FORCED
        ) {
            if (!$this->is_subscribed()) {
                $url = $this->get_subsribe_url();
                $text = get_string('subscribe', 'mod_newsletter');
                $output .= html_writer::link($url, $text, ['class', 'btn btn-primary m-2']);
            } else {
                $url = new moodle_url(
                    '/mod/newsletter/view.php',
                    array(
                        NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
                        NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_UNSUBSCRIBE
                    )
                );
                $text = get_string('unsubscribe', 'mod_newsletter');
                $output .= html_writer::link($url, $text, ['class' => 'btn btn-primary m-2']);
            }
        } else {
            if (!empty($CFG->registerauth) and is_enabled_auth('email')) {
                $guestsignuppossible = true;
            } else {
                $guestsignuppossible = false;
            }
            if ($this->get_instance()->allowguestusersubscriptions && (!isloggedin() || isguestuser()) && $guestsignuppossible) {
                $url = new moodle_url(
                    '/mod/newsletter/view.php',
                    array(
                        NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
                        NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_GUESTSUBSCRIBE
                    )
                );
                $text = get_string('subscribe', 'mod_newsletter');
                $output .= html_writer::link($url, $text, array('class' => 'btn btn-primary m-2'));
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
        if (!(has_capability('mod/newsletter:editissue', $this->get_context())) && $this->get_issue(
            $params[NEWSLETTER_PARAM_ISSUE]
        )->publishon > time()) {
            require_capability('mod/newsletter:editissue', $this->get_context());
        }
        if (!(has_capability('mod/newsletter:createissue', $this->get_context())) && $this->get_issue(
            $params[NEWSLETTER_PARAM_ISSUE]
        )->publishon > time()) {
            require_capability('mod/newsletter:createissue', $this->get_context());
        }
        $renderer = $this->get_renderer();

        $output = $renderer->render(
            new \newsletter_header(
                $this->get_instance(),
                $this->get_context(),
                false,
                $this->get_course_module()->id
            )
        );
        $currentissue = $this->get_issue($params[NEWSLETTER_PARAM_ISSUE]);

        if (has_capability('mod/newsletter:editissue', $this->get_context())) {
            $url = new moodle_url(
                '/mod/newsletter/view.php',
                array(
                    NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
                    'action' => NEWSLETTER_ACTION_EDIT_ISSUE,
                    NEWSLETTER_PARAM_ISSUE => $currentissue->id
                )
            );
            $output .= $renderer->render(
                new \newsletter_action_link(
                    $url,
                    get_string('edit_issue', 'mod_newsletter'),
                    'btn btn-default'
                )
            );
        }

        $navigationbar = new \newsletter_navigation_bar(
            $currentissue,
            $this->get_first_issue($currentissue),
            $this->get_previous_issue($currentissue),
            $this->get_next_issue($currentissue),
            $this->get_last_issue($currentissue)
        );
        $output .= $renderer->render($navigationbar);

        // Generate table of content.
        $parsedhtml = new \mod_newsletter\issue_parser($currentissue);
        $currentissue->htmlcontent = $parsedhtml->get_parsed_html();

        // Render the html with inline css based on the used stylesheet.
        $currentissue->htmlcontent = file_rewrite_pluginfile_urls(
            $currentissue->htmlcontent,
            'pluginfile.php',
            $this->get_context()->id,
            'mod_newsletter',
            NEWSLETTER_FILE_AREA_ISSUE,
            $params[NEWSLETTER_PARAM_ISSUE],
            issue_form::editor_options(
                $this->get_context(),
                $params[NEWSLETTER_PARAM_ISSUE]
            )
        );
        $currentissue->htmlcontent = $this->inline_css(
            $currentissue->htmlcontent,
            $currentissue->stylesheetid
        );

        $output .= $renderer->render(new \newsletter_issue($currentissue));

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $this->get_context()->id,
            'mod_newsletter',
            NEWSLETTER_FILE_AREA_ATTACHMENT,
            $currentissue->id,
            "",
            false
        );
        foreach ($files as $file) {
            $file->link = file_encode_url(
                $CFG->wwwroot . '/pluginfile.php',
                '/' . $this->get_context()->id . '/mod_newsletter/attachments/' . $currentissue->id . '/' . $file->get_filename()
            );
        }

        if (!empty($files)) {
            $output .= $renderer->render(new \newsletter_attachment_list($files));
        } else {
            $output .= $renderer->render_newsletter_attachment_list_empty();
        }
        if (has_capability('mod/newsletter:editissue', $this->get_context())) {
            $output .= $renderer->render(
                new \newsletter_action_link(
                    $url,
                    get_string('edit_issue', 'mod_newsletter'),
                    'btn btn-default'
                )
            );
        }
        $output .= $renderer->render($navigationbar);
        $output .= $renderer->render_footer();

        $params = array(
            'context' => $this->get_context(),
            'objectid' => $params[NEWSLETTER_PARAM_ISSUE],
            'other' => array('newsletterid' => $this->get_instance()->id)
        );

        $event = \mod_newsletter\event\issue_viewed::create($params);
        $event->trigger();

        return $output;
    }

    /**
     * delete a single issue of a newsletter can only be done when not yet sent
     *
     * @param array $params
     * @return string rendered html
     */
    private function view_delete_issue_page(array $params) {
        global $OUTPUT;
        if (!$params[NEWSLETTER_PARAM_ISSUE] || !$this->check_issue_id(
            $params[NEWSLETTER_PARAM_ISSUE]
        )) {
            throw new moodle_exception (
                'Wrong ' . NEWSLETTER_PARAM_ISSUE . ' parameter value: ' . $params[NEWSLETTER_PARAM_ISSUE]
            );
        }

        if ($params[NEWSLETTER_PARAM_CONFIRM] != NEWSLETTER_CONFIRM_UNKNOWN) {
            $url = $this->get_url();
            if ($params[NEWSLETTER_PARAM_CONFIRM] == NEWSLETTER_CONFIRM_YES) {
                $this->delete_issue($params[NEWSLETTER_PARAM_ISSUE]);
                redirect($url);
            } else if ($params[NEWSLETTER_PARAM_CONFIRM] == NEWSLETTER_CONFIRM_NO) {
                redirect($url);
            } else {
                throw new moodle_exception ("Wrong confirm!");
            }
        }

        $renderer = $this->get_renderer();
        $output = '';
        $output .= $renderer->render(
            new \newsletter_header(
                $this->get_instance(),
                $this->get_context(),
                false,
                $this->get_course_module()->id
            )
        );
        $url = new moodle_url(
            '/mod/newsletter/view.php',
            array(
                NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
                NEWSLETTER_PARAM_ISSUE => $params[NEWSLETTER_PARAM_ISSUE],
                NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_DELETE_ISSUE
            )
        );
        $output .= $OUTPUT->confirm(
            get_string('delete_issue_question', 'mod_newsletter'),
            new moodle_url($url, array(NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_YES)),
            new moodle_url($url, array(NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_NO))
        );
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
        global $CFG;

        $options = array('subdirs' => 0, 'maxbytes' => 0, 'maxfiles' => 0, 'changeformat' => 0,
        'areamaxbytes' => FILE_AREA_MAX_BYTES_UNLIMITED, 'context' => $this->get_context(), 'noclean' => 0, 'trusttext' => 0,
        'return_types' => 15, 'enable_filemanagement' => true, 'removeorphaneddrafts' => false, 'autosave' => true);

        $draftitemid = file_get_submitted_draft_itemid('attachments');
        $ctx = $options['context'];

        if (!$this->check_issue_id($params[NEWSLETTER_PARAM_ISSUE])) {
            throw new moodle_exception (
                'Wrong ' . NEWSLETTER_PARAM_ISSUE . ' parameter value: ' . $params[NEWSLETTER_PARAM_ISSUE]
            );
        }

        $context = $this->get_context();
        $issue = $this->get_issue($params[NEWSLETTER_PARAM_ISSUE]);
        $newsletterconfig = $this->get_config();
        if (is_null($issue)) {
            $issue = new stdClass();
            $issue->htmlcontent = '';
            $issue->id = 0;
            $issue->title = '';
            $issue->publishon = strtotime("+48 hours");
            $issue->toc = 0;
            $issue->stylesheetid = NEWSLETTER_DEFAULT_STYLESHEET;
            $issue->delivered = NEWSLETTER_DELIVERY_STATUS_UNKNOWN;
        }
        // Publishon can not be altered, if delivery has already started or been completed.
        $deliverystartedorcompleted = 'yes';
        if ($issue->delivered == NEWSLETTER_DELIVERY_STATUS_UNKNOWN || $issue->delivered == NEWSLETTER_DELIVERY_STATUS_FAILED) {
            $deliverystartedorcompleted = 'no';
        }

        $issue->messageformat = editors_get_preferred_format();
        $issue->newsletterid = $this->get_instance()->id;

        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $context->id,
            'mod_newsletter',
            NEWSLETTER_FILE_AREA_STYLESHEET,
            $this->get_instance()->id,
            'filename',
            false
        );

        $fpoptions = array();
        if($options['maxfiles'] != 0 ) {
            $args = new stdClass();

            $args->accepted_types = array('web_image');
            $args->return_types = $options['return_types'];
            $args->context = $ctx;
            $args->env = 'filepicker';

            $image_options = initialise_filepicker($args);
            $image_options->context = $ctx;
            $image_options->client_id = uniqid();
            $image_options->maxbytes = $options['maxbytes'];
            $image_options->areamaxbytes = $options['areamaxbytes'];
            $image_options->env = 'editor';
            $image_options->itemid = $draftitemid;

            $args->accepted_types = array('video', 'audio');
            $media_options = initialise_filepicker($args);
            $media_options->context = $ctx;
            $media_options->client_id = uniqid();
            $media_options->maxbytes  = $options['maxbytes'];
            $media_options->areamaxbytes  = $options['areamaxbytes'];
            $media_options->env = 'editor';
            $media_options->itemid = $draftitemid;

            $args->accepted_types = '*';
            $link_options = initialise_filepicker($args);
            $link_options->context = $ctx;
            $link_options->client_id = uniqid();
            $link_options->maxbytes  = $options['maxbytes'];
            $link_options->areamaxbytes  = $options['areamaxbytes'];
            $link_options->env = 'editor';
            $link_options->itemid = $draftitemid;

            $args->accepted_types = array('.vtt');
            $subtitle_options = initialise_filepicker($args);
            $subtitle_options->context = $ctx;
            $subtitle_options->client_id = uniqid();
            $subtitle_options->maxbytes  = $options['maxbytes'];
            $subtitle_options->areamaxbytes  = $options['areamaxbytes'];
            $subtitle_options->env = 'editor';
            $subtitle_options->itemid = $draftitemid;

            $args->accepted_types = ['h5p'];
            $h5poptions = initialise_filepicker($args);
            $h5poptions->context = $this->context;
            $h5poptions->client_id = uniqid();
            $h5poptions->maxbytes  = $options['maxbytes'];
            $h5poptions->env = 'editor';
            $h5poptions->itemid = $draftitemid;

            $fpoptions['image'] = $image_options;
            $fpoptions['media'] = $media_options;
            $fpoptions['link'] = $link_options;
            $fpoptions['subtitle'] = $subtitle_options;
            $fpoptions['h5p'] = $h5poptions;
        }
        
        $editor = new newsletter_editor();
        $editor->use_editor('id_htmlcontent', $options, $fpoptions, $issue, $files);
        $mform = new issue_form(
            null,
            array('newsletter' => $this, 'issue' => $issue, 'context' => $context)
        );

        file_prepare_draft_area(
            $draftitemid,
            $context->id,
            'mod_newsletter',
            NEWSLETTER_FILE_AREA_ATTACHMENT,
            empty($issue->id) ? null : $issue->id,
            issue_form::attachment_options($newsletterconfig, $this->get_context(), 10)
        );

        $issueid = empty($issue->id) ? null : $issue->id;
        $draftideditor = file_get_submitted_draft_itemid('htmlcontent');
        $currenttext = file_prepare_draft_area(
            $draftideditor,
            $context->id,
            'mod_newsletter',
            NEWSLETTER_FILE_AREA_ISSUE,
            $issueid,
            issue_form::editor_options($context, $issueid),
            $issue->htmlcontent
        );

        $setarray = array(
            'attachments' => $draftitemid, 'title' => $issue->title,
            'htmlcontent' => array(
                'text' => $currenttext,
                'format' => empty($issue->messageformat) ? editors_get_preferred_format() : $issue->messageformat,
                'itemid' => $draftideditor
            ), 'deliverystarted' => $deliverystartedorcompleted,
            'toc' => $issue->toc, 'publishon' => $issue->publishon,
            'stylesheetid' => $issue->stylesheetid
        );

        // When we only want to recalculate filter.
        if ($mform->no_submit_button_pressed()) {
            $data = $mform->get_submitted_data();
            if ($data->issue) {
                $issue = $this->update_issue($data);
            } else {
                // We need the new issue object.
                $issue = $this->return_issue_from_form_data($data);
            }
            // Now we set the form with the right values, before it will be rendered again.
            // As we want a static element to be rendered again, we recreate the whole form.
            $mform = new issue_form(
                null,
                array('newsletter' => $this, 'issue' => $issue, 'context' => $context)
            );
        } else if ($data = $mform->get_data()) {
            if (!$data->issue) {
                $this->add_issue($data);
            } else {
                $this->update_issue($data);
            }
            redirect($this->get_url());
        }

        userfilter::set_form_values($issue, $setarray);
        $mform->set_data($setarray);

        $output = '';
        $renderer = $this->get_renderer();

        $output .= $renderer->render(
            new \newsletter_header(
                $this->get_instance(),
                $this->get_context(),
                false,
                $this->get_course_module()->id
            )
        );
        // TODO: remove ugly config hack and provide js for atto.
        $texteditors = $CFG->texteditors;
        $CFG->texteditors = 'tiny';
        $output .= $renderer->render(
            new \newsletter_form($mform, get_string('edit_issue_title', 'mod_newsletter'))
        );
        $CFG->texteditors = $texteditors;
        $output .= $renderer->render_footer();
        return $output;
    }

    /**
     * Display a list of users with statusses and action links in order to manage single subscriptions
     *
     * @param array $params
     * @return string rendered html
     */
    private function view_manage_subscriptions(array $params) {
        global $DB, $OUTPUT;

        $url = new moodle_url(
            '/mod/newsletter/view.php',
            array(
                'id' => $this->get_course_module()->id,
                'action' => NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS
            )
        );

        list($filtersql, $filterparams) = $this->get_filter_sql($params);
        if ($params['resetbutton'] !== '') {
            redirect($url);
        }
        $from = $params[NEWSLETTER_PARAM_FROM];
        $count = $params[NEWSLETTER_PARAM_COUNT];
        $subscriptions = $DB->get_records_sql($filtersql, $filterparams, $from, $count);

        $sqlparams = array('newsletterid' => $this->get_instance()->id);
        $total = $DB->count_records('newsletter_subscriptions', $sqlparams);
        list($countsql, $countparams) = $this->get_filter_sql($params, true);
        if ($countsql > 0) {
            $totalfiltered = $DB->count_records_sql($countsql, $countparams);
        } else {
            $totalfiltered = 0;
        }
        $pages = $this->calculate_pages($totalfiltered, $from, $count);

        $columns = array(
            NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_EMAIL,
            NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_NAME, NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_HEALTH,
            NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_BOUNCERATIO,
            NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_TIMESUBSCRIBED,
            NEWSLETTER_SUBSCRIPTION_LIST_COLUMN_ACTIONS
        );

        $filterform = new \mod_newsletter\subscription\mod_newsletter_subscription_filter_form(
            'view.php',
            array('newsletter' => $this),
            'get',
            '',
            array('id' => 'filterform')
        );
        $filterform->set_data(
            array(
                'search' => $params['search'], 'status' => $params['status'],
                'count' => $params['count'], 'orderby' => $params['orderby']
            )
        );

        $renderer = $this->get_renderer();
        $output = '';
        $output .= $renderer->render(
            new \newsletter_header(
                $this->get_instance(),
                $this->get_context(),
                false,
                $this->get_course_module()->id
            )
        );
        $newurl = $url;
        $newurl->params($params);

        require_once(dirname(__FILE__) . '/subscription/subscriptions_admin_form.php');
        $cohorts = \cohort_get_available_cohorts($this->get_context());
        if (!empty($cohorts)) {
            $mform = new \mod_newsletter\subscription\mod_newsletter_subscriptions_admin_form(
                null,
                array('id' => $this->get_course_module()->id, 'course' => $this->get_course())
            );
            if ($data = $mform->get_data()) {
                if (isset($data->subscribe)) {
                    foreach ($data->cohorts as $cohortid) {
                        $this->subscribe_cohort($cohortid);
                    }
                } else if (isset($data->unsubscribe)) {
                    foreach ($data->cohorts as $cohortid) {
                        $this->unsubscribe_cohort($cohortid);
                    }
                } else {
                    throw new moodle_exception ("Wrong submit!");
                }
                redirect($url);
            }
        }

        require_once(dirname(__FILE__) . '/subscription/newsletter_user_subscription.php');
        $subscriberselector = new \mod_newsletter\subscription\mod_newsletter_potential_subscribers(
            'subsribeusers',
            array(
                'courseid' => $this->get_course()->id,
                'newsletterid' => $this->get_instance()->id
            )
        );
        $subscribedusers = new \mod_newsletter\subscription\mod_newsletter_existing_subscribers(
            'subscribedusers',
            array('newsletterid' => $this->get_instance()->id, 'newsletter' => $this)
        );

        if (optional_param('add', false, PARAM_BOOL) && confirm_sesskey()) {
            $userstosubscribe = $subscriberselector->get_selected_users();
            if (!empty($userstosubscribe)) {
                foreach ($userstosubscribe as $user) {
                    $this->subscribe($user->id, false, NEWSLETTER_SUBSCRIBER_STATUS_OK);
                }
            }
            $subscriberselector->invalidate_selected_users();
            $subscribedusers->invalidate_selected_users();
        }

        if (optional_param('unsubscribe', false, PARAM_BOOL) && confirm_sesskey()) {
            $userstoremove = $subscribedusers->get_selected_users();
            if (!empty($userstoremove)) {
                foreach ($userstoremove as $user) {
                    $this->unsubscribe($user->subid, $user->id);
                }
            }
            $subscriberselector->invalidate_selected_users();
            $subscribedusers->invalidate_selected_users();
        }

        if (optional_param('remove', false, PARAM_BOOL) && confirm_sesskey()) {
            $userstoremove = $subscribedusers->get_selected_users();
            if (!empty($userstoremove)) {
                foreach ($userstoremove as $user) {
                    $this->delete_subscription($user->subid, $user->id);
                }
            }
            $subscriberselector->invalidate_selected_users();
            $subscribedusers->invalidate_selected_users();
        }

        require_once(dirname(__FILE__) . '/subscription/subscriber_selector_form.php');
        $subscriberform = new \mod_newsletter\subscription\mod_newsletter_subscriber_selector_form(
            null,
            array(
                'id' => $this->get_course_module()->id, 'course' => $this->get_course(),
                'existing' => $subscribedusers, 'potential' => $subscriberselector,
                'leftarrow' => $OUTPUT->larrow(), 'rightarrow' => $OUTPUT->rarrow()
            )
        );

        $output .= $renderer->render(new \newsletter_form($subscriberform, null));
        if (!empty($cohorts)) {
            $output .= $renderer->render(new \newsletter_form($mform, null));
        }
        $output .= $renderer->render(new \newsletter_form($filterform));
        $output .= $renderer->render(
            new \newsletter_pager($newurl, $from, $count, $pages, $total, $totalfiltered)
        );
        $output .= $renderer->render(
            new \newsletter_subscription_list(
                $this->get_course_module()->id,
                $subscriptions,
                $columns
            )
        );
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
     * Get number of delivered issues
     *
     * @param $userid
     * @param $issueid
     * @return int
     * @throws \dml_exception
     */
    public static function get_delivered_issues($userid): int {
        global $DB;
        $delivered = $DB->count_records('newsletter_deliveries', array('userid' => $userid));
        return $delivered;
    }

    /**
     * Display edit form for editing the status of a single subscription of a single user
     *
     * @param array $params
     * @return string rendered html with form
     */
    private function view_edit_subscription(array $params) {
        global $DB;
        $subscription = $DB->get_record(
            'newsletter_subscriptions',
            array('id' => $params[NEWSLETTER_PARAM_SUBSCRIPTION])
        );
        require_once(dirname(__FILE__) . '/subscription/subscription_form.php');
        $mform = new \mod_newsletter\subscription\mod_newsletter_subscription_form(
            null,
            array('newsletter' => $this, 'subscription' => $subscription)
        );

        if ($mform->is_cancelled()) {
            redirect(new moodle_url('view.php', array(
                'id' => $this->get_course_module()->id,
                NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS
            )));
        } else if ($data = $mform->get_data()) {
            $this->update_subscription($data);
            $url = new moodle_url(
                '/mod/newsletter/view.php',
                array(
                    NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
                    NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS
                )
            );
            redirect($url);
        }

        $output = '';
        $renderer = $this->get_renderer();

        $output .= $renderer->render(
            new \newsletter_header(
                $this->get_instance(),
                $this->get_context(),
                false,
                $this->get_course_module()->id
            )
        );
        $output .= $renderer->render(
            new \newsletter_form($mform, get_string('edit_subscription_title', 'mod_newsletter'))
        );
        $output .= $renderer->render_footer();
        return $output;
    }

    /**
     * Display deletion dialogue for deleting a single subscription of a user and delete user upon confirmation
     *
     * @param array $params
     * @return string rendered html
     */
    private function view_delete_subscription(array $params) {
        global $OUTPUT;

        if ($params[NEWSLETTER_PARAM_CONFIRM] != NEWSLETTER_CONFIRM_UNKNOWN) {
            $redirecturl = new moodle_url(
                '/mod/newsletter/view.php',
                array(
                    NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
                    NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_MANAGE_SUBSCRIPTIONS
                )
            );
            if ($params[NEWSLETTER_PARAM_CONFIRM] == NEWSLETTER_CONFIRM_YES) {
                $this->delete_subscription($params[NEWSLETTER_PARAM_SUBSCRIPTION]);
                redirect($redirecturl);
            } else if ($params[NEWSLETTER_PARAM_CONFIRM] == NEWSLETTER_CONFIRM_NO) {
                redirect($redirecturl);
            } else {
                throw new moodle_exception ("Wrong confirm!");
            }
        }

        $renderer = $this->get_renderer();
        $output = $renderer->render(
            new \newsletter_header(
                $this->get_instance(),
                $this->get_context(),
                false,
                $this->get_course_module()->id
            )
        );

        $url = new moodle_url(
            '/mod/newsletter/view.php',
            array(
                NEWSLETTER_PARAM_ID => $this->get_course_module()->id,
                NEWSLETTER_PARAM_ACTION => NEWSLETTER_ACTION_DELETE_SUBSCRIPTION,
                NEWSLETTER_PARAM_SUBSCRIPTION => $params[NEWSLETTER_PARAM_SUBSCRIPTION]
            )
        );
        $output .= $OUTPUT->confirm(
            get_string('delete_subscription_question', 'mod_newsletter'),
            new moodle_url($url, array(NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_YES)),
            new moodle_url($url, array(NEWSLETTER_PARAM_CONFIRM => NEWSLETTER_CONFIRM_NO))
        );
        $output .= $renderer->render_footer();
        return $output;
    }

    /**
     * Display the overview of all newsletter issues as a list
     * TODO: implement issue navigation from a point of time to a point of time
     *
     * @param string $heading
     * @param string $groupby
     * @return NULL|newsletter_section_list
     */
    private function prepare_issue_list($heading, $groupby) {
        // TODO: Add first day of the week check.
        $editissue = has_capability('mod/newsletter:editissue', $this->get_context());
        $deleteissue = has_capability('mod/newsletter:deleteissue', $this->get_context());
        $duplicateissue = has_capability('mod/newsletter:duplicateissue', $this->get_context());

        $issues = $this->get_issues();

        if (empty($issues)) {
            return null;
        }

        $firstissue = reset($issues);
        // First day of week $firstdayofweek = (int) get_string('firstdayofweek', 'langconfig');.
        switch ($groupby) {
            case NEWSLETTER_GROUP_ISSUES_BY_YEAR:
                list($from, $to) = $this->get_year_from_to_issuelist($firstissue->publishon);
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
                $dateformat = get_string("week") . " %W/%Y";
                $datefromto = "%d. %B %Y";
                break;
            default:
                $from = strtotime("first day of this month", $firstissue->publishon);
                $to = strtotime("next month", $from);
                $dateformat = "%B %Y";
        }

        $sectionlist = new \newsletter_section_list($heading);
        $currentissuelist = new \newsletter_issue_summary_list();
        foreach ($issues as $issue) {
            if ($issue->publishon >= $from && $issue->publishon < $to) { // If issue in timeslot.
                if (!($issue->publishon > time() && !$editissue)) { // Do not display issues that.
                    // Are not yet published.
                    $currentissuelist->add_issue_summary(
                        new \newsletter_issue_summary($issue, $editissue, $deleteissue, $duplicateissue)
                    );
                }
            } else {
                if ($groupby == NEWSLETTER_GROUP_ISSUES_BY_WEEK) {
                    $heading = userdate($from, $dateformat) . ' (' . userdate($from, $datefromto) . ' - ' . userdate(
                        strtotime('yesterday', $to),
                        $datefromto
                    ) . ')';
                } else {
                    $heading = userdate($from, $dateformat);
                }
                switch ($groupby) {
                    case NEWSLETTER_GROUP_ISSUES_BY_YEAR:
                        list($from, $to) = $this->get_year_from_to_issuelist($issue->publishon);
                        $dateformat = "%Y";
                        break;
                    case NEWSLETTER_GROUP_ISSUES_BY_MONTH:
                        $from = strtotime("first day of this month", $issue->publishon);
                        $to = strtotime("next month", $from);
                        $dateformat = "%B %Y";
                        break;
                    case NEWSLETTER_GROUP_ISSUES_BY_WEEK:
                        $from = strtotime(date('o-\\WW', $issue->publishon));
                        $to = strtotime("next monday", $from);
                        $dateformat = get_string("week") . " %W/%Y";
                        $datefromto = "%d. %B %Y";
                        break;
                }
                if (!empty($currentissuelist->issues)) {
                    $sectionlist->add_issue_section(
                        new \newsletter_section($heading, $currentissuelist)
                    );
                }
                $currentissuelist = new \newsletter_issue_summary_list();
                if (!($issue->publishon > time() && !$editissue)) { // Do not display issues that
                    // are not yet published.
                    $currentissuelist->add_issue_summary(
                        new \newsletter_issue_summary($issue, $editissue, $deleteissue, $duplicateissue)
                    );
                }
            } // End if issue in timeslot.
        } // Foreach issue.
        if (!empty($currentissuelist->issues)) {
            if ($groupby == NEWSLETTER_GROUP_ISSUES_BY_WEEK) {
                $heading = userdate($from, $dateformat) . ' (' . userdate($from, $datefromto) . ' - ' . userdate(
                    strtotime('yesterday', $to),
                    $datefromto
                ) . ')';
            } else {
                $heading = userdate($from, $dateformat);
            }
            $sectionlist->add_issue_section(new \newsletter_section($heading, $currentissuelist));
        }
        return $sectionlist;
    }

    /**
     * get first day of year (from) and the first day of the following year (to) of the issue's publication date
     *
     * @param int $publishonts issue's publication date
     * @return array:number number |from to| from:first day of the year of publication, to: first day following year
     */
    private function get_year_from_to_issuelist($publishonts) {
        $year = date("Y", $publishonts);
        $from = strtotime($year . "/01/01");
        $to = strtotime("+1 year", $from);

        return array($from, $to);
    }

    /**
     * calculate number of pages for displaying subscriptions
     *
     * @param int $total
     * @param int $from
     * @param int $count
     * @return array number of pages
     */
    private function calculate_pages(int $total, int $from, int $count): array {
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

    /**
     * Check if issueid exists
     *
     * @param $issueid
     * @return bool
     * @throws coding_exception
     * @throws \dml_exception
     */
    private function check_issue_id(int $issueid): bool {
        global $DB;

        return !$issueid || $DB->get_field(
            'newsletter_issues',
            'newsletterid',
            array('id' => $issueid, 'newsletterid' => $this->get_instance()->id)
        );
    }

    /**
     * Add newsletter issue
     *
     * @param stdClass $data
     * @return stdClass
     * @throws coding_exception
     * @throws \dml_exception
     */
    private function add_issue(stdClass $data) {
        global $DB;
        $context = $this->get_context();

        $issue = $this->return_issue_from_form_data($data);

        $issue->id = $DB->insert_record('newsletter_issues', $issue);

        $issue->htmlcontent = file_save_draft_area_files(
            $data->htmlcontent['itemid'],
            $context->id,
            'mod_newsletter',
            NEWSLETTER_FILE_AREA_ISSUE,
            $issue->id,
            issue_form::editor_options($context, $issue->id),
            $data->htmlcontent['text']
        );

        $DB->set_field(
            'newsletter_issues',
            'htmlcontent',
            $issue->htmlcontent,
            array('id' => $issue->id)
        );

        $fileoptions = array(
            'subdirs' => NEWSLETTER_FILE_OPTIONS_SUBDIRS, 'maxbytes' => 0,
            'maxfiles' => -1
        );

        if ($data && $data->attachments) {
            file_save_draft_area_files(
                $data->attachments,
                $context->id,
                'mod_newsletter',
                NEWSLETTER_FILE_AREA_ATTACHMENT,
                $issue->id,
                $fileoptions
            );
        }

        $params = array(
            'context' => $context, 'objectid' => $issue->id,
            'other' => array('newsletterid' => $issue->newsletterid)
        );
        $event = \mod_newsletter\event\issue_created::create($params);
        $event->trigger();

        return $issue;
    }

    /**
     * Update an existing newsletter issue.
     *
     * @param stdClass $data
     * @return stdClass
     */
    private function update_issue(stdClass $data) {
        global $DB;

        $context = $this->get_context();
        $oldissue = $this->get_issue($data->issue);
        $deliverystartedorcompleted = true;
        if ($oldissue->delivered == NEWSLETTER_DELIVERY_STATUS_UNKNOWN
            || $oldissue->delivered == NEWSLETTER_DELIVERY_STATUS_FAILED) {
            $deliverystartedorcompleted = false;
        }

        $issue = new stdClass();
        $issue->id = $data->issue;
        $issue->title = $data->title;
        $issue->newsletterid = $this->get_instance()->id;
        $issue->htmlcontent = file_save_draft_area_files(
            $data->htmlcontent['itemid'],
            $context->id,
            'mod_newsletter',
            NEWSLETTER_FILE_AREA_ISSUE,
            $issue->id,
            issue_form::editor_options($context, $issue->id),
            $data->htmlcontent['text']
        );

        // The publishon on date must not be altered after newsletter was sent.
        if (!$deliverystartedorcompleted) {
            $issue->publishon = $data->publishon;
        }

        $issue->stylesheetid = $data->stylesheetid;
        $issue->toc = $data->toc;
        $fileoptions = array(
            'subdirs' => NEWSLETTER_FILE_OPTIONS_SUBDIRS, 'maxbytes' => 0,
            'maxfiles' => -1
        );

        if ($data && $data->attachments) {
            file_save_draft_area_files(
                $data->attachments,
                $context->id,
                'mod_newsletter',
                NEWSLETTER_FILE_AREA_ATTACHMENT,
                $issue->id,
                $fileoptions
            );
        }

        $issue->userfilter = userfilter::return_json_from_form($data);

        $issue->timemodified = time();

        $DB->update_record('newsletter_issues', $issue);

        return $issue;
    }

    /**
     * Function to return issue object from form data.
     *
     * @param stdClass $data
     * @return stdClass
     */
    private function return_issue_from_form_data($data) {

        $issue = new stdClass();
        $issue->id = 0;
        $issue->newsletterid = $this->get_instance()->id;
        $issue->title = $data->title;
        $issue->htmlcontent = '';
        $issue->publishon = $data->publishon;
        $issue->stylesheetid = $data->stylesheetid;
        $issue->toc = $data->toc;
        $issue->userfilter = userfilter::return_json_from_form($data);
        $issue->timecreated = time();
        $issue->timemodified = $issue->timecreated;

        return $issue;
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
     * and subscribe every single user of the cohort to the newsletter in a course only
     * enrolled users who are members of the cohort will be subscribed
     *
     * @param number $cohortid
     * @param boolean $resubscribeunsubscribed
     */
    public function subscribe_cohort($cohortid, $resubscribeunsubscribed = false) {
        global $DB;
        $instanceid = $this->get_instance()->id;
        list($enrolledsql, $enrolledparams) = get_enrolled_sql($this->get_context());
        $sql = "SELECT cm.userid
                FROM {cohort_members} cm
                WHERE cm.cohortid = :cohortid
                AND cm.userid IN ($enrolledsql)";
        $params = array('cohortid' => $cohortid, 'newsletterid' => $instanceid);
        $params = array_merge($params, $enrolledparams);
        $users = $DB->get_fieldset_sql($sql, $params);
        foreach ($users as $userid) {
            $this->subscribe(
                $userid,
                true,
                NEWSLETTER_SUBSCRIBER_STATUS_OK,
                $resubscribeunsubscribed
            );
        }
    }

    /**
     * unsubscribes members of a cohort from newsletter
     *
     * @param integer $cohortid
     */
    public function unsubscribe_cohort($cohortid) {
        global $DB;
        $newsletterid = $this->get_instance()->id;
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
     * @param int $from UTC timestamp
     * @param int $to UTC timestamp
     * @return array db records of newsletter issues
     */
    private function get_issues(int $from = 0, int $to = 0): array {
        global $DB;
        $total = $DB->count_records_select(
            'newsletter_subscriptions',
            'newsletterid = ' . $this->get_instance()->id . ' AND health < 2'
        );

        $query = "SELECT i.*
                    FROM {newsletter_issues} i
                   WHERE i.newsletterid = :newsletterid
                     AND i.publishon > :from
                     AND i.publishon > :to
                ORDER BY i.publishon DESC";
        $params = array('newsletterid' => $this->get_instance()->id, 'from' => $from, 'to' => $to);
        $records = $DB->get_records_sql($query, $params);
        foreach ($records as $key => $record) {

            if (!userfilter::user_can_see_this_issue($record)) {
                unset($records[$key]);
                $total--;
                continue;
            }

            $record->cmid = $this->get_course_module()->id;
            $record->numsubscriptions = $total;
            if (
                $record->delivered == NEWSLETTER_DELIVERY_STATUS_DELIVERED ||
                $record->delivered == NEWSLETTER_DELIVERY_STATUS_INPROGRESS
            ) {
                $record->numnotyetdelivered = $DB->count_records(
                    'newsletter_deliveries',
                    array('issueid' => $record->id, 'delivered' => 0)
                );
                $record->numdelivered = $DB->count_records_select(
                    'newsletter_deliveries',
                    'issueid = :issueid AND delivered > 0',
                    array('issueid' => $record->id)
                );
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
    public function get_issue($issueid) {
        global $DB;
        if ($issueid == 0) {
            return null;
        }
        if (isset($this->issues[$issueid])) {
            return $this->issues[$issueid];
        } else {
            $record = $DB->get_record(
                'newsletter_issues',
                array('id' => $issueid, 'newsletterid' => $this->get_instance()->id)
            );
            if ($record) {
                $record->cmid = $this->get_course_module()->id;
                $record->context = $this->get_context()->id;
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
        $files = $fs->get_area_files(
            $context->id,
            'mod_newsletter',
            NEWSLETTER_FILE_AREA_STYLESHEET,
            $this->get_instance()->id,
            'filename',
            false
        );
        if ($id === 0) {
            return $files;
        } else {
            foreach ($files as $file) {
                if ($file->get_id() == $id) {
                    return array($id => $file);
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
     * @return string <string, void, mixed>
     */
    public function inline_css($htmlcontent, $stylesheetid, $fulldocument = false) {
        global $CFG;
        $cssfile = $this->get_stylesheets($stylesheetid);
        $basecss = file_get_contents(
            $CFG->dirroot . '/mod/newsletter/' . NEWSLETTER_BASE_STYLESHEET_PATH
        );
        $toccss = file_get_contents($CFG->dirroot . '/mod/newsletter/' . 'toc.css');
        $css = $basecss . $toccss;
        if (!empty($cssfile)) {
            foreach ($cssfile as $storedstylefile) {
                $css .= ($cssfile ? ('\n' . $storedstylefile->get_content()) : '');
            }
        }

        $converter = new  CssToInlineStyles();
        $html = $converter->convert(mb_convert_encoding($htmlcontent, 'HTML-ENTITIES', 'UTF-8'), $css);

        if (!$fulldocument) {
            if (preg_match(
                '/.*?<html.*?style="([^"]*?)"[^>]*?>.*?<body.*?style="([^"]*?)"[^>]*?>(.+)<\/body>.*/msi',
                $html
            )) {
                $html = preg_replace(
                    '/.*?<html.*?style="([^"]*?)"[^>]*?>.*?<body.*?style="([^"]*?)"[^>]*?>(.+)<\/body>.*/msi',
                    '<div style="$1 $2">$3</div>',
                    $html
                );
            } else if (preg_match(
                '/.*?<html[^>]*?>.*?<body.*?style="([^"]*?)"[^>]*?>(.+)<\/body>.*/msi',
                $html
            )) {
                $html = preg_replace(
                    '/.*?<html[^>]*?>.*?<body.*?style="([^"]*?)"[^>]*?>(.+)<\/body>.*/msi',
                    '<div style="$1">$2</div>',
                    $html
                );
            } else if (preg_match(
                '/.*?<html.*?style="([^"]*?)"[^>]*?>.*?<body[^>]*?>(.+)<\/body>.*/msi',
                $html
            )) {
                $html = preg_replace(
                    '/.*?<html.*?style="([^"]*?)"[^>]*?>.*?<body[^>]*?>(.+)<\/body>.*/msi',
                    '<div style="$1">$2</div>',
                    $html
                );
            } else if (preg_match('/.*?<html[^>]*?>.*?<body[^>]*?>(.+)<\/body>.*/msi', $html)) {
                $html = preg_replace(
                    '/.*?<html[^>]*?>.*?<body[^>]*?>(.+)<\/body>.*/msi',
                    '<div>$1</div>',
                    $html
                );
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
     * @return null|array <NULL, mixed>
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
     * @return null|array <NULL, mixed>
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
     * @return null|array <NULL, mixed>
     */
    private function get_first_issue($issue) {
        global $DB;
        $query = "SELECT *
                    FROM {newsletter_issues} i
                   WHERE i.newsletterid = :newsletterid
                     AND i.publishon < :publishon
                     AND i.id != :id
                ORDER BY i.publishon ASC";
        $params = array(
            'newsletterid' => $issue->newsletterid, 'publishon' => $issue->publishon,
            'id' => $issue->id
        );
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
        $params = array(
            'newsletterid' => $issue->newsletterid, 'publishon' => $issue->publishon,
            'id' => $issue->id
        );
        $results = $DB->get_records_sql($query, $params);
        return empty($results) ? null : reset($results);
    }

    /**
     * Returns the base url for the newsletter instance
     *
     * @return moodle_url
     * @throws \moodle_exception
     */
    public function get_url() {
        return new moodle_url(
            '/mod/newsletter/view.php',
            array(NEWSLETTER_PARAM_ID => $this->get_course_module()->id)
        );
    }

    /**
     * Returns the url for the subscribing to the newsletter
     *
     * @return moodle_url
     */
    public function get_subsribe_url() {
        $url = $this->get_url();
        $url->param(NEWSLETTER_PARAM_ACTION, NEWSLETTER_ACTION_SUBSCRIBE);
        return $url;
    }
    
    /**
     * subscribe a user to a newsletter and return the subscription id if successful
     * When user status is unsubscribed and $resubscribed_unsubscribed
     * is true, user will be subscribed as active again
     * When user is already subscribed and status is other than unsubscribed, the subscription status
     * remains unchanged
     *
     * @param number $userid
     * @param boolean $bulk set to true if multiple users are subscribed
     * @param string $status
     * @param boolean $resubscribeunsubscribed true to resubscribe unsubscribed users
     * @param integer $instanceid only needed when newsletter instance is created
     * @return boolean|number <boolean, number> subscriptionid for new subscription false when user is subscribed
     *  and status remains unchanged true when changed from unsubscribed to NEWSLETTER_SUBSCRIBER_STATUS_OK
     */
    public function subscribe(
        $userid = 0,
        $bulk = false,
        $status = NEWSLETTER_SUBSCRIBER_STATUS_OK,
        $resubscribeunsubscribed = false,
        $instanceid = 0
    ) {
        global $DB, $USER;
        $now = time();

        if ($userid == 0) {
            $userid = $USER->id;
        }
        if ($instanceid == 0) {
            $instanceid = $this->get_instance()->id;
        }
        if ($sub = $DB->get_record(
            "newsletter_subscriptions",
            array("userid" => $userid, "newsletterid" => $instanceid)
        )) {
            if ($sub->health == NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED && $resubscribeunsubscribed) {
                $sub->health = NEWSLETTER_SUBSCRIBER_STATUS_OK;
                $sub->timestatuschanged = $now;
                $sub->subscriberid = $USER->id;
                $result = $DB->update_record('newsletter_subscriptions', $sub);
                if ($result) {
                    $params = array(
                        'context' => $this->get_context(), 'objectid' => $sub->id,
                        'relateduserid' => $userid,
                        'other' => array('newsletterid' => $sub->newsletterid)
                    );
                    $event = \mod_newsletter\event\subscription_resubscribed::create($params);
                    $event->trigger();
                }
                return $result;
            } else {
                return false;
            }
        } else {
            $sub = new stdClass();
            $sub->userid = $userid;
            $sub->newsletterid = $instanceid;
            $sub->health = $status;
            $sub->timesubscribed = $now;
            $sub->timestatuschanged = $now;
            $sub->subscriberid = $USER->id;
            $result = $DB->insert_record("newsletter_subscriptions", $sub, true, $bulk);
            if ($result) {
                $params = array(
                    'context' => $this->get_context(), 'objectid' => $result,
                    'relateduserid' => $userid,
                    'other' => array('newsletterid' => $sub->newsletterid)
                );
                $event = \mod_newsletter\event\subscription_created::create($params);
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
        global $DB;

        if ($data->health == NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED && $this->get_subscription_status(
            $data->subscription
        ) != NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED) {
            $this->unsubscribe($data->subscription);
        } else {
            $subscription = new stdClass();
            $subscription->id = $data->subscription;
            $subscription->health = $data->health;
            $subscription->nounsublink = $data->nounsublink;
            $subscription->timestatuschanged = time();
            $DB->update_record('newsletter_subscriptions', $subscription);

            $params = array(
                'context' => $this->get_context(), 'objectid' => $data->subscription,
                'relateduserid' => $data->userid,
                'other' => array(
                    'newsletterid' => $this->get_instance()->id,
                    'status' => $data->health
                )
            );
            $event = \mod_newsletter\event\subscription_statuschanged::create($params);
            $event->trigger();
        }
    }

    /**
     * given the id of newsletter_subscriptions deletes the subscription completely (including health status)
     *
     * @param integer $subid
     * @param integer $userid used only for log data
     * @return boolean
     */
    public function delete_subscription($subid, $userid = 0) {
        global $DB;
        if ($userid == 0) {
            $userid = $DB->get_field('newsletter_subscriptions', 'userid', array('id' => $subid));
        }
        $result = $DB->delete_records("newsletter_subscriptions", array('id' => $subid));

        $params = array(
            'context' => $this->get_context(), 'objectid' => $subid,
            'relateduserid' => $userid,
            'other' => array('newsletterid' => $this->get_instance()->id)
        );
        $event = \mod_newsletter\event\subscription_deleted::create($params);
        $event->trigger();

        return $result;
    }

    /**
     * set health status to "unsubscribed" for this instance of the newsletter
     *
     * @param number $subscriptionid
     * @param number $userid only used for log data
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
            'context' => $this->get_context(), 'objectid' => $subid,
            'relateduserid' => $userid,
            'other' => array('newsletterid' => $this->get_instance()->id)
        );
        $event = \mod_newsletter\event\subscription_unsubscribed::create($params);
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
        return $DB->record_exists_select(
            "newsletter_subscriptions",
            "userid = :userid AND newsletterid = :newsletterid AND health <> :health",
            array(
                "userid" => $userid, "newsletterid" => $this->get_instance()->id,
                "health" => NEWSLETTER_SUBSCRIBER_STATUS_UNSUBSCRIBED
            )
        );
    }

    /**
     * Return the subscription status (health) of a given subscription id
     *
     * @param number $subid
     * @return boolean
     */
    public function get_subscription_status($subid) {
        global $DB;
        return $DB->get_field("newsletter_subscriptions", 'health', array("id" => $subid));
    }

    /**
     * return the e-mail address that receives bounce mails
     * The bounce e-mail address is used to collect all bounces that will be fetched and processed by the bounce processor
     */
    public function get_bounceemail_address() {
        global $CFG;
        if ($this->config->enablebounce == '1' && filter_var(
            $this->config->bounceemail,
            FILTER_VALIDATE_EMAIL
        )) {
            return $this->config->bounceemail;
        } else {
            return $CFG->noreplyaddress;
        }
    }

    /**
     * Creates a new user and subscribes the user to the newsletter
     * TODO there are unsufficient checks for creating the user
     * TODO check if email already exists for another user if yes, then display message to
     * login in order to subscribe
     * TODO unsufficient checks if user is already subscribed and has status "unsubscribed", in this case e-mail
     * confirmation should be required
     *
     * @param string $firstname
     * @param string $lastname
     * @param string $email
     * @return boolean true when confirm mail was successfully sent to user, false when not
     */
    public function subscribe_guest(string $firstname, string $lastname, string $email): bool {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        require_once($CFG->dirroot . '/user/lib.php');

        if (empty($CFG->registerauth)) {
            throw new moodle_exception ('notlocalisederrormessage', 'error', '', 'Sorry, you may not use this page.');
        }
        $authplugin = get_auth_plugin($CFG->registerauth);

        if (!$authplugin->can_signup()) {
            throw new moodle_exception ('notlocalisederrormessage', 'error', '', 'Sorry, you may not use this page.');
        }

        // Generate username. If already exists try to find another one, repeat until username found.
        if ($CFG->extendedusernamechars) {
            $newusername = $email;
        } else {
            $cfirstname = preg_replace(
                    '/[^a-zA-Z]+/',
                    '',
                    iconv('UTF-8', 'US-ASCII//TRANSLIT', $firstname)
            );
            $clastname = preg_replace(
                    '/[^a-zA-Z]+/',
                    '',
                    iconv('UTF-8', 'US-ASCII//TRANSLIT', $lastname)
            );
            $username = strtolower(substr($cfirstname, 0, 1) . $clastname);
            $i = 0;
            do {
                $newusername = $username . ($i != 0 ? $i : '');
                $i++;
                $olduser = get_complete_user_data('username', $newusername);
            } while (!empty($olduser));
        }

        $usernew = new stdClass();
        $usernew->username = $newusername;
        $usernew->email = $email;
        $usernew->firstname = $firstname;
        $usernew->lastname = $lastname;
        $usernew->auth = 'email';
        $usernew->confirmed = 0;
        $usernew->deleted = 0;
        $usernew->password = $password = generate_password();
        $usernew->mailformat = 1;
        $usernew->lang = current_language();
        $usernew->firstaccess = time();
        $usernew->timemodified = time();
        $usernew->secret = $secret = random_string(15);
        $usernew->mnethostid = $CFG->mnet_localhost_id; // Always local user.
        $usernew->timecreated = time();
        $usernew->password = hash_internal_user_password($usernew->password);
        $usernew->courseid = $this->get_course()->id;
        $usernew->id = user_create_user($usernew, false, false);
        $user = $DB->get_record('user', array('id' => $usernew->id));
        user_created::create_from_userid($user->id)->trigger();

        $this->subscribe($user->id, false, NEWSLETTER_SUBSCRIBER_STATUS_OK);

        $cm = $this->get_course_module();
        $newslettername = $DB->get_field('newsletter', 'name', array('id' => $cm->instance));

        $data = "{$secret}-{$user->id}-{$cm->instance}-guest";
        $activateurl = new moodle_url(
            '/mod/newsletter/confirm.php',
            array(NEWSLETTER_PARAM_DATA => $data)
        );

        $site = get_site();
        $a = array(
            'fullname' => fullname($user), 'newslettername' => $newslettername,
            'sitename' => format_string($site->fullname), 'email' => $email,
            'username' => $user->username, 'password' => $password,
            'link' => $activateurl->__toString(), 'admin' => generate_email_signoff()
        );

        $messagetext = get_string('subscription_message', 'newsletter', $a);
        $htmlcontent = text_to_html($messagetext);
        $supportuser = core_user::get_support_user();

        if (!email_to_user(
            $user,
            $supportuser,
            get_string('welcometonewsletter', 'mod_newsletter'),
            $messagetext,
            $htmlcontent
        )) {
            return false;
        }
        return true;
    }

    /**
     * Obtains WHERE clause to filter results by defined search for view managesubscriptions
     *
     * @return array Two-element array with SQL and params for WHERE clause
     */
    public function get_filter_sql(array $getparams, $count = false) {
        $fields = \core_user\fields::for_name()->with_identity($this->context, false);
        $usersql = $fields->get_sql('u');
        $extrafields = $fields->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]);
        if ($count) {
            $sql = "SELECT COUNT(*)
            FROM {newsletter_subscriptions} ns
            INNER JOIN {user} u ON ns.userid = u.id
            WHERE ns.newsletterid = :newsletterid AND ";
        } else {
            $sql = "SELECT DISTINCT ns.* {$usersql->selects}, COUNT(DISTINCT nb.id) AS bounces
            FROM {newsletter_subscriptions} ns
            INNER JOIN {user} u ON ns.userid = u.id
            LEFT JOIN {newsletter_bounces} nb ON nb.userid = u.id
            WHERE ns.newsletterid = :newsletterid AND ";
        }

        $params = array('newsletterid' => $this->get_instance()->id);

        // Search condition (search for username).
        list($usersql, $userparams) = users_search_sql($getparams['search'], 'u', true, $extrafields);
        $sql .= $usersql;
        $params += $userparams;

        // Status condition.
        if ($getparams['status'] != 10) {
            $sql .= " AND ns.health = :status";
            $params += array('status' => $getparams['status']);
        }
        if (!$count) {
            $sql .= " GROUP BY u.id, ns.id ";
        }
        if ($count && $getparams['orderby'] != '') {
            $sql .= " GROUP BY u." . $getparams['orderby'];
        }
        if ($getparams['orderby'] != '') {
            $sql .= " ORDER BY u." . $getparams['orderby'];
        }

        return array($sql, $params);
    }

    /**
     * Saves a new instance of the newsletter into the database
     * Given an object containing all the necessary data, (defined by the form in mod_form.php)
     * this function will create a new instance and return
     * the id number of the new instance.
     *
     * @param object $newsletter An object from the form in mod_form.php
     * @param \mod_newsletter_mod_form $mform
     * @return int The id of the newly inserted newsletter record
     */
    public function add_instance(stdClass $newsletter, \mod_newsletter_mod_form $mform = null) {
        global $DB;
        $now = time();
        $newsletter->timecreated = $now;
        $newsletter->timemodified = $now;
        $newsletter->id = $DB->insert_record('newsletter', $newsletter);

        $fileoptions = array(
            'subdirs' => NEWSLETTER_FILE_OPTIONS_SUBDIRS, 'maxbytes' => 0,
            'maxfiles' => -1
        );

        $context = $this->get_context();

        if ($mform && $mform->get_data() && $mform->get_data()->stylesheets) {
            file_save_draft_area_files(
                $mform->get_data()->stylesheets,
                $context->id,
                'mod_newsletter',
                NEWSLETTER_FILE_AREA_STYLESHEET,
                $newsletter->id,
                $fileoptions
            );
        }

        if (
            $newsletter->subscriptionmode == NEWSLETTER_SUBSCRIPTION_MODE_OPT_OUT ||
            $newsletter->subscriptionmode == NEWSLETTER_SUBSCRIPTION_MODE_FORCED
        ) {
            $users = get_enrolled_users($context, null, null, 'u.id');
            foreach ($users as $user) {
                $this->subscribe(
                    $user->id,
                    true,
                    NEWSLETTER_SUBSCRIBER_STATUS_OK,
                    false,
                    $newsletter->id
                );
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
    public function update_instance($data, $mform) {
        global $DB;

        $now = time();
        $data->timemodified = $now;
        $data->id = $data->instance;

        $fileoptions = array(
            'subdirs' => NEWSLETTER_FILE_OPTIONS_SUBDIRS, 'maxbytes' => 0,
            'maxfiles' => -1
        );

        $context = $this->get_context();

        if ($mform && $mform->get_data() && $mform->get_data()->stylesheets) {
            file_save_draft_area_files(
                $mform->get_data()->stylesheets,
                $context->id,
                'mod_newsletter',
                NEWSLETTER_FILE_AREA_STYLESHEET,
                $data->id,
                $fileoptions
            );
        }

        if (
            $data->subscriptionmode == NEWSLETTER_SUBSCRIPTION_MODE_OPT_OUT ||
            $data->subscriptionmode == NEWSLETTER_SUBSCRIPTION_MODE_FORCED
        ) {
            $users = get_enrolled_users($context, null, null, 'u.id');
            foreach ($users as $user) {
                $this->subscribe($user->id, true);
            }
        }
        return $DB->update_record('newsletter', $data);
    }

    /**
     * Function to duplicate newsletter and reset date to the future.
     *
     * @param integer $issueid
     * @return void
     */
    private function duplicate_issue(int $issueid): ?int {
        global $DB;
        $record = $DB->get_record('newsletter_issues', array('id' => $issueid));
        unset($record->id);
        $now = time();
        $newtime = strtotime("+2 days", $now);
        $record->publishon = $newtime;
        $record->timecreated = $now;
        $record->timemodified = $now;
        $record->delivered = 0;
        return $DB->insert_record('newsletter_issues', $record);
    }
}
