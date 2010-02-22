<?php
/**
 * <textarea></textarea> with WYSIWYG editing
 * @author m.augustynowicz
 *
 * (params passed as local variables) 
 * same as FString_area
 * @param null|boolean $use_loader Use Xinha's splash screen? null for xinha/init.php default
 */

extract(array_merge(
        array(
            'use_loader'  => null,
        ),
        (array) $____local_variables
    ));

$xinha_parms = array();
if (null !== $use_loader)
    $xinha_parms['use_loader'] = $use_loader;

$t->inc('xinha/init', $xinha_parms);

if (!@$____local_variables['disabled'])
    @$____local_variables['class'] .= ' richedit';

$t->inc('Forms/FString_area', $____local_variables);

