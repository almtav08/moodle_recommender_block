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
 * Defines the form for editing activity results block instances.
 *
 * @package    block_hybridrecom
 * @copyright  2024 Alex Martinez <alemarti@uji.es>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    // Default ip address of the recommender server.
    $setting = new admin_setting_configtext('block_hybridrecom/config_ipaddress',
        new lang_string('ipaddress', 'block_hybridrecom'),
        new lang_string('ipaddress_desc', 'block_hybridrecom'), 'http://127.0.0.1:8080', PARAM_TEXT);
    $settings->add($setting);

    // Default low scores.
    $setting = new admin_setting_configtext('block_hybridrecom/config_top',
        new lang_string('top', 'block_hybridrecom'),
        new lang_string('top_desc', 'block_hybridrecom'), 5, PARAM_INT);
    $settings->add($setting);

    $setting = new admin_setting_configtext('block_hybridrecom/config_key',
        new lang_string('key', 'block_hybridrecom'),
        new lang_string('key_desc', 'block_hybridrecom'), "API KEY", PARAM_TEXT);
    $settings->add($setting);
}
