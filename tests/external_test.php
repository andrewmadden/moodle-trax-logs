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
 * Trax Logs for Moodle.
 *
 * @package    logstore_trax
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/test_config.php');

use \logstore_trax\Controller;

class external_test extends test_config {
    
    /**
     * Get an actor.
     */
    public function test_get_actor_and_activity()
    {
        // Generate data
        $user = $this->prepare_session();
        $course = $this->getDataGenerator()->create_course();
        $lti = $this->getDataGenerator()->create_module('lti', array('course' => $course->id));

        // Send a Statement
        \mod_lti\event\course_module_viewed::create([
            'objectid' => $lti->id,
            'context' => context_module::instance($lti->cmid),
        ])->trigger();

        // Check data
        $controller = new Controller();

        // User
        $actor = $controller->actor('user', $user->id);
        $this->assertTrue($actor && isset($actor['account']) && isset($actor['account']['name']));

        // System
        $activity = $controller->activity('system', 0);
        $this->assertTrue($activity && isset($activity['id']));

        // Course
        $activity = $controller->activity('course', $course->id);
        $this->assertTrue($activity && isset($activity['id']));

        // LTI module
        $activity = $controller->activity('lti', $lti->id);
        $this->assertTrue($activity && isset($activity['id']));

        // Non existing module
        try {
            $activity = $controller->activity('lti', 65416871984164);
            $this->assertTrue(false);
        } catch (\moodle_exception $e) {
            $this->assertTrue(true);
        }
    }

}