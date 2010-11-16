<?php

$conf['keys'] = array(

    // reCAPTCHA (domain-specific)
    // https://www.google.com/recaptcha/admin/create
    'recaptcha' => array(
        'public'  => null,
        'private' => null,
    ),

    // Google Analytics (domain-specific)
    // http://google.com/analytics/
    'google analytics' =>
            (ENVIRONMENT == PROD_ENV) ?
            null : // production. you probably want to set this
            false,

    // Google Maps (domain-specific, set this in local conf)
    // http://code.google.com/apis/maps/signup.html
    'google maps' => null,

);

