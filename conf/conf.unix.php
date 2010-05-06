<?php
/**
 * unix tools
 *
 * each of conf[unix][COMMAND] can be:
 * boolean -- just determine if COMMAND is available;
 * array -- it's available, you can set additional params:
 *      [path] -- path to use
 *      [args] -- additional params (remember to escapeshellarg()!)
 */

// file(1)
$conf['unix']['file'] = true;

