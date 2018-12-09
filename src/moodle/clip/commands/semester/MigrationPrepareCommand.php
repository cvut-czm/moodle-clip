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
use local_kos\instance;
use moodle\clip\context\SemesterContext;
use PHPUnit\Exception;

class MigrationPrepareCommand extends Command {

    public static function name() : string {
        return 'migration-prepare';
    }

    public static function description() : string {
        return 'Prepare data for migration';
    }

    public static function usage(PrintBuilder $builder) {
        // TODO: Implement usage() method.
    }

    /** @var SemesterPreperation $prep */
    private $prep;
    /** @var Console $console */
    private $console;
    /** @var SemesterContext $context */
    private $context;
    /** @var PrintBuilder $out */
    private $out;

    public function loadExtra() {
        $this->prep->loadExtra();
        $this->out->deleteLastLine()->writeln('Loaded courses that were already in semester')->send()->progressBar()
                ->display(20, 'Fetching semester course list from Kos', false);
        $this->console->executeFuture([$this, 'loadCourseList']);
    }

    public function loadCourseList() {
        try {
            $this->prep->loadCourseList();
            $this->out->deleteLastLine()->writeln('Fetched ' . count($this->prep->allSmesterCourses) . ' courses from KOS.')->send()
                    ->progressBar()
                    ->display(60, 'Searching for courses in last semester', false);
            $this->console->executeFuture([$this, 'loadLastSemester']);

        } catch (\Exception $e) {
            $this->out->writeln("Cannot connect to KOS")->sendInputLine();
        }
    }

    public function loadLastSemester() {
        $sem = $this->context->getSemester()->get_previous();
        $this->prep->findCoursesInSemester($sem);
        $this->out->deleteLastLine()->writeln('Last semester contains ' . count($this->prep->readyToLoad[$sem->code]) .
                ' courses.')
                ->send()
                ->progressBar()
                ->display(80, 'Searching for courses in last year semester', false);

        $this->console->executeFuture([$this, 'loadLastYear']);
    }

    public function loadLastYear() {
        $sem = $this->context->getSemester()->get_previous()->get_previous();
        $this->prep->findCoursesInSemester($sem);
        $this->out->deleteLastLine()->writeln('Last year semester contains ' . count($this->prep->readyToLoad[$sem->code]) .
                ' courses.')
                ->progressBar()
                ->display(90, 'Creating course grouping', false);
        $this->console->executeFuture([$this, 'groupCourses']);
    }

    public function groupCourses() {
        $last = $this->context->getSemester()->get_previous();
        $lastyear = $last->get_previous();
        $this->prep->groupCourses($last);
        $this->prep->groupCourses($lastyear);
        $this->out->deleteLastLine()
                ->writeln('Semester ' . $last->code . ' courses: ' . count($this->prep->readyToLoad[$last->code]))->send()
                ->writeln( 'Semester ' . $last->code .' groupings:' . count($this->prep->groupings[$last->code]))->send()
                ->writeln('Semester ' . $lastyear->code . ' courses: ' . count($this->prep->readyToLoad[$lastyear->code]))->send()
                ->writeln('Semester ' . $lastyear->code .' groupings:' . count($this->prep->groupings[$lastyear->code]))->sendInputLine();
        $this->context->cacheAdd('prep', $this->prep);
    }

    /**
     * @param SemesterContext $context
     */
    public function execute(Params $params) : ?WaitForInput {
        $out = $this->console()->printBuilder();
        $out->writeln('Preparing migration for semester ' . $this->context()->getSemester()->code)->send();
        $out->writeln('Departments for migration: ' . join(',', instance::cache_course_from()))->send();
        $out->progressBar()->display(10, 'Loading courses already in semester', false);

        $this->prep = new SemesterPreperation($this->context()->getSemester());
        $this->context=$this->context();
        $this->console=$this->console();
        $this->out = $out;

        $this->console->executeFuture([$this, 'loadExtra']);
        return null;
    }
}