<?php

namespace moodle\clip\commands\semester;

use clip\command\Command;
use clip\command\Params;
use clip\command\WaitForInput;
use clip\Console;
use clip\Context;
use clip\PrintBuilder;
use local_cool\entity\course;
use local_cool\entity\user;
use local_kos\course_builder;
use moodle\clip\context\SemesterContext;

class MigrationApplyCommand extends Command {

    /** @var Console $console */
    private $console;
    /** @var SemesterContext $context */
    private $context;

    public static function name() : string {
        return 'migration-apply';
    }

    public static function description() : string {
        return '';
    }

    public static function usage(PrintBuilder $builder) {
        // TODO: Implement usage() method.
    }

    /**
     * @param Console $console
     * @param SemesterContext $context
     * @param Params $params
     * @return WaitForInput|null
     */
    public function execute(Params $params) : ?WaitForInput {
        global $USER;
        if($params->get(0)==null || !ctype_digit($params->get(0)))
        {
            $this->console()->printBuilder()->writeln('You must supply user id, that will be used as author of this migration.')->send()
            ->writeln('Usage: migration-apply [userid]')->sendInputLine();
            return null;
        }
        if($USER==null)
            $USER=new \stdClass();
        $USER->id=$params->get(0);
        /** @var SemesterPreperation $prep */
        $prep = $this->context()->cacheGet('prep');
        $this->console = $this->console();
        $this->context = $this->context();

        if ($prep == null) {
            $this->console()->printBuilder()->writeln('Migration was not prepared. Running now.')->send();
            $cmd = new MigrationPrepareCommand($this->console(),$this->context(),$params);
            $cmd->execute($params);
            $this->console()->printBuilder()
                    ->writeln('Migration prepared. If you want to apply this migration type "migration-apply" again.')
                    ->sendInputLine();
            return null;
        }

        $this->console()->printBuilder()->write('Migrate only last year semester? [yes/no]:')->send();
        return new WaitForInput([$this, 'migrateOnly']);
    }

    public function migrateOnly(Params $params) : ?WaitForInput {
        if (!in_array($params->get(0), ['yes', 'no'])) {
            $this->console()->printBuilder()->deleteLastLine()->write('Migrate only last year semester? [yes/no]:')->send();
            return new WaitForInput([$this, 'migrateOnly']);
        }
        $this->migrateLastSemester = $params->get(0) == 'no';

        $last = $this->context->getSemester()->get_previous();
        $lastyear = $last->get_previous();
        $prep = $this->context()->cacheGet('prep');
        $prep->groupCourses($last);
        $prep->groupCourses($lastyear);
        if($this->migrateLastYear)
        $this->printBuilder()
                ->writeln('Semester ' . $lastyear->code . ' courses: ' . count($prep->readyToLoad[$lastyear->code]))->send()
                ->writeln('Semester ' . $lastyear->code .' groupings:' . count($prep->groupings[$lastyear->code]))->send();
        if($this->migrateLastSemester)
            $this->printBuilder()
                ->writeln('Semester ' . $last->code . ' courses: ' . count($prep->readyToLoad[$last->code]))->send()
                ->writeln( 'Semester ' . $last->code .' groupings:' . count($prep->groupings[$last->code]))->send();
        $this->console()->printBuilder()->write('Are you sure? [yes/no]:')->send();
        return new WaitForInput([$this, 'confirm']);
    }

    public function confirm(Params $params) : ?WaitForInput {
        if (!in_array($params->get(0), ['yes', 'no'])) {
            $this->console()->printBuilder()->deleteLastLine()->write('Are you sure? [yes/no]:')->send();
            return new WaitForInput([$this, 'migrateOnly']);
        }
        /** @var SemesterPreperation $prep */
        $prep = $this->context()->cacheGet('prep');
        if ($params->get(0) == 'no') {
            return null;
        }

        $sem = $this->context()->getSemester()->get_previous();
        $this->migrate = [];
        if ($this->migrateLastYear) {
            $this->migrate = $prep->groupings[$sem->get_previous()->code];
        }
        if ($this->migrateLastSemester) {
            $this->migrate = $prep->groupings[$sem->code]+$this->migrate;
        }
        $this->context()->cacheAdd('prep', null);
        $this->console()->printBuilder()->writeln('Starting migration for ' . count($this->migrate) . ' groups.')->send();
        if (count($this->migrate) > 0) {
            $this->console->executeFuture([$this, 'migrateCourse'], 0);
        } else {
            $this->console->printBuilder()->writeln('Migration complete.')->sendInputLine();
        }
        return null;
    }

    private $migrateLastYear = true;
    private $migrateLastSemester = true;
    private $migrate = [];
    private $c = 0;

    public function migrateCourse() {
        foreach ($this->migrate as $courseid=>$course) {
            $this->console->printBuilder()->writeln('[' . $this->c . '] Migration from courseid(' . $courseid . ') courses(' .
                    join(',', $course) . ')')->send();
            course_builder::create()->add_kos_courses($course)->set_main_course($course[0])->set_semester($this->context->getSemester())

                    ->duplicate_from(course::get(['id' => $courseid]))->build();
            $this->console->printBuilder()->writeln('[' . $this->c . '] Migrated.')->send();
            unset($this->migrate[$courseid]);
            if (count($this->migrate) > 0) {
                $this->console->executeFuture([$this, 'migrateCourse'], 0);
            } else {
                $this->console->printBuilder()->writeln('Migration complete.')->sendInputLine();
            }
            $this->c++;
            break;
        }
    }
}