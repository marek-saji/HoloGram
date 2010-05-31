<?php
/**
 * Technical settings
 */

// even consider the idea of debug mode being enabled
$conf['allow_debug'] = true;

$conf['controllers']['lib']['default'] = 'Main';
$conf['controllers']['debug']['sub'] = array (
    'name'=>'lib',
    'type'=>'Library',
);
$conf['controllers']['debug/lib'] = & $conf['controllers']['lib'];

// it's not obligatory, but let's show debug toolbar, when deebug is enabled
if ($conf['allow_debug'])
{
    // be awesome (debug toolbar etc)
    $conf['first_controller'] = array(
        'name'=>'debug',
        'type'=>'Debug', // does cool things.
    );
}
else
{
    // be less awesome
    $conf['first_controller'] = & $conf['controllers']['debug']['sub'];
}

// controllers loaded on every request
$conf['permanent_controllers'] = array(
);

// override hg classes with app implementations
$conf['classes_override'] = array(
);

