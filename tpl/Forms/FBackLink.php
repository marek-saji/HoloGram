<?php
if (!@$data)
    $data = g()->req->getReferer();

printf('<input type="hidden" name="%s[%s]" value="%s" />',
       $ident, $input, $data );

