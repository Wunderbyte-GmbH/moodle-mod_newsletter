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

var newsletter = {
    mod_newsletter: {}
};

newsletter.mod_newsletter.collapse_subscribe_form = function() {
    var fieldset = document.querySelector('form #id_subscribe');
    if (fieldset) {
        fieldset.classList.add('collapsed');
    }
};

export const loadCss = async function(stylesheets, selected) {
    var select = document.querySelector('#id_stylesheetid');

    /**
     * Wait for tinyMCE to be loaded.
     * @returns {Promise}
     */
    function waitUntilTinyMCELoaded() {
        return new Promise((resolve) => {
            /**
             * Check if tinyMCE object is available
             */
            function checkIfLoaded() {
                if (window.tinyMCE) {
                    // If tinyMCE is available, resolve the promise
                    resolve(window.tinyMCE);
                } else {
                    // If not, wait and check again
                    setTimeout(checkIfLoaded, 100); // Check every 100 milliseconds
                }
            }

            // Start checking if loaded
            checkIfLoaded();
        });
    }
    waitUntilTinyMCELoaded()
        .then((tinyMCE) => {
            console.log('tinyMCE is loaded:', tinyMCE);
            change_stylesheet({target: select});
            tinyMCE.init({
                content_css: stylesheets[selected]
            });
            // Proceed with further operations using tinyMCE
        });


    /**
     * Changes the stylesheet based on the selected option.
     * @param {Object} e Event object containing information about the target element.
     */
    function change_stylesheet(e) {
        let selectedTarget = e.target;
        let selectedIndex = selectedTarget.value;
        tinyMCE.remove();
        tinyMCE.init({
            selector: 'textarea',
            content_css: stylesheets[selectedIndex]
        });
    }

    if (select) {
        select.addEventListener('change', change_stylesheet);
    } else {
        setTimeout(function() {
            newsletter.mod_newsletter.init_editor(stylesheets, selected);
        }, 100);
    }
};
