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

var M = {
    mod_newsletter: {}
};

M.mod_newsletter.collapse_subscribe_form = function() {
    var fieldset = document.querySelector('form #id_subscribe');
    if (fieldset) {
        fieldset.classList.add('collapsed');
    }
};

M.mod_newsletter.init_editor = function(stylesheets, selected) {
    var tinymceEditor = document.querySelector('.mce-tinymce');
    if (tinymceEditor === null) {
        console.error('Error: TinyMCE editor is required for init_editor function in mod_newsletter.');
    }
    /**
     * Changes the stylesheet based on the selected option.
     * @param {Object} e Event object containing information about the target element.
     */
    function change_stylesheet(e) {
        var select = e.target;
        var selectedIndex = select.value;
        document.querySelectorAll('head link').forEach(function (node) {
            node.remove();
        });
        var link1 = document.createElement('link');
        link1.type = 'text/css';
        link1.rel = 'stylesheet';
        link1.href = stylesheets[0];
        document.head.appendChild(link1);
        var link2 = document.createElement('link');
        link2.type = 'text/css';
        link2.rel = 'stylesheet';
        link2.href = stylesheets[selectedIndex];
        document.head.appendChild(link2);
    }

    var select = document.querySelector('#id_stylesheetid');
    if (select) {
        select.addEventListener('change', change_stylesheet);
    } else {
        setTimeout(function () {
            M.mod_newsletter.init_editor(stylesheets, selected);
        }, 100);
    }
};