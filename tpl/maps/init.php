<?php
/**
 * Google Maps initialization
 * @author m.augustynowicz
 *
 * @uses TPL__GMAPS_INITIATED won't initiate if it's defined.
 * @uses conf[gmaps_api_key]
 *
 * (parameters passed as assigned local variables)
 * @param string $ver google maps api version
 * @param string $locale language to use (uses Lang::get() by default)
 *
 * @return boolean successfully initiated?
 */

if (defined('TPL__GMAPS_INITIATED'))
    return;

// should we even bother tring?
if (g()->debug->on('disable','gmaps'))
    return;

extract(array_merge(
        array(
            // stable:  2.s (e.g. 2.140)
            // current: 2   (e.g. 2.225)
            // latest:  2.x (e.g. 2.232)
            // see: http://code.google.com/p/gmaps-api-issues/wiki/JavascriptMapsAPIChangelog
            'ver'       => '2.x',
            'locale'    => g()->lang->get(),
        ),
        (array) $____local_variables
    ));

define('TPL__GMAPS_INITIATED',
       (bool) $gmaps_api_key = @g()->conf['gmaps_api_key'] );

if (!TPL__GMAPS_INITIATED)
{
    g()->debug->set(false, array('disable','gmaps')); // don't try, when there's no use.
    $v->addOnLoad('console.error("Invalid Google Maps API key")');
}
else
{
    $v->addJs('http://maps.google.com/maps?file=api&v='.$ver.'&sensor=true&hl='.$locale.'&key='.$gmaps_api_key);
    $v->addJs($this->file('maps/mapiconmaker_packed','js'));
    $v->addOnLoad('$(document).unload(function(){GUnload();});');
}

return TPL__GMAPS_INITIATED;

