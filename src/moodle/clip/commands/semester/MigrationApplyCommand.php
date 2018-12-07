<?php

namespace moodle\clip\commands\semester;

use clip\command\Command;
use clip\command\Params;
use clip\command\WaitForInput;
use clip\Console;
use clip\Context;
use clip\PrintBuilder;
use local_cool\entity\course;
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
    public function execute(Console $console, ?Context $context, Params $params) : ?WaitForInput {
        /** @var SemesterPreperation $prep */
        $prep = $context->cacheGet('prep');
        $this->console = $console;
        $this->context = $context;

        if ($prep == null) {
            $console->printBuilder()->writeln('Migration was not prepared. Running now.')->send();
            $cmd = new MigrationPrepareCommand($this->console(),$this->context(),$params);
            $cmd->execute($console, $context, $params);
            $console->printBuilder()
                    ->writeln('Migration prepared. If you want to apply this migration type "migration-apply" again.')
                    ->sendInputLine();
            return null;
        }

        $console->printBuilder()->write('Migrate only last year semester? [yes/no]:')->send();
        return new WaitForInput([$this, 'migrateOnly']);
    }

    public function migrateOnly(Console $console, SemesterContext $context, Params $params) : ?WaitForInput {
        if (!in_array($params->get(0), ['yes', 'no'])) {
            $console->printBuilder()->deleteLastLine()->write('Migrate only last year semester? [yes/no]:')->send();
            return new WaitForInput([$this, 'migrateOnly']);
        }
        $this->migrateLastSemester = $params->get(0) == 'no';
        $console->printBuilder()->write('Are you sure? [yes/no]:')->send();
        return new WaitForInput([$this, 'confirm']);
    }

    public function confirm(Console $console, SemesterContext $context, Params $params) : ?WaitForInput {
        if (!in_array($params->get(0), ['yes', 'no'])) {
            $console->printBuilder()->deleteLastLine()->write('Are you sure? [yes/no]:')->send();
            return new WaitForInput([$this, 'migrateOnly']);
        }
        /** @var SemesterPreperation $prep */
        $prep = $context->cacheGet('prep');
        if ($params->get(0) == 'no') {
            return null;
        }

        $sem = $context->getSemester()->get_previous();
        $this->migrate = [];
        if ($this->migrateLastYear) {
            $this->migrate = array_merge($prep->readyToLoad[$sem->get_previous()->code], $this->migrate);
        }
        if ($this->migrateLastSemester) {
            $this->migrate = array_merge($prep->readyToLoad[$sem->code], $this->migrate);
        }
        $context->cacheAdd('prep', null);
        $console->printBuilder()->writeln('Starting migration for ' . count($this->migrate) . ' groups.')->send();
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
        $courseid = key($this->migrate);
        $course = end($this->migrate);
        unset($this->migrate[$courseid]);
        $this->console->printBuilder()->writeln('[' . $this->c . '] Migration from courseid(' . $courseid . ') courses(' .
                join(',', $course) . ')')->send();
        course_builder::create()->set_main_course($course[0])->set_semester($this->context->getSemester())->add_kos_courses($course)
                ->duplicate_from(course::get(['id' => $courseid]))->build();
        $this->console->printBuilder()->writeln('[' . $this->c . '] Migrated.')->send();
        if (count($this->migrate) > 0) {
            $this->console->executeFuture([$this, 'migrateCourse'], 0);
        } else {
            $this->console->printBuilder()->writeln('Migration complete.')->sendInputLine();
        }
    }
}