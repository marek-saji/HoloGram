<?php

$conf['locale'] = array(
    // see: http://en.wikipedia.org/wiki/IETF_language_tag
    'fallback'  => 'pl',
    // on *nix: find /usr/share/zoneinfo # (or similar)
    'time zone' => 'Europe/Warsaw',

    'date format' => array(
        'default' => 35, // see class.Functions.php for values

        /*
        'days offsets' => array( // will be translated
            0 => 'today',
            32 => '32 days ago',
        ),
         */
        // strftime() format
        //'human time' => 'H:M',
        //'sq' => DATE_ATOM
    ),

    // specify regexps of accepted formats, or leave empty for all
    // parseable by strtotime
    'accepted date formats' => array(
        // e.g. '/[0-9]{4}-[0-9]{2}-[0-9]{2}/',
    ),

);

