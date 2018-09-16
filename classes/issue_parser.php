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
 * Parsing newsletter issues for creating table of contents
 * and replace tags, etc.
 *
 * @package mod_newsletter
 * @copyright 2015 onwards David Bogner <info@edulabs.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */
namespace mod_newsletter;

defined('MOODLE_INTERNAL') || die();

class issue_parser {

    /**
     *
     * @var \DOMDocument to be parsed
     */
    private $dom = null;

    /**
     *
     * @var string HTML table of contents
     */
    private $tochtml = null;

    /**
     *
     * @var string HTML the altered HTML
     */
    private $htmlcontent = null;

    /**
     *
     * @var number toc setting
     */
    private $tocsetting = null;

    /**
     *
     * @var array
     */
    private $tags = array('issueurl' => 'replace_issueurl', 'issuelink' => 'replace_issuelink',
        'firstname' => 'replace_firstname', 'lastname' => 'replace_lastname',
        'fullname' => 'replace_fullname');

    /**
     *
     * @var integer context id
     */
    private static $contextid = null;

    /**
     *
     * @var integer issue id
     */
    private static $issueid = null;

    /**
     *
     * @var integer newsletter id
     */
    private static $newsletterid = null;

    /**
     *
     * @var boolean called from cron: true otherwise false
     */
    private static $cron = false;

    /**
     * Convert the inital HTML to \DOMDocument
     *
     * @param object $issue
     * @param boolean $cron true if called from cron job false if newsletter is called from browser
     */
    public function __construct($issue, $cron = false) {
        self::$newsletterid = $issue->newsletterid;
        self::$contextid = $issue->cmid;
        self::$issueid = $issue->id;
        self::$cron = $cron;
        $this->htmlcontent = $issue->htmlcontent;
        $this->tocsetting = $issue->toc;
        if ($this->has_tag($this->htmlcontent)) {
            $this->htmlcontent = $this->replace_tags($issue);
        }
    }

    /**
     * Return the table of content and the modified newsletter content with anchors as an array Generate if it is not yet generated
     *
     * @return string htmlcontent
     */
    public function get_parsed_html() {
        if ($this->tocsetting > 0) {
            $this->dom = new \DOMDocument();
            @$this->dom->loadHTML(mb_convert_encoding($this->htmlcontent, 'HTML-ENTITIES', 'UTF-8'));
            if (is_null($this->tochtml)) {
                $this->generate_toc();
            }
            return $this->tochtml . $this->htmlcontent;
        } else {
            return $this->htmlcontent;
        }
    }

    /**
     * Get the supported tag names as an array
     *
     * @return array of strings
     */
    public function get_supported_tags() {
        return array_keys($this->tags);
    }

    /**
     * Generate the table of content for the newsletter issue
     * The resulting HTML is saved as the TOC HTML and the modified HTML of the newsletter
     * issue is saved as @var string htmlcontent $this->tochtml $this->htmlcontent
     */
    private function generate_toc() {
        $toc = new \DOMDocument();
        $rootnode = $toc->createDocumentFragment();
        $rootnode->appendChild($toc->createElement('ol'));
        $node = &$rootnode->firstChild;

        // Analyse the HTML.
        $xpath = new \DOMXPath($this->dom);
        $headlines = array();
        $highestlevel = 7;
        $lowestlevel = 0;
        $levelstodisplay = $this->tocsetting;
        $count = 0;
        $previouslevel = null;
        foreach ($xpath->query(
                '//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]') as $headline) {
            // Get level of current headline.
            $curr = null;
            sscanf($headline->tagName, 'h%u', $curr);
            if ($curr < $highestlevel) {
                $highestlevel = $curr;
            }
            if ($curr > $lowestlevel) {
                $lowestlevel = $curr;
            }
            $h = new \stdClass();
            $h->level = $curr;
            $h->content = $headline->textContent;
            $h->headline = $headline;
            $h->id = 'newsletter-headline-' . $count;
            $headlines[$count] = $h;
            $count++;
        }

        foreach ($headlines as $headlinenode) {
            if ($headlinenode->level < $highestlevel + $levelstodisplay) {
                $this->apply_anchors($headlinenode);

                // Variable @var toclevel: starts from 0 as the most important headline and 1 as the next important and so on.
                $toclevel = $headlinenode->level - $highestlevel;

                if ($previouslevel == $toclevel || (is_null($previouslevel) && $toclevel == 0)) {
                    $node = $node->appendChild($toc->createElement('li'));
                    $a = $toc->createElement('a', $headlinenode->headline->textContent);
                    $a->setAttribute('href', '#' . $headlinenode->id);
                    $node->appendChild($a);
                } else if ($toclevel == 0) {
                    $node = $rootnode->firstChild->appendChild($toc->createElement('ol'));
                    $node = $node->appendChild($toc->createElement('li'));
                    $a = $toc->createElement('a', $headlinenode->headline->textContent);
                    $a->setAttribute('href', '#' . $headlinenode->id);
                    $node->appendChild($a);
                } else if ($previouslevel < $toclevel) {
                    $levelgap = $toclevel - $previouslevel;
                    if ($node->nodeName == 'li') {
                        $node = $node->parentNode;
                    }
                    for ($i = 1; $i <= $levelgap; $i++) {
                        $node = $node->appendChild($toc->createElement('li'));
                        $node = $node->appendChild($toc->createElement('ol'));
                        // When level reached create the entry.
                        if ($i == $levelgap) {
                            $element = $node->appendChild($toc->createElement('li'));
                            $a = $toc->createElement('a', $headlinenode->headline->textContent);
                            $a->setAttribute('href', '#' . $headlinenode->id);
                            $element->appendChild($a);
                        }
                    }
                } else {
                    $levelgap = $previouslevel - $toclevel;
                    if ($node->nodeName == 'li') {
                        $node = $node->parentNode;
                    }
                    for ($i = 1; $i <= $levelgap; $i++) {
                        $node = $node->parentNode;
                    }
                    $node = $node->appendChild($toc->createElement('li'));
                    $a = $toc->createElement('a', $headlinenode->headline->textContent);
                    $a->setAttribute('href', '#' . $headlinenode->id);
                    $node->appendChild($a);
                }
                $previouslevel = $toclevel;
            }
        }
        $toccontainer = $toc->createElement('div');
        $toccontainer->setAttribute('id', 'newsletter-toc');
        $container = $toc->appendChild($toccontainer);
        $container->appendChild($rootnode);
        $this->tochtml = $toc->saveHTML();
        $this->htmlcontent = $this->dom->saveHTML();
    }

    /**
     * Apply the anchors referenced in the table of content to the original HTML of the newsletter issue.
     * Save the modified issue HTML in $htmlcontent
     */
    private function apply_anchors($headlinenode) {
        // Add anchor to headline.
        $a = $this->dom->createElement('a');
        $a->setAttribute('name', $headlinenode->id);
        $a->setAttribute('id', $headlinenode->id);
        $headlinenode->headline->insertBefore($a, $headlinenode->headline->firstChild);
    }

    /**
     * Whether the passed content contains the specified tag
     *
     * @param string $content Content to search for tags.
     * @param string $tag tag to check.
     * @return bool Whether the passed content contains the given tag.
     */
    private function has_tag($content) {
        if (false === strpos($content, 'news://')) {
            return false;
        }
        return true;
    }

    /**
     * Whether a registered tag exists
     *
     * @param string $tag tag to check.
     * @return bool Whether the given shortcode exists.
     */
    private function tag_exists($tag) {
        return array_key_exists($tag, $this->tags);
    }

    /**
     * Search content for tags and filter tags through their hooks.
     * If there are no tag tags defined, then the content will be returned without any
     * filtering.
     *
     * @param object $issue to search for tags.
     * @return string Content with tags filtered out.
     */
    private function replace_tags($issue) {
        $content = $issue->htmlcontent;

        if (false === strpos($content, 'news://')) {
            return $content;
        }

        if (empty($this->tags) || !is_array($this->tags)) {
            return $content;
        }

        // Find all registered tag names in $content.
        $matches = array();
        preg_match_all('@news:\/\/([a-zA-Z0-9_]+)\/@', $content, $matches);
        $tagnames = array_intersect(array_keys($this->tags), $matches[1]);

        if (empty($tagnames)) {
            return $content;
        }

        $pattern = $this->get_tag_regex($tagnames);
        $content = preg_replace_callback("/$pattern/",
                function ($matches) {
                    return $this->perform_tag_replacement($matches);
                }, $content);
        return $content;
    }

    /**
     * Return regular expression for searching all occurances of all tags
     *
     * @param array $tagnames
     * @return string regular expression
     */
    private function get_tag_regex($tagnames = null) {
        if (empty($tagnames)) {
            $tagnames = array_keys($this->tags);
        }
        $tagregexp = join('|', array_map('preg_quote', $tagnames));

        return 'news:\\/\\/' . // Opening.
        "($tagregexp)" . // First: tagname.
        '\\/'; // Closing.
    }

    /**
     * Regular Expression callable for replacing a tag
     *
     * @see $this->get_tag_regex for details of the match array contents.
     * @param array $m Regular expression match array
     * @return string|false False on failure.
     */
    private function perform_tag_replacement($m) {
        $replacement = '';
        $callable = null;
        if (array_key_exists($m[1], $this->tags)) {
            $callable = "\mod_newsletter\issue_parser::" . "replace_" . $m[1];
        }
        if (!is_callable($callable)) {
            $this->tags[$m[1]];
            return $m[0];
        }
        $replacement = call_user_func($callable, $m);
        return $replacement;
    }

    /**
     *
     * @param array $m regular expression match array
     * @return \moodle_url issueurl
     */
    public static function replace_issueurl($m) {
        $url = new \moodle_url('/mod/newsletter/view.php',
                array(NEWSLETTER_PARAM_ID => self::$contextid,
                    NEWSLETTER_PARAM_ISSUE => self::$issueid, NEWSLETTER_PARAM_ACTION => 'readissue'));
        return $url;
    }

    /**
     * return issuelink only if sent as email. Return empty string when viewing online
     *
     * @param array $m regular expression match array
     * @return \moodle_url issuelink
     */
    public static function replace_issuelink($m) {
        $url = new \moodle_url('/mod/newsletter/view.php',
                array(NEWSLETTER_PARAM_ID => self::$contextid,
                    NEWSLETTER_PARAM_ISSUE => self::$issueid, NEWSLETTER_PARAM_ACTION => 'readissue'));
        $link = '<a href="' . $url . '">' . get_string('readonline', 'mod_newsletter') . "</a>";
        if (self::$cron) {
            return $link;
        }
        return '';
    }

    /**
     *
     * @param array $m regular expression match array
     * @return string lastname
     */
    public static function replace_lastname($m) {
        global $USER;
        if (!self::$cron and !isloggedin() or isguestuser()) {
            return get_string('user');
        }
        if (self::$cron) {
            return $m[0];
        } else {
            return $USER->lastname;
        }
    }

    /**
     *
     * @param array $m regular expression match array
     * @return string lastname
     */
    public static function replace_firstname($m) {
        global $USER;
        if (!self::$cron and !isloggedin() or isguestuser()) {
            return get_string('guest');
        }
        if (self::$cron) {
            return $m[0];
        } else {
            return $USER->firstname;
        }
    }

    /**
     *
     * @param array $m regular expression match array
     * @return string fullname
     */
    public static function replace_fullname($m) {
        global $USER;
        if (!self::$cron and !isloggedin() or isguestuser()) {
            return get_string('guest') . " " . get_string('user');
        }
        if (self::$cron) {
            return $m[0];
        } else {
            return fullname($USER);
        }
    }
}
