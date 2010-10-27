<?php

// later gets overriden by Debug class
$conf['allow_debug'] = false;

$conf['first_controller'] = array(
    'name'=>'debug',
    'type'=>'Debug', // does cool things.
);
/*
$conf['first_controller'] = array(
    'name'=>'lib',
    'type'=>'Library',
);
 */
$conf['controllers']['debug']['sub'] = array (
    'name'=>'lib',
    'type'=>'Library',
);
$conf['controllers']['lib']['default'] = 'Hello';
$conf['controllers']['debug/lib'] = & $conf['controllers']['lib'];

// site name.
$conf['site_name'] = 'Hologram';
if (defined('ENVIRONMENT'))
{
    if (defined('LOCAL_ENV') && ENVIRONMENT == LOCAL_ENV)
        $conf['site_name'] .= '(local)';
    else if (defined('DEV_ENV') && ENVIRONMENT == DEV_ENV)
        $conf['site_name'] .= '(dev)';
}

// debugs to turn on when enabling "fav"
$conf['favorite debugs'] = 'db, js, mails, view, debug, user';

// app-specific class overrides
$conf['classes_override'] = array(
    // Use adoDB
    //'NonConnectedDb' => 'NonConnectedDbWhichIsNotReallyNonConnectedAndActuallyIsADODbProxy',
    // will use only one language
    //'Lang'           => 'LangOne',
);

// whether to use translations stored in database, in addition
// to those in config files
$conf['use_db_trans'] = false;

