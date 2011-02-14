<?php
/* WARNING: keys (controllers' names) get lowercased! */

$conf['translations'] = array(
    
'_global' => array(

    'welcome, %s' => 'witaj, %s',
    'create account' => 'załóż konto',

    // for human-readable dates
    '%s at %s' => '%s o %s',
    'two days ago' => 'przedwczoraj',
    'yesterday' => 'wczoraj',
    'today' => 'dzisiaj',
    'tomorrow' => 'jutro',
    'day after tomorrow' => 'pojutrze',

), // _global


'log' => array(

    'anonymous' => 'niezalogowany',

    '((enum log_level: %s))' => array(
        'info' => 'informacja',
        'warn' => 'ostrzeżenie',
        'error' => 'błąd',
        null => '%s'
    ),

    'by %s at %s' => 'przez %s, %s',

    // context name
    '((context: %s))' => array(
        'User' => 'użytkownicy',

        null => '%s'
    ),

    // event name: context/action on target
    '((event: %s/%s on %s))' => array(
            // events on user
            "User" => 'nieznana wydarzenie <em>%2$s</em> na użytkowniku: %3$s',
            "User\tlogin" => 'zalogowanie się',
            "User\tlogout" => 'wylogowanie się',
            "User\tactivate" => 'aktywowanie konta',
            "User\tadd" => 'stworzenie konta',
            "User\tresetpassword" => 'resetowanie hasła',
            "User\tlostpassword" => 'funkcja "nie pamiętam hasła"',

            // default event message
            null => 'nieznane wydarzenie <em>%s/%s</em><div>%s</div>'
        ),

), // log

);

