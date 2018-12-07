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

namespace moodle\clip\context\courses;

use clip\Console;
use clip\Context;
use moodle\clip\commands\courses\course\EnrolCommand;
use moodle\clip\commands\courses\course\UnEnrolCommand;
use moodle\clip\context\courses\course\EnrolmentContext;
use moodle\clip\context\courses\course\KosCourseContext;

class CourseContext extends Context {

    public function __construct(Console $console, array $options) {
        parent::__construct($console, $options);
        if (count($options) != 1 || !ctype_digit($options[0])) {
            throw new \Exception('Context need courseid. Usage: cd course [courseid]');
        }
    }

    public function custom_name(): string {
        return parent::custom_name().'('.$this->options[0].')';
    }

    public static function name(): string {
        return 'Course';
    }

    public static function description(): string {
        return 'Manage course.';
    }

    protected function context_commands(): array {
        return [
        ];
    }

    protected function context_childs(): array {
        return [
                EnrolmentContext::class,
                KosCourseContext::class,

        ];
    }
}