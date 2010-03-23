<?php
/**
 * (Google) Map widget
 *
 * Be extremely carefull when adding new parameters here -- most of them
 * are passed to JavaScript! Escape!
 * @author m.augustynowicz
 *
 * @uses maps/init template should be avaliable at this location
 *
 * (parameters passed as assigned variables)
 * @param string $id id of the div to embed map to, if none given,
 *        it is being generated (will have class="map") and inserted into DOM
 *        (shound have height i width, also "overflow: hidden" would be nice)
 * @param string|array $latlon initial latitude and longitude
 *        ('(0.1,0.1)' format or array)
 * @param integer $zoom initial zoom level
 * @param string $type default display type (see validation for possible values)
 * @param array $markers permanent markers ([latlon] exploded, rest of
 *        keys passed to hg.gmapPlaceMarker()
 * @param array $controls controls to use with the map
 *        http://code.google.com/intl/pl/apis/maps/documentation/reference.html#GControl
 * @param boolean $wheel_zoom enable zooming with mouse wheel scroll?
 * @param boolean $fetch whether markers should be fetched at all
 * @param null|string $form id to use with this map, when no given,
 *        map's closes form parent will be used {@see gmapsSetup()} in hg.gmaps.js
 * @param boolean $update_on_change launch onchange event when map changes
 *        (pan or move)
 * @param null|string $update_url address to use, when no form is avilable,
 *        or where to send form data, if $form != null
 * @param integer $update_delay delay to use when updating on move/zoom event
 * @param null|array $map_events hg function names to bind as map events,
 *        event names in keys (e.g. array('click'=>'fooMapOnClick'));
 *        functions have to be avaliable through hg();
 *        in addition to Google's event there are special ones:
 *        beforechange, change and afterchange with params: map, event_type, 
 * @param null|array $marker_events hg function names to bind as marker events,
 *        event names in keys (e.g. array('click'=>'fooMarkerOnClick'));
 *        functions have to be avaliable through hg();
 *
 * @return string|boolean false on error, js variable name with gmap on success.
 */
extract(array_merge(
        array(
            'id'            => null,
            'latlon'        => '(0,0)',
            'zoom'          => 1,
            'type'          => 'hybrid', 
            'markers'       => array(),
            'controls'      => array('LargeMap','MapType','OverviewMap'),
            'wheel_zoom'    => true,
            'fetch'         => true,
            'bind_form'     => false,
            'form'          => null,
            'update_on_move' => false, // pan or move
            'update_url'    => null,
            'update_delay'  => 1000,
            'map_events'    => array(),
            'marker_events' => array(),
        ),
        (array) $____local_variables
    ));

if (!$this->inc('maps/init'))
    return false;

/* id */
if ($id)
    $id = $f->ASCIIfyText($id); // be careful, will be used in js
else // generate id and create div
{
    $id = 'map_'.$f->generateSimpleKey(4);
    if (g()->debug->on('js'))
        $v->addOnLoad("console.info('Generated map id: $id')");
    printf('<div class="%1$s_wrapper"><div id="%1$s"></div></div>', $id);
}

/* lat and lon */
if (!is_array($latlon))
{
    $latlon = trim($latlon, '()');
    $latlon = split(',',$latlon);
}
if (!isset($latlon[0]) || !isset($latlon[1]))
{
    trigger_error('Incorrect $latlon parameter passed to tpl/maps/map: '.join(',',$latlon), E_USER_NOTICE);
    return false;
}
else
{
    $lat = & $latlon[0];
    $lon = & $latlon[1];
}

/* zoom */
if (!$f->isInt($zoom))
    $zoom = 1;

/* type */
$type = strtoupper($type);
// http://code.google.com/intl/pl/apis/maps/documentation/reference.html#GMapType
$allowable_types = array('NORMAL'=>1, 'SATELLITE'=>1, 'AERIAL'=>1, 'HYBRID'=>1, 'AERIAL_HYBRID'=>1, 'PHYSICAL'=>1);
if (!isset($allowable_types[$type]))
    $type = 'HYBRID';
$type = sprintf('G_%s_MAP', $type);

/* controls */
foreach ($controls as &$c) // just to make sure it's objects or sth like that
    $c = sprintf('G%sControl', $c);

/* wheel_scroll */
$wheel_zoom = (bool) $wheel_zoom;

/* do fetch? */
$fetch = (bool) $fetch;

/* permanent markers */
$markers = (array) $markers;
foreach ($markers as &$m)
{
    if (isset($m['latlon']))
    {
        list($m['lat'],$m['lng']) = explode(',',trim($m['latlon'],'()'));
        unset($m['latlon']);
        $m['lat'] = $f->floatVal($m['lat'], true);
        $m['lng'] = $f->floatVal($m['lng'], true);
    }
}
unset($m);

/* form and update */
$bind_form = (bool) $bind_form;
$form = (string) $form;
$update_on_move = (bool) $update_on_move;
$update_url = (string) $update_url;

/* javascript callbacks */
if (!$map_events)
    $map_events = array();
else
{
    $map_events = (array) $map_events;
    foreach ($map_events as &$name)
        $name = (string) $name;
}
if (!$marker_events)
    $marker_events = array();
else
{
    $marker_events = (array) $marker_events;
    foreach ($marker_events as &$name)
        $name = (string) $name;
}

/* translations */
$msg_loading = $t->trans('loading...');

$params = json_encode(compact(
        'lat', 'lon', 'zoom', 'type', 'controls', 'wheel_zoom',
        'markers', 'fetch',
        'bind_form', 'form', 'update_on_move', 'update_url',
        'map_events', 'marker_events', 'msg_loading'
    ));
$v->addOnLoad("hg('gmapsSetup')('$id', $params)");

return $id;

