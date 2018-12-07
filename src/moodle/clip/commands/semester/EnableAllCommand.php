<?php

namespace moodle\clip\commands\semester;

use clip\command\Command;
use clip\command\Params;
use clip\command\WaitForInput;
use clip\Console;
use clip\Context;
use clip\PrintBuilder;
use moodle\clip\context\SemesterContext;

class EnableAllCommand extends Command {

    public static function name() : string {
        return 'enable-all';
    }

    public static function description() : string {
        return 'Enable all courses.';
    }

    public static function usage(PrintBuilder $builder) {
        // TODO: Implement usage() method.
    }

    public function execute(Console $console, ?Context $context, Params $params) : ?WaitForInput {
        global $DB;
        $console->printBuilder()->write('This will make all courses visible to all students. Are you sure? [yes/no]')->send();
        return new WaitForInput([$this,'confirm']);
    }
    public function confirm(Console $console, SemesterContext $context, Params $params) : ?WaitForInput {
        $semester=$context->getSemester();
        $console->printBuilder()->writeln('Enabling all courses');
        $instances = $semester->get_course_instances();
        foreach ($instances as $instance) {
            $course = $DB->get_record('course', ['id' => $instance->course_id]);
            $course->visible = 1;
            $DB->update_record('course', $course);
        }
    }
}