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
 * This is obsolete. To be deleted in the future. Maybe if Moodle does a better integration of Tiny 6, it could be used again.
 *
 * @param editorconfig
 * @returns {Promise<void>}
 */
export const loadCss = async function(editorconfig) {
    var select = document.querySelector('#id_stylesheetid');
    if (select) {
        select.addEventListener('change', change_stylesheet);
    }
    /**
     * Function to wait until tinyMCE is loaded
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
            checkIfLoaded();
        });
    }

    /**
     * Function to change CSS for the content inside TinyMCE
     */
    waitUntilTinyMCELoaded()
        .then((tinyMCE) => {
            console.log('tinyMCE is loaded:', tinyMCE);
            const existingEditor = tinyMCE.add('id_htmlcontent');
            if(existingEditor) {
                console.log(existingEditor);
            }
            // Call function to change CSS for TinyMCE content
            tinyMCE.init(editorconfig);
            change_stylesheet();
        });

    /**
     * Changes the stylesheet based on the selected option.
     */
    function change_stylesheet() {
        console.log('config');
    }
};
