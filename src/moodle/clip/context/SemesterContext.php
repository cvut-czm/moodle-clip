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

namespace moodle\clip\context;

use clip\Console;
use clip\Context;
use local_kos\entity\semester;
use moodle\clip\commands\semester\InfoCommand;
use moodle\clip\commands\semester\LoadCommand;
use moodle\clip\commands\semester\MigrationApplyCommand;
use moodle\clip\commands\semester\MigrationPrepareCommand;
use moodle\clip\commands\semester\RepairDateCommand;
use moodle\clip\commands\semester\TestLoadCommand;

class SemesterContext extends Context {
    private $semester;

    public function getSemester() : semester
    {
        return $this->semester;
    }
    public function __construct(Console $console, array $options) {
        parent::__construct($console, $options);
        if (count($options) != 1) {
            throw new \Exception('Semester need code. Usage: cd semester [code] Example: cd semester B171');
        }
        $test = strlen($options[0]) != 4 || $options[0][0] !== 'B' || !ctype_digit($options[0][1] . $options[0][2]) ||
                ($options[0][3] != 1 && $options[0][3] != 2);
        if($test)
            throw new \Exception('Semester need code. Usage: cd semester [code] Example: cd semester B171');
        $this->semester=semester::get(['code'=>$options[0]]);
        if($this->semester==null)
            throw new \Exception('Semester does not exist in moodle. Create it with: semester-init {code}');
    }

    public static function name(): string {
        return 'Semester';
    }

    public function custom_name(): string {
        return parent::custom_name() . '(' . $this->options[0] . ')';
    }

    public static function description(): string {
        return 'Semester control';
    }

    protected function context_commands(): array {
        return [InfoCommand::class,LoadCommand::class,MigrationPrepareCommand::class,
                MigrationApplyCommand::class,RepairDateCommand::class];
    }

    protected function context_childs(): array {
        return [];
    }
}