<?php
if (!@$data)
{
    if (isset($this->__params['backto']))
        $data = $this->__params['backto'];
    else
        $data = g()->req->getReferer();
}

echo $f->tag('input', array(
        'type' => 'hidden',
        'name' => "${ident}[$input]",
        'value' => $data
    ));

