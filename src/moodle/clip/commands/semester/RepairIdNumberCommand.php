<?php

namespace moodle\clip\commands\semester;

use clip\command\Command;
use clip\command\Params;
use clip\command\WaitForInput;
use clip\Console;
use clip\Context;
use clip\PrintBuilder;
use local_kos\entity\semester;
use local_kos\helper\id_number_generator;
use moodle\clip\context\SemesterContext;

class RepairIdNumberCommand extends Command {

    public static function name() : string {
        return 'repair-idnumber';
    }

    public static function description() : string {
        return 'Repair course idnumbers in semester.';
    }

    public static function usage(PrintBuilder $builder) {
        // TODO: Implement usage() method.
    }

    public function execute(Params $params) : ?WaitForInput {
        global $DB;

        /** @var semester $semester */
        $semester=$this->context()->getSemester();
        $this->console()->printBuilder()->writeln('Repairing course idnumbers in semester '.$semester->code.'.')->send();

        $instances = $semester->get_course_instances();
        foreach ($instances as $instance) {
            $courses = $instance->get_kos_courses();
            $courses_out = '';
            foreach ($courses as $c) {
                $courses_out .= $c->code . ',';
            }
            $courses_out = substr($courses_out, 0, -1);
            $idnumber = id_number_generator::generate_course_instance($instance, $courses_out);
            $course = $DB->get_record('course', ['id' => $instance->course_id]);
            if ($course !== false) {
                $course->idnumber = $idnumber;
                $DB->update_record('course', $course);
            }
        }
        $this->console()->printBuilder()->writeln('Repaired.')->sendInputLine();
        return null;
    }
}