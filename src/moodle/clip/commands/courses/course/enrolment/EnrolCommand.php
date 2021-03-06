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

namespace moodle\clip\commands\courses\course\enrolment;

use clip\command\Command;
use clip\command\Params;
use clip\command\WaitForInput;
use clip\Console;
use clip\Context;
use clip\PrintBuilder;

class EnrolCommand extends Command {

    public static function name(): string {
        return 'enrol';
    }

    public static function description(): string {
        return 'Enrol user in course';
    }

    public static function usage(PrintBuilder $builder) {
        $builder->writeln('Usage: enrol [username] {student/teacher}');
    }

    public function execute(Console $console, ?Context $context, Params $params): ?WaitForInput {
        if($params->get(0)===null || !in_array($params->get(1),['student','teacher']))
        {
            $builder=$console->printBuilder()->writeln('Missing parameter.')->send();
            self::usage($builder);
        }
        //TODO: Implement
        return null;
    }
}