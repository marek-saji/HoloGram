<?php
/**
 * Hidden form field that contains link we should end at after
 * processing the form
 *
 * Automagically addded in {@see Component::_fixFormsFormat()}.
 * Using it's value is up to Controller's author
 * @author m.augustynowicz
 */

if (!@$data)
{
    if (isset($this->_params['backto']))
        $data = $this->_params['backto'];
    else
        $data = g()->req->getReferer();
}

echo $f->tag('input', array(
        'type' => 'hidden',
        'name' => "${ident}[$input]",
        'value' => $data
    ));

