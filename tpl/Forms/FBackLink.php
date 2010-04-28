<?php
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

