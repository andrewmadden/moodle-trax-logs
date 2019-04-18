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
 * xAPI transformation of a Moodle event.
 *
 * @package    logstore_trax
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace logstore_trax\src\statements\mod_hvp;

defined('MOODLE_INTERNAL') || die();

use logstore_trax\src\statements\base_statement;
use logstore_trax\src\utils\module_context;

/**
 * xAPI transformation of a Moodle event.
 *
 * @package    logstore_trax
 * @copyright  2019 Sébastien Fraysse {@link http://fraysse.eu}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class course_module_viewed extends base_statement {

    use module_context;

    /**
     * Build the Statement.
     *
     * @return array
     */
    protected function statement() {
        global $DB;

        // Get the H5P type.
        $hvp = $DB->get_record('hvp', array('id' => $this->event->objectid));
        $library = $DB->get_record('hvp_libraries', array('id' => $hvp->main_library_id));
        $hvptype = $library->machine_name;

        // Get the vocab type.
        $vocabtype = 'hvp-poll';
        if (in_array($hvptype, ['H5P.SingleChoiceSet', 'H5P.QuestionSet'])) {
            $vocabtype = 'hvp-quiz';
        }

        // Build the statement.
        return array_replace($this->base($this->event->objecttable, true, $vocabtype), [
            'actor' => $this->actors->get('user', $this->event->userid),
            'verb' => $this->verbs->get('navigated-in'),
            'object' => $this->activities->get($this->event->objecttable, $this->event->objectid, true, 'module', $vocabtype),
        ]);
    }

}