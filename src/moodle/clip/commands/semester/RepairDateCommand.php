<?php

namespace moodle\clip\commands\semester;

use clip\command\Command;
use clip\command\Params;
use clip\command\WaitForInput;
use clip\Console;
use clip\Context;
use clip\PrintBuilder;

class RepairDateCommand extends Command {

    public static function name() : string {
        return 'repair-date';
    }

    public static function description() : string {
        return 'Repair start and end dates in courses.';
    }

    public static function usage(PrintBuilder $builder) {
        // TODO: Implement usage() method.
    }

    public function execute(Params $params) : ?WaitForInput {
        global $DB;
        $this->console()->printBuilder()->writeln('Repairing course start and end dates...')->send();
        $instances = $this->context()->getSemester()->get_course_instances();
        foreach ($instances as $instance) {
            $course = $DB->get_record('course', ['id' => $instance->course_id]);
            if ($course !== false) {
                $course->startdate = $this->context()->getSemester()->start_date;
                $course->enddate = $this->context()->getSemester()->end_date;
                $DB->update_record('course', $course);
            }
        }
        $this->console()->printBuilder()->writeln('Course start and end dates repaired.')->sendInputLine();
        return null;
    }
}