<?php
/**
 * Hidden form field that contains form's render timestamp
 *
 * Automagically addded in {@see Component::_fixFormsFormat()}.
 * Using it's value is up to Controller's author
 * @author m.augustynowicz
 */

$data = time();

echo $f->tag('input', array(
        'type' => 'hidden',
        'name' => "${ident}[$input]",
        'value' => $data
    ));

