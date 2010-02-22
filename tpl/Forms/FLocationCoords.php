<?php
/**
 * @author m.augustynowicz
 * @uses tpl/maps
 *
 * (parameters passed as local variables)
 * @param boolean $print_div shall div#$id be printed here?
 */
extract(array_merge(
        array(
            'print_div'     => true,
        ),
        (array) $____local_variables
    ));

if (g()->debug->on('js','gmaps'))
    $____local_variables['attrs']['type'] = 'text';
else
    $____local_variables['attrs']['type'] = 'hidden';

$____local_variables['input_id'] = $id = $f->ASCIIfyText($ident.'['.$input.']');
$id = $id.'__map';
$____local_variables['id'] = $id;
$____local_variables['latlon'] = $____local_variables['data'];

if ($print_div)
    printf('<div id="%s"></div>', $id);

$attrs = $t->inc('maps/latlon_chooser', $____local_variables);
if (g()->debug->on('js','gmaps'))
    print '<small>(this is a hidden field -- visible only in debug)</small>';

return array_merge($attrs, array(
        'gmap_id' => $id,
    ));

