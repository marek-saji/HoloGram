<?php
/**
 * Site Settings
 */

// site name.
$conf['site_name'] = 'Hologram App';

if(defined('ENVIRONMENT'))
{
    if(defined('LOCAL_ENV') && ENVIRONMENT == LOCAL_ENV)
        $conf['site_name'] .= ' (local)';
    else if(defined('DEV_ENV') && ENVIRONMENT == DEV_ENV)
        $conf['site_name'] .= ' (dev)';
}

$conf['alternative base URLs'] = array(
    'local' => 'http://hg-app.local/',
    //'dev'   => 'http://hg-app.dev.example.com/',
    //'test'  => 'http://hg-app.test.example.com/',
    //'pprod' => 'http://hg-app.pprod.example.com/',
    //'prod'  => 'http://hg-app.example.com/',
);

