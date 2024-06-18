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

namespace mod_newsletter;
use editor_tiny\editor;

/**
 * Add custom css to the default editor. This is quite a hack due to the hacky implementation of Tiny 6 editor in Moodle.
 */
class newsletter_editor extends editor {
    /**
     * Use this editor for given element.
     *
     * @param string $elementid
     * @param array $options
     * @param array $fpoptions
     * @param null $issue
     */
    public function use_editor($elementid, array $options = null, $fpoptions = null, $issue = null, array $files = []) {
        global $PAGE, $CFG;
        // Ensure that the default configuration is set.
        self::reset_default_configuration();
        self::set_default_configuration($this->manager);

        if ($fpoptions === null) {
            $fpoptions = [];
        }

        $context = $PAGE->context;

        if (isset($options['context']) && ($options['context'] instanceof \context)) {
            // A different context was provided.
            // Use that instead.
            $context = $options['context'];
        }

        $cssurls = [];
        $cssurls[NEWSLETTER_DEFAULT_STYLESHEET] = "{$CFG->wwwroot}/mod/newsletter/reset.css";
        if (!empty($files)) {
            foreach ($files as $file) {
                $url = "{$CFG->wwwroot}/pluginfile.php/{$file->get_contextid()}/mod_newsletter/" . NEWSLETTER_FILE_AREA_STYLESHEET;
                $cssurls[$file->get_id()] = $url . $file->get_filepath() . $file->get_itemid() . '/' . $file->get_filename();
            }
        }
        // Generate the configuration for this editor.
        $siteconfig = get_config('editor_tiny');
        $config = (object) [
            // The URL to the CSS file for the editor.
                'css' => $cssurls[$issue->stylesheetid],

            // The current context for this page or editor.
                'context' => $context->id,

            // File picker options.
                'filepicker' => $fpoptions,

                'currentLanguage' => current_language(),

                'branding' => property_exists($siteconfig, 'branding') ? !empty($siteconfig->branding) : true,

            // Language options.
                'language' => [
                        'currentlang' => current_language(),
                        'installed' => get_string_manager()->get_list_of_translations(true),
                        'available' => get_string_manager()->get_list_of_languages()
                ],

            // Placeholder selectors.
            // Some contents (Example: placeholder elements) are only shown in the editor, and not to users. It is unrelated to the
            // real display. We created a list of placeholder selectors, so we can decide to or not to apply rules, styles... to
            // these elements.
            // The default of this list will be empty.
            // Other plugins can register their placeholder elements to placeholderSelectors list by calling
            // editor_tiny/options::registerPlaceholderSelectors.
                'placeholderSelectors' => [],

            // Plugin configuration.
                'plugins' => $this->manager->get_plugin_configuration($context, $options, $fpoptions, $this),

            // Nest menu inside parent DOM.
                'nestedmenu' => true,
        ];

        if (defined('BEHAT_SITE_RUNNING') && BEHAT_SITE_RUNNING) {
            // Add sample selectors for Behat test.
            $config->placeholderSelectors = ['.behat-tinymce-placeholder'];
        }

        foreach ($fpoptions as $fp) {
            // Guess the draftitemid for the editor.
            // Note: This is the best we can do at the moment.
            if (!empty($fp->itemid)) {
                $config->draftitemid = $fp->itemid;
                break;
            }
        }

        $configoptions = json_encode(convert_to_array($config));

        // Note: This is not ideal but the editor does not have control over any HTML output.
        // The Editor API only allows you to run JavaScript.
        // In the future we will extend the editor API to allow it to generate the textarea, or attributes to use in the
        // textarea or its wrapper.
        // For now we cannot use the `js_call_amd()` API call because it warns if the parameters passed exceed a
        // relatively low character limit.
        $inlinejs = <<<EOF
            M.util.js_pending('editor_tiny/editor');
            require(['editor_tiny/editor'], (Tiny) => {
                Tiny.setupForElementId({
                    elementId: "${elementid}",
                    options: ${configoptions},
                });
                M.util.js_complete('editor_tiny/editor');
            });
        EOF;
        $PAGE->requires->js_amd_inline($inlinejs);
    }
}