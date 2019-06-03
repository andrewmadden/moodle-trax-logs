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
 * xAPI transformation of a H5P event.
 *
 * @package    logstore_trax
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_trax\src\statements\logstore_trax;

defined('MOODLE_INTERNAL') || die();

use logstore_trax\src\statements\base_statement;
use logstore_trax\src\utils\inside_module_context;
use logstore_trax\src\utils;

/**
 * xAPI transformation of a H5P event.
 *
 * @package    logstore_trax
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hvp_quiz_question_answered extends base_statement {

    use inside_module_context;

    /**
     * Vocab type of the H5P activity.
     *
     * @var string $vocabtype
     */
    protected $vocabtype = 'hvp-quiz';


    /**
     * Build the Statement.
     *
     * @return array
     */
    protected function statement() {

        // Get the H5P statement.
        $statement = json_decode($this->eventother->statement);

        // Base statement (includes context).
        $base = $this->base('hvp', true, $this->vocabtype);

        // Transform native object.
        $object = $statement->object;
        $object = $this->transform_object($statement->object, $base);

        // Statement props.
        $props = [
            'actor' => $this->actors->get('user', $this->event->userid),
            'verb' => $this->verbs->get('answered'),
            'object' => $object,
        ];
        if (isset($statement->result)) {
            $props['result'] = $statement->result;
        }

        return array_replace($base, $props);
    }

    /**
     * Transform the H5P object.
     *
     * @param \stdClass $nativeobject H5P object
     * @param array $base Statement base
     * @return \stdClass
     */
    protected function transform_object($nativeobject, $base) {

        // Change ID.
        $internalid = explode('subContentId=', $nativeobject->id)[1];
        $nativeobject->id = $base['context']['contextActivities']['parent'][0]['id'] . '/question/' . $internalid;

        // Adapt name and description.
        $this->transform_object_strings($nativeobject, $base);

        // Remove extensions.
        unset($nativeobject->definition->extensions);

        return $nativeobject;
    }

    /**
     * Transform the H5P object strings.
     *
     * @param \stdClass $nativeobject H5P object
     * @param array $base Statement base
     * @return \stdClass
     */
    protected function transform_object_strings($nativeobject, $base)
    {
        global $DB;
        $course = $DB->get_record('course', array('id' => $this->event->courseid), '*', MUST_EXIST);

        // Clean name.
        if (isset($nativeobject->definition->name)) {
            $name = (array)$nativeobject->definition->name;
            $name = reset($name);
            $name = trim($name);
            $nativeobject->definition->name = utils::lang_string($name, $course);
        }

        // Clean description.
        if (isset($nativeobject->definition->description)) {
            $description = (array)$nativeobject->definition->description;
            $description = reset($description);
            $description = trim($description);
            $nativeobject->definition->description = utils::lang_string($description, $course);
        }

        // Move description to empty name.
        if (isset($nativeobject->definition->description) && !isset($nativeobject->definition->name)) {
            $nativeobject->definition->name = $nativeobject->definition->description;
            unset($nativeobject->definition->description);
        }
    }

}