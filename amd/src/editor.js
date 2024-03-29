/* eslint-disable no-console */
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
 * Loads CSS for Editor
 *
 * @module     mod_newsletter/load_CSS
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getTinyMCE} from 'editor_tiny/loader';

var M = {
    mod_newsletter: {}
};

var tinymce = null;

M.mod_newsletter.collapse_subscribe_form = function() {
    var fieldset = document.querySelector('form #id_subscribe');
    if (fieldset) {
        fieldset.classList.add('collapsed');
    }
};

export const loadCss = async function(stylesheets, selected) {
    tinymce = await getTinyMCE();
    var select = document.querySelector('#id_stylesheetid');

    /**
     * Changes the stylesheet based on the selected option.
     * @param {Object} e Event object containing information about the target element.
     */
    function change_stylesheet(e) {
        var select = e.target;
        var selectedIndex = select.value;
        tinymce.remove();
        tinymce.init({
            selector: 'textarea',
            content_css: stylesheets[selectedIndex]
        });
    }

    // Execute change_stylesheet function initially
    change_stylesheet({target: select});

    if (select) {
        select.addEventListener('change', change_stylesheet);
    } else {
        setTimeout(function() {
            M.mod_newsletter.init_editor(stylesheets, selected);
        }, 100);
    }
};
