<?php

namespace moodle\clip\commands\semester;

use local_kos\entity\semester;

class SemesterPreperation {
    public $missing = [];
    public $allSmesterCourses = [];
    public $readyToLoad = [];
    public $inSemester = [];
    public $extraInSemester = [];
    public $inCourseware = [];
    public $groupings = [];

    private $semester;

    public function __construct(semester $semester) {
        $this->semester = $semester;
    }

    public function loadExtra() {
        $partial = $this->semester->get_course_instances();
        foreach ($partial as $instance) {
            foreach ($instance->get_kos_courses() as $crs) {
                $this->extraInSemester[$crs->code] =
                        ['url' => $instance->get_url(), 'code' => $crs->code, 'name' => $crs->get_name()];
            }
        }
    }

    public function loadCourseList() {
        $kos_context = new \local_kos\kos_context();
        $crs = \local_kos\api\kosapi\entities\course::fetchCourses($kos_context, null, $this->semester->code);
        foreach ($crs as $c) {
            $this->allSmesterCourses[$c->code()] = $c;
            if (isset($this->extraInSemester[$c->code()])) {
                $this->inSemester[$c->code()] = $this->extraInSemester[$c->code()];
                unset($this->extraInSemester[$c->code()]);
            } else {
                $this->missing[$c->code()] = $c;
            }
        }
    }

    public function findCoursesInSemester(semester $semester) {
        $this->readyToLoad[$semester->code] = [];
        foreach ($semester->get_course_instances() as $crs_in) {
            foreach ($crs_in->get_kos_courses() as $crs) {
                if (isset($this->missing[$crs->code])) {
                    $this->readyToLoad[$semester->code] =
                            ['url' => $crs_in->get_url(), 'code' => $crs->code, 'name' => $crs->get_name(),
                                    'id' => $crs_in->course_id];
                    unset($this->missing[$crs->code]);
                }
            }
        }
    }

    public function groupCourses(semester $semester) {
        $this->groupings[$semester->code] = [];
        foreach ($this->readyToLoad[$semester->code] as $course) {
            if (!isset($this->groupings[$semester->code][$course['id']])) {
                $this->groupings[$semester->code][$course['id']] = [$course['code']];
            } else {
                $this->groupings[$semester->code][$course['id']][] = $course['code'];
            }
        }
    }
}