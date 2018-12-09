<?php

namespace moodle\clip\commands\semester;

use clip\command\Command;
use clip\command\Params;
use clip\command\WaitForInput;
use clip\Console;
use clip\Context;
use clip\PrintBuilder;

class InitCommand extends Command {

    public static function name() : string {
        return 'init';
    }

    public static function description() : string {
        return 'Initialize category structure for semester.';
    }

    public static function usage(PrintBuilder $builder) {
        // TODO: Implement usage() method.
    }

    public function execute(Params $params) : ?WaitForInput {

    }
}