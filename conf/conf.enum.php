<?php
/**
 * Definitions of enumerated fields
 *
 * WARNING: After creating model with FEnum field you *can't* add new values
 * to it. You'd have to remove all enum fields from models and then re-create
 * them (loosing all values). So think it through.
 * Or supply a patch fixing that. [;
 * @see FEnum
 * @author m.augustynowicz
 */

$conf['enum'] = array(

    'log_level' => array(
        'info',  // regular users can get access to that sort of things
        'warn',  // things that should not really happen
        'error', // things that cannot happen
    ),

);

