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
 * Parsing newsletter issues for creating table of contents
 * and replace tags, etc.
 *
 * @package mod_newsletter
 * @copyright 2015 onwards David Bogner <info@edulabs.org>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *         
 */
namespace mod_newsletter;

use MyProject\Proxies\__CG__\OtherProject\Proxies\__CG__\stdClass;

class mod_newsletter_issue_parser {
	
	/**
	 * @var \DOMDocument to be parsed
	 */
	private $dom = NULL;
	
	/**
	 * @var string HTML table of contents
	 */
	private $toc_html = NULL;
	
	/**
	 * @var string HTML the altered HTML
	 */
	private $htmlwithanchors = NULL;
	
	/**
	 * @var number toc setting
	 */
	private $tocsetting = NULL;
	
	/**
	 * Convert the inital HTML to \DOMDocument
	 * 
	 * @param objed $issue        	
	 */
	public function __construct($issue) {
		$this->dom = new \DOMDocument ();
		@$this->dom->loadHTML (mb_convert_encoding($issue->htmlcontent, 'HTML-ENTITIES', 'UTF-8' ));
		$this->tocsetting = $issue->toc;
	}
	
	/**
	 * Return the table of content and the modified newsletter content with anchors as an array
	 * Generate if it is not yet generated
	 *
	 * @return string htmlcontent
	 */
	public function get_toc_and_doc() {
		if (is_null ( $this->toc_html )) {
			$this->generate_toc ();
		}
		return $this->toc_html . $this->htmlwithanchors;
	}
	
	/**
	 * Generate the table of content for the newsletter issue
	 * The resulting HTML is saved as the TOC HTML and the modified HTML of the newsletter issue is saved
	 * as @var string htmlwithanchors
	 * $this->toc_html
	 * $this->htmlwithanchors
	 */
	private function generate_toc() {
		$toc = new \DOMDocument ();
		$rootnode = $toc->createDocumentFragment ();
		$rootnode->appendChild ( $toc->createElement ( 'ol' ) );
		$node = &$rootnode->firstChild;
		
		// analyse the HTML
		$xpath = new \DOMXPath ( $this->dom );
		$headlines = array ();
		$highestlevel = 7;
		$lowestlevel = 0;
		$levels_to_display = $this->tocsetting;
		$count = 0;
		$previouslevel = null;
		foreach ( $xpath->query ( '//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]' ) as $headline ) {
			// get level of current headline
			sscanf ( $headline->tagName, 'h%u', $curr );
			if ($curr < $highestlevel) {
				$highestlevel = $curr;
			}
			if ($curr > $lowestlevel) {
				$lowestlevel = $curr;
			}
			$h = new \stdClass ();
			$h->level = $curr;
			$h->content = $headline->textContent;
			$h->headline = $headline;
			$h->id = 'newsletter-headline-' . $count;
			$headlines [$count] = $h;
			$count ++;
		}
		
		foreach ( $headlines as $headlinenode ) {
			if ($headlinenode->level < $highestlevel + $levels_to_display) {
				$this->apply_anchors ( $headlinenode );
				
				// @var toclevel: starts from 0 as the most important headline and 1 as the next important and so on
				$toclevel = $headlinenode->level - $highestlevel;
				
				if ($previouslevel == $toclevel || (is_null ( $previouslevel ) && $toclevel == 0)) {
					$node = $node->appendChild ( $toc->createElement ( 'li' ) );
					$a = $toc->createElement ( 'a', $headlinenode->headline->textContent );
					$a->setAttribute ( 'href', '#' . $headlinenode->id );
					$node->appendChild ( $a );
				} else if ($toclevel == 0) {
					$node = $rootnode->firstChild->appendChild ( $toc->createElement ( 'li' ) );
					$a = $toc->createElement ( 'a', $headlinenode->headline->textContent );
					$a->setAttribute ( 'href', '#' . $headlinenode->id );
					$node->appendChild ( $a );
				} else if ($previouslevel < $toclevel) {
					$levelgap = $toclevel - $previouslevel;
					if ($node->nodeName == 'li') {
						$node = $node->parentNode;
					}
					for($i = 1; $i <= $levelgap; $i ++) {
						$node = $node->appendChild ( $toc->createElement ( 'ol' ) );
						// when level reached create the entry
						if ($i == $levelgap) {
							$element = $node->appendChild ( $toc->createElement ( 'li' ) );
							$a = $toc->createElement ( 'a', $headlinenode->headline->textContent );
							$a->setAttribute ( 'href', '#' . $headlinenode->id );
							$element->appendChild ( $a );
						}
					}
				} else {
					$levelgap = $previouslevel - $toclevel;
					if ($node->nodeName == 'li') {
						$node = $node->parentNode;
					}
					for($i = 1; $i <= $levelgap; $i ++) {
						$node = $node->parentNode;
					}
					$node = $node->appendChild ( $toc->createElement ( 'li' ) );
					$a = $toc->createElement ( 'a', $headlinenode->headline->textContent );
					$a->setAttribute ( 'href', '#' . $headlinenode->id );
					$node->appendChild ( $a );
				}
				$previouslevel = $toclevel;
			}
		}
		$toccontainer = $toc->createElement ( 'div' );
		$toccontainer->setAttribute ( 'id', 'newsletter-toc' );
		$container = $toc->appendChild ( $toccontainer );
		$container->appendChild ( $rootnode );
		$this->toc_html = $toc->saveHTML ();
		$this->htmlwithanchors = $this->dom->saveHTML ();
	}
	
	/**
	 * Apply the anchors referenced in the table of content
	 * to the original HTML of the newsletter issue.
	 * Save the
	 * modified issue HTML in $htmlwithanchors
	 */
	private function apply_anchors($headlinenode) {
		// add anchor to headline
		$a = $this->dom->createElement ( 'a' );
		$a->setAttribute ( 'name', $headlinenode->id );
		$a->setAttribute ( 'id', $headlinenode->id );
		$headlinenode->headline->insertBefore ( $a, $headlinenode->headline->firstChild );
	}
}
