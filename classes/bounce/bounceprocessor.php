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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <http://www.gnu.org/licenses/>.

/**
 * This file processes bounces
 *
 *
 * @package mod_newsletter
 * @copyright 2015 onwards David Bogner <info@edulabs.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_newsletter\bounce;
require_once 'bouncehandler.php';
require_once $CFG->dirroot . '/mod/newsletter/lib.php';

define ( 'NEWSLETTER_BOUNCE_HARD', 0 ); // hard bounce
define ( 'NEWSLETTER_BOUNCE_SOFT', 1 ); // soft bounce
class bounceprocessor extends bouncehandler {
	
	/**
	 *
	 * @var integer unix timestamp
	 */
	protected $timecreated = null;
	
	/**
	 *
	 * @var array
	 */
	public $bounces = array();
	
	/**
	 * @var array of strings
	 */
	public $issues = null;
	
	/**
	 * Apply the settings
	 *
	 * @param \stdClass $conf
	 *        	bounce settings of newsletter
	 */
	public function __construct($conf) {
		$this->timecreated = time ();
		
		if (isset ( $conf->open_mode )) {
			$this->open_mode = CWSMBH_OPEN_MODE_IMAP;
		}
		if (isset ( $conf->host )) {
			$this->host = $conf->host;
		}
		if (isset ( $conf->username )) {
			$this->username = $conf->username;
		}
		if (isset ( $conf->password )) {
			$this->password = $conf->password;
		}
		if (isset ( $conf->port )) {
			$this->port = $conf->port;
		}
		if (isset ( $conf->service )) {
			$this->service = $conf->service;
		}
		if (isset ( $conf->service_option )) {
			$this->service_option = $conf->service_option;
		}
		if (isset ( $conf->cert )) {
			$this->cert = CWSMBH_CERT_NOVALIDATE;
		}
		if (isset ( $conf->test_mode )) {
			$this->test_mode = $conf->test_mode;
		}
		if (isset ( $conf->debug_verbose )) {
			$this->debug_verbose = $conf->debug_verbose;
		}
	}
	
	/**
	 * process mails and show results in the browser
	 * does not save or delete anything.
	 * just for information
	 * This can be used to test the imap/pop3 settings
	 */
	public function testrun() {
		$this->test_mode = true;
		$this->debug_verbose = CWSMBH_VERBOSE_DEBUG;
		$this->processMails ();
	}
	
	/**
	 * write bounces to database
	 * the bounce time is used to check if bounce was already processed
	 * just in case it was not deleted after it was processed
	 */
	public function process_bounces() {
		global $DB;
		$this->test_mode = false;
		$this->debug_verbose = CWSMBH_VERBOSE_QUIET;
		// TODO: threshhold should be a reasonable time between publishing date and a date where no bounces are expected anymore
		$threshold = $this->timecreated - 5*86400;
		$this->issues = $DB->get_records_select('newsletter_issues', 'publishon > :threshold', array('threshold' => $threshold), 'id', 'id, title' );
		$this->processMails ();
		
		$getusersql = 'SELECT MAX(u.id)
				FROM {user} u
				WHERE u.email = ? AND u.id IN (
					SELECT ns.userid
					FROM {newsletter_subscriptions} ns				
				)';
		if (! empty ( $this->result ['msgs'] )) {
			foreach ( $this->result ['msgs'] as $msg ) {
				foreach ( $msg['recipients'] as $recipientdata ) {
						$userid = $DB->get_field_sql($getusersql,array($recipientdata['email']));
						// check if userid exists and if this bounce is already saved
						if($userid && !$DB->record_exists('newsletter_bounces', array('timereceived' => $msg['date'], 'userid' => $userid))){
							$issueid = false;
							$bouncedata = new \stdClass ();
							$bouncedata->timecreated = $this->timecreated;
							if($recipientdata['action'] == 'failed' || $recipientdata['bounce_type'] == 'hard' ){
								$bouncedata->type = NEWSLETTER_BOUNCE_HARD;
							} else {
								$bouncedata->type = NEWSLETTER_BOUNCE_SOFT;
							}
							$bouncedata->userid = $userid;
							$bouncedata->statuscode = $recipientdata['status'];
							$bouncedata->timereceived = $msg['date'];
							//guesss the issue id the last guess is the latest issue
							if(!is_null($msg['issueid'])){
								$issueid = $msg['issueid'];
							} else if(!empty($this->issues)){
								foreach ($this->issues as $key => $value){
									if(preg_match("/".$value->title."/", $msg['subject']) == 1){
										$issueid = $key;
										break;
									} 
								}
								if($issueid == false){
									$issueid = max(array_keys($this->issues));
								}
							} else {
								$issueid = $DB->get_field_sql('SELECT id, MAX(publishon) FROM {newsletter_issues} WHERE publishon < :time)', array('time' => $this->timecreated));
							}
							$bouncedata->issueid = $issueid;
							$this->bounces [] = $bouncedata;
							
						} 
				}
			}
		}
		$DB->insert_records ( 'newsletter_bounces', $this->bounces );
	}

    /**
     * Process the messages in a mailbox or a file/folder
     * @param int $max : maximum limit messages processed in one batch, if not given uses the property $max_messages.
     * @return boolean
     */
    public function processMails($max=0)
    {
        $this->output('<h2>Init processMails</h2>', CWSMBH_VERBOSE_SIMPLE, false);
        
        if ($this->isImapOpenMode()) {
            if (!$this->_handler) {
                $this->error_msg = '<strong>Mailbox not opened</strong>';
                $this->output();
                exit();
            }
        } else {
            if (empty($this->_files)) {
                $this->error_msg = '<strong>File(s) not opened</strong>';
                $this->output();
                exit();
            }
        }
        
        if ($this->move_hard && $this->disable_delete === false) {
            $this->disable_delete = true;
        }
        
        if (!empty($max)) {
            $this->max_messages = $max;
        }
        
        // initialize counter
        $this->result['counter'] = $this->_counter_result;
        $this->result['counter']['total'] = $this->isImapOpenMode() ? imap_num_msg($this->_handler) : count($this->_files);
        $this->result['counter']['fetched'] = $this->result['counter']['total'];
        $this->output('<strong>Total:</strong> ' . $this->result['counter']['total'] . ' messages.');
        
        // process maximum number of messages
        if (!empty($this->max_messages) && $this->result['counter']['fetched'] > $this->max_messages) {
            $this->result['counter']['fetched'] = $this->max_messages;
            $this->output('Processing first <strong>' . $this->result['counter']['fetched'] . ' messages</strong>...');
        }
        
        if ($this->test_mode) {
            $this->output('Running in <strong>test mode</strong>, not deleting messages from mailbox.');
        } else {
            if ($this->disable_delete) {
                if ($this->move_hard) {
                    $this->output('Running in <strong>move mode</strong>.');
                } else {
                    $this->output('Running in <strong>disable_delete mode</strong>, not deleting messages from mailbox.');
                }
            } else {
                $this->output('<strong>Processed messages will be deleted</strong> from mailbox.');
            }
        }
        
        if ($this->isImapOpenMode()) {
            for ($msg_no = 1; $msg_no <= $this->result['counter']['fetched']; $msg_no++) {
                $this->output('<h3>Msg #' . $msg_no . '</h3>', CWSMBH_VERBOSE_REPORT, false);
                
                $header = @imap_fetchheader($this->_handler, $msg_no);
                $headerdata = @imap_headerinfo($this->_handler, $msg_no);
                $bodydata = @imap_fetchstructure($imap_stream, $msg_no);
                $body = @imap_body($this->_handler, $msg_no);
                
                //$this->result['msgs'][] = $this->processParsing($msg_no, $header . '\r\n\r\n' . $body);
                $this->result['msgs'][] = $this->processMsg($msg_no, $headerdata, $bodydata, $header . '\r\n\r\n' . $body);
            }
        } else {
            foreach ($this->_files as $file) {
                $this->output('<h3>Msg #' . $file['name'] . '</h3>', CWSMBH_VERBOSE_REPORT, false);
                $this->result['msgs'][] = $this->processParsing($file['name'], $file['content']);
            }
        }
        
        foreach ($this->result['msgs'] as $msg) {
            if ($msg['processed']) {
                $this->result['counter']['processed']++;
                if (!$this->test_mode && !$this->disable_delete) {
                    $this->processDelete($msg['token']);
                    $this->result['counter']['deleted']++;
                } elseif ($this->move_hard) {
                    $this->processMove($msg['token'], 'hard');
                    $this->result['counter']['moved']++;
                } elseif ($this->move_soft) {
                    $this->processMove($msg['token'], 'soft');
                    $this->result['counter']['moved']++;
                }
            } else {
                $this->result['counter']['unprocessed']++;
                if (!$this->test_mode && !$this->disable_delete && $this->purge) {
                    $this->processDelete($msg['token']);
                    $this->result['counter']['deleted']++;
                }
            }
        }
        
        $this->output('<h2>End of process</h2>', CWSMBH_VERBOSE_SIMPLE, false);
        if ($this->isImapOpenMode()) {
            $this->output('Closing mailbox, and purging messages');
            @imap_close($this->_handler);
        }
        
        $this->output($this->result['counter']['fetched'] . ' messages read');
        $this->output($this->result['counter']['processed'] . ' action taken');
        $this->output($this->result['counter']['unprocessed'] . ' no action taken');
        $this->output($this->result['counter']['deleted'] . ' messages deleted');
        $this->output($this->result['counter']['moved'] . ' messages moved');
        
        $this->output($this->_newline . '<strong>Full result:</strong>', CWSMBH_VERBOSE_REPORT);
        $this->output($this->result, CWSMBH_VERBOSE_REPORT, false, true);
        
        return true;
    }
    
    /**
     * Function to process parsing of each individual message
     * @param string $token : message number or filename.
     * @param string $content : message content.
     * @return array
     */
    protected function processMsg($token, $headerdata, $bodydata, $content)
    {
        $result = $this->_msg_result;
        $result['token'] = $token;
        
        // format content
        $content = $this->formatContent($content);
        
        // split head and body
        if (preg_match('#\r\n\r\n#is', $content)) {
            list($header, $body) = preg_split('#\r\n\r\n#', $content, 2);
        } else {
            list($header, $body) = preg_split('#\n\n#', $content, 2);
        }
        
        $this->output('<strong>Header:</strong>', CWSMBH_VERBOSE_DEBUG);
        $this->output($header, CWSMBH_VERBOSE_DEBUG, false, true);
        $this->output('<strong>Body:</strong>', CWSMBH_VERBOSE_DEBUG);
        $this->output($body, CWSMBH_VERBOSE_DEBUG, false, true);
        $this->output('&nbsp;', CWSMBH_VERBOSE_DEBUG);
		if (! empty ( $this->issues )) {
			foreach ( $this->issues as $key => $value ) {
				if (preg_match ( "/" . $value->title . "/", $header ) == 1) {
					$result ['issueid'] = $key;
					break;
				}
			}
		}

        // parse header
        $header = $this->parseHeader($header);
        
        // parse body sections
        $body_sections = $this->parseBodySections($header, $body);
        
        // check bounce and fbl
        $is_bounce = $this->isBounce($header);
        $is_fbl = $this->isFbl($header, $body_sections);
        
        if ($is_bounce) {
            $result['type'] = 'bounce';
        } elseif ($is_fbl) {
            $result['type'] = 'fbl';
        }
        
        // begin process
        $result['recipients'] = array();
        if ($is_fbl) {
            $this->output('<strong>Feedback loop</strong> detected', CWSMBH_VERBOSE_DEBUG);
            $result['subject'] = trim(str_ireplace('Fw:', '', $header['Subject']));
            
            if ($this->isHotmailFbl($body_sections)) {
                $this->output('This message is an <strong>Hotmail fbl</strong>', CWSMBH_VERBOSE_DEBUG);
                $body_sections['ar_machine']['Content-disposition'] = 'inline';
                $body_sections['ar_machine']['Content-type'] = 'message/feedback-report';
                $body_sections['ar_machine']['Feedback-type'] = 'abuse';
                $body_sections['ar_machine']['User-agent'] = 'Hotmail FBL';
                if (!$this->isEmpty($body_sections['ar_first'], 'Date')) {
                    $body_sections['ar_machine']['Received-date'] = $body_sections['ar_first']['Date'];
                }
                if (!$this->isEmpty($body_sections['ar_first'], 'X-HmXmrOriginalRecipient')) {
                    $body_sections['ar_machine']['Original-rcpt-to'] = $body_sections['ar_first']['X-HmXmrOriginalRecipient'];
                }
                if (!$this->isEmpty($body_sections['ar_first'], 'X-sid-pra')) {
                    $body_sections['ar_machine']['Original-mail-from'] = $body_sections['ar_first']['X-sid-pra'];
                }
            } else {
                if (!$this->isEmpty($body_sections, 'machine')) {
                    $body_sections['ar_machine'] = $this->parseLines($body_sections['machine']);
                }
                if (!$this->isEmpty($body_sections, 'returned')) {
                    $body_sections['ar_returned'] = $this->parseLines($body_sections['returned']);
                }
                if ($this->isEmpty($body_sections['ar_machine'], 'Original-mail-from') && !$this->isEmpty($body_sections['ar_returned'], 'From')) {
                    $body_sections['ar_machine']['Original-mail-from'] = $body_sections['ar_returned']['From'];
                }
                if ($this->isEmpty($body_sections['ar_machine'], 'Original-rcpt-to') && !$this->isEmpty($body_sections['ar_machine'], 'Removal-recipient')) {
                    $body_sections['ar_machine']['Original-rcpt-to'] = $body_sections['ar_machine']['Removal-recipient'];
                } elseif (!$this->isEmpty($body_sections['ar_returned'], 'To')) {
                    $body_sections['ar_machine']['Original-rcpt-to'] = $body_sections['ar_returned']['To'];
                }
                // try to get the actual intended recipient if possible
                if (preg_match('#Undisclosed|redacted#i', $body_sections['ar_machine']['Original-mail-from']) && !$this->isEmpty($body_sections['ar_machine'], 'Removal-recipient')) {
                    $body_sections['ar_machine']['Original-rcpt-to'] = $body_sections['ar_machine']['Removal-recipient'];
                }
                if ($this->isEmpty($body_sections['ar_machine'], 'Received-date') && !$this->isEmpty($body_sections['ar_machine'], 'Arrival-date')) {
                    $body_sections['ar_machine']['Received-date'] = $body_sections['ar_machine']['Arrival-date'];
                }
                if (!$this->isEmpty($body_sections['ar_machine'], 'Original-mail-from')) {
                    $body_sections['ar_machine']['Original-mail-from'] = $this->extractEmail($body_sections['ar_machine']['Original-mail-from']);
                }
                if (!$this->isEmpty($body_sections['ar_machine'], 'Original-rcpt-to')) {
                    $body_sections['ar_machine']['Original-rcpt-to'] = $this->extractEmail($body_sections['ar_machine']['Original-rcpt-to']);
                }
            }
            
            $recipient = $this->_recipient_result;
            $recipient['email'] = $body_sections['ar_machine']['Original-rcpt-to'];
            $recipient['status'] = '5.7.1';
            $recipient['action'] = 'failed';
            $result['recipients'][] = $recipient;
            $result['date'] = strtotime($headerdata->date);
        } elseif (!$this->isEmpty($header, 'Subject') && preg_match('#auto.{0,20}reply|vacation|(out|away|on holiday).*office#i', $header['Subject'])) {
            $this->output('<strong>Autoreply</strong> engaged', CWSMBH_VERBOSE_DEBUG);
            $recipient = $this->_recipient_result;
            $recipient['bounce_cat'] = CWSMBH_CAT_AUTOREPLY;
            $result['recipients'][] = $recipient;
        } elseif ($this->isRfc1892Report($header)) {
            $this->output('<strong>RFC 1892 report</strong> engaged', CWSMBH_VERBOSE_DEBUG);
            $ar_body_machine = $this->parseBodySectionMachine($body_sections['machine']);
            if (!$this->isEmpty($ar_body_machine['per_recipient'])) {
                foreach ($ar_body_machine['per_recipient'] as $ar_recipient) {
                    $recipient = $this->_recipient_result;
                    $recipient['email'] = $this->findEmail($ar_recipient);
                    $recipient['status'] = isset($ar_recipient['Status']) ? $ar_recipient['Status'] : null;
                    $recipient['action'] = isset($ar_recipient['Action']) ? $ar_recipient['Action'] : null;
                    $result['date'] = isset($headerdata->date) ? strtotime($headerdata->date) : null;
                    $result['recipients'][] = $recipient;
                }
            }
        } elseif (!$this->isEmpty($header, 'X-failed-recipients')) {
            $this->output('<strong>X-failed-recipients</strong> engaged', CWSMBH_VERBOSE_DEBUG);
            $ar_emails = explode(",", $header['X-failed-recipients']);
            foreach($ar_emails as $email) {
                $recipient = $this->_recipient_result;
                $recipient['email'] = trim($email);
                $result['recipients'][] = $recipient;
                $result['date'] = isset($headerdata->date) ? strtotime($headerdata->date) : null;
            }
        } elseif(isset($header['Content-type']) && !$this->isEmpty($header['Content-type'], 'boundary') && $this->isBounce($header)) {
            $this->output('<strong>First body part</strong> engaged', CWSMBH_VERBOSE_DEBUG);
            $ar_emails = $this->findEmails($body_sections['first']);
            foreach($ar_emails as $email) {
                $recipient = $this->_recipient_result;
                $recipient['email'] = trim($email);
                $result['recipients'][] = $recipient;
                $result['date'] = isset($headerdata->date) ? strtotime($headerdata->date) : null;
            }
        } elseif($this->isBounce($header)) {
            $this->output('<strong>Other bounces</strong> engaged', CWSMBH_VERBOSE_DEBUG);
            $ar_emails = $this->findEmails($body);
            foreach($ar_emails as $email) {
                $recipient = $this->_recipient_result;
                $recipient['email'] = trim($email);
                $result['recipients'][] = $recipient;
                $result['date'] = isset($headerdata->date) ? strtotime($headerdata->date) : null;
            }
        } else {
            $result['processed'] = false;
        }
        
        if (empty($result['subject']) && isset($header['Subject'])) {
            $result['subject'] = $header['Subject'];
        }
        if (!empty($result['recipients'])) {
            $tmp_recipient = $result['recipients'];
            $result['recipients'] = array();
            foreach($tmp_recipient as $recipient) {
                if (empty($recipient['status'])) {
                    $recipient['status'] = $this->findStatusCodeByRecipient($body);
                } else {
                    $recipient['status'] = $this->formatStatusCode($recipient['status']);
                }
                if (empty($recipient['action'])) {
                    $recipient['action'] = $this->findActionByStatusCode($recipient['status']);
                }
                if ($recipient['bounce_cat'] == CWSMBH_CAT_UNRECOGNIZED && !empty($recipient['status'])) {
                    foreach ($this->_rule_cat_resolver as $key => $value) {
                        if ($key == $recipient['status']) {
                            $recipient['bounce_cat'] = $value;
                        }
                        $recipient['bounce_type'] = $this->_rules_cats[$recipient['bounce_cat']]['bounce_type'];
                        $recipient['remove'] = $this->_rules_cats[$recipient['bounce_cat']]['remove'];
                    }
                }
                $result['recipients'][] = $recipient;
            }
        }
        
        $this->output('<strong>Result:</strong>', CWSMBH_VERBOSE_REPORT);
        $this->output($result, CWSMBH_VERBOSE_REPORT, false, true);
        
        return $result;
    }
    

    /**
     * update the health satus of the subscribers
     * TODO: this method can be optimized
     */
    public function update_health(){
    	global $DB;
    	foreach($this->bounces as $bounce){
    		$bounces = $DB->count_records_select('newsletter_bounces','userid = :userid',array('userid' => $bounce->userid));
    		$hardbounces = $DB->count_records_select('newsletter_bounces','userid = :userid AND type = '. NEWSLETTER_BOUNCE_HARD,array('userid' => $bounce->userid));
    		$sent = $DB->get_record_sql('SELECT SUM(sentnewsletters) as sent FROM {newsletter_subscriptions} WHERE userid = :userid', array('userid' => $bounce->userid));

    		
    		if($bounces > 0){
    			//hardbounces have more weight in calculation of bounce ratio
    			$bounceratio = ($hardbounces + $bounces)/$sent->sent;
    		} else {
    			$bounceratio = 0;
    		}
    		
    		if($hardbounces > 5 AND $bounceratio < 0.2){
    			$health = NEWSLETTER_SUBSCRIBER_STATUS_PROBLEMATIC;
    		} else if ($hardbounces >= 8 AND $bounceratio >= 0.2){
    			$health = NEWSLETTER_SUBSCRIBER_STATUS_BLACKLISTED;
    		} else if ($bounces >= 10 AND $bounceratio < 0.2){
    			$health = NEWSLETTER_SUBSCRIBER_STATUS_PROBLEMATIC;
    		} else if (($hardbounces < 10 AND $bounceratio > 0.2)){
    			$health = NEWSLETTER_SUBSCRIBER_STATUS_PROBLEMATIC;
    		} else {
    			$health = NEWSLETTER_SUBSCRIBER_STATUS_OK;
    		}
    		$subscriptions = $DB->get_records_select('newsletter_subscriptions', 'userid = :userid AND ( health = :ok OR health = :problematic )', array('userid' => $bounce->userid, 'ok' => NEWSLETTER_SUBSCRIBER_STATUS_OK, 'problematic' => NEWSLETTER_SUBSCRIBER_STATUS_PROBLEMATIC));
    		foreach ($subscriptions as $subcription){
    			if($subcription->health != $health){
    				$DB->set_field('newsletter_subscriptions','health',$health, array('id' => $subcription->id));
    			}
    		}
    	}
    }
    
    static public function get_user_bounce_stats(){
    	global $DB;
    	
    }
}
