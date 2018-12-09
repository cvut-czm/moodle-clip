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
 * This class provide full control over sandboxes.
 *
 * @package local_personal_sandbox
 * @category core
 * @copyright 2018 CVUT CZM, Jiri Fryc
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace moodle\clip\commands\semester;

use clip\command\Command;
use clip\command\Params;
use clip\command\WaitForInput;
use clip\Console;
use clip\Context;
use clip\PrintBuilder;
use local_kos\entity\semester_location;
use moodle\clip\context\SemesterContext;

class InfoCommand extends Command {

    public static function name() : string {
        return 'info';
    }

    public static function description() : string {
        return 'Information about semester in moodle.';
    }

    public static function usage(PrintBuilder $builder) {
        // TODO: Implement usage() method.
    }

    /**
     * @param SemesterContext $context
     */
    public function execute(Params $params) : ?WaitForInput {
        $s = $this->context()->getSemester();
        $this->console()->printBuilder()->writeln('Semester ' . $s->code)->send()
                ->writeln($s->get_long_name())->send()
                ->write($s->get_start_date()->print_standard_date())->write(' - ')->writeln($s->get_end_date()
                        ->print_standard_date())->send()
                ->writeln('State: '.semester_location::code_of($s->location))->send()
                ->writeln('Courses in semester: '.count($s->get_course_instances()))->sendInputLine();

        return null;
    }
}