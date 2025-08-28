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
 * Block definition class for the block_hybridrecom plugin.
 *
 * @package   block_hybridrecom
 * @copyright 2024 Alex Martinez <alemarti@uji.es>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_hybridrecom extends block_base {

    /**
     * Initialises the block.
     *
     * @return void
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_hybridrecom');
    }

    /**
     * Gets the block contents.
     *
     * @return string The block HTML.
     */
    public function get_content() {
        global $OUTPUT;
        global $DB;
        global $USER;
        global $PAGE;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass();
        $this->content->footer = '';

        $this->content->text = '<div style="padding: 0.75em 1.25em; position: relative;"> <b>What should you do next?</b> <button commandfor="disclaimer" command="show-modal" style=" position: absolute; right: -0.5rem; top: -0.5rem; background-color: gray; border-radius: 100%; font-size: 0.75em; width: 1.5rem; height: 1.5rem; display: flex; justify-content: center; align-items: center; color: white; font-weight: 600; cursor: pointer; user-select: none; border: none;">i</button> </div> <hr>';

        // Make a request here to an external API to fetch data.
        $data = $this->fetch_data_from_api($USER->id);

        $this->content->text .= $data;

        return $this->content;
    }

    private function fetch_data_from_api($userid) {
        global $DB;
        

        // Make a request to an external API to fetch data.
        $ip_adderess = get_config('block_hybridrecom', 'config_ipaddress');
        $top = get_config('block_hybridrecom', 'config_top'); // Number of recommendations to fetch
        $api_url = $ip_adderess . '/recommendations/' . $userid . '/' . $top;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
            curl_close($ch);
            return 'Error fetching data from API: ' . $error_msg;
        }

        curl_close($ch);

        $data = json_decode($response);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return 'Error decoding API response: ' . json_last_error_msg();
        }

        $output = '';

        $modulesid = array_column($data, 'id');
        $ids = implode(',', $modulesid);

        $reasons = array_column($data, 'reason');

        $recommended_modules = $DB->get_records_sql(
            "SELECT cm.id, cm.module, m.name as modulename
            FROM {course_modules} AS cm
            JOIN {modules} AS m ON cm.module = m.id
            WHERE cm.id IN (" . $ids . ")"
        );

        // Create a map of recommended modules for quick lookup
        $recommended_modules_map = [];
        foreach ($recommended_modules as $resource) {
                $recommended_modules_map[$resource->id] = $resource;
        }

        $output .= '<div style="display: flex; flex-flow: column nowrap; aligh-items: center; justify-content: center; gap: 0.5em;">';
        $output .= '<dialog id="disclaimer" style="max-width: 50rem;">
            <h2>Disclaimer</h2>
            <p>
              The recommendations provided by this system are generated using advanced artificial intelligence techniques. Rather than analyzing the content of the resources directly, the system leverages behavioral patterns from other users along with expert-defined rules to offer suggestions tailored to your potential interests.
            </p>
            <p>
              While the system is designed to provide relevant and helpful recommendations, users are encouraged to complement them with their own judgment to ensure the best possible experience.
            </p>
            <button commandfor="disclaimer" command="close" style="padding-block: 4px; padding-inline: 8px; cursor: pointer;">Close</button>
          </dialog>';
        /*
        Necesary CSS for the tooltip functionality
        <style>
        .tooltip:hover span { 
            opacity: 1;
            visibility: visible;
        }
        .tooltip span {
            padding: 10px;
            top: 20px;
            min-width: 75px;
            max-width: 100%;
            background-color: #000000; 
            color: #FFFFFF;
            height: auto;
            border-radius: 5px; 
            opacity: 0; 
            position:absolute;
            visibility: hidden;
            word-wrap: break-word;
            transition: all 0.5s;
        }
        </style>

        */

        // Asing css classes to the modules for icon color
        $colormap = array(
            'assign' => 'assessment',
            'book' => 'content',
            'choice' => 'communication',
            'data' => 'collaboration',
            'feedback' => 'communication',
            'file' => 'content',
            'folder' => 'content',
            'forum' => 'collaboration',
            'glossary' => 'collaboration',
            'imscp' => 'interactivecontent',
            'lesson' => 'interactivecontent',
            'page' => 'content',
            'quiz' => 'assessment',
            'scorm' => 'interactivecontent',
            'url' => 'content',
            'wiki' => 'collaboration',
            'workshop' => 'assessment',
        );
        $zindex = 100 + (int) $top;
        $reason_idx = 0;

        // Iterate over the modulesid array to order the output
        foreach ($modulesid as $module_id) {
            if (isset($recommended_modules_map[$module_id])) {
                $resource = $recommended_modules_map[$module_id];
                $course_module = get_coursemodule_from_id($resource->modulename, $resource->id, 0, false, MUST_EXIST);
                $course = $DB->get_record('course', array('id' => $course_module->course));
                $module_info = get_fast_modinfo($course->id)->get_cm($module_id);
                $icon = $module_info->get_icon_url();
                $resource_name = format_string($course_module->name);
                $course_shortname = format_string($course->shortname);
                $resource_link = new moodle_url('/mod/' . $resource->modulename . '/view.php', array('id' => $course_module->id));
                $output .= '<div class="tooltip" style="background-color: #f8f8ff; border-radius: 10px; padding: 0.75em 1.25em; position: relative; display: inline-block; opacity: 1; z-index: ' . $zindex . ';"> <div class="smaller activityiconcontainer '. $colormap[$resource->modulename] .'" style="display: inline; padding: 0;"> <img src="' . $icon . '" class="activityicon " data-region="activity-icon" data-id="' . $module_id . '" alt> </div> <a href="' . $resource_link . '">' . $resource_name . '</a> <div style="position: absolute; right: -0.5rem; top: -0.5rem; background-color: gray; border-radius: 100%; font-size: 0.75em; width: 1.5rem; height: 1.5rem; display: flex; justify-content: center; align-items: center; color: white; font-weight: 600; cursor: pointer; user-select: none;">i</div><span>' . $reasons[$reason_idx] . '</span></div>';
                $zindex--;
                $reason_idx++;
            }
        }

        $output .= '</div>';

        return $output;
    }

    /**
     * Defines in which pages this block can be added.
     *
     * @return array of the pages where the block can be added.
     */
    public function applicable_formats() {
        return [
            'admin' => false,
            'site-index' => true,
            'course-view' => true,
            'mod' => false,
            'my' => true,
        ];
    }

    function has_config() {
        return true;
    }
}