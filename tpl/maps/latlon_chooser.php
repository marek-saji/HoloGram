<?php
/**
 * (Google) Map widget for selecting location.
 * It embeds into given <div /> and prints hidden "latlon" field
 * @author m.augustynowicz
 *
 * @uses maps/init template should be avaliable at this location
 *
 * (parameters passed as assigned variables)
 * @param string $id (required) id of the div to embed map to
 *        (should have height i width, also "overflow: hidden" would be ok)
 * @param string $latlon initial coordinates (format: "(X,Y)" or "X,Y")
 * @param string $title markers tooltip
 * @param integer $zoom initial zoom level (will zoom out if no latlon given)
 * @param string $name latlon field's suffix (empty by default), e.g. "Users"
 * @param array $addr_ids ids of form inputs with address parts to sync to the map
 * @param string $addr_context string to append when searching for the addres (i.e.'poland')
 */

$this->inc('maps/init');

extract(array_merge(
        array(
            'latlon'        => '(0,0)',
            'title'         => '',
            'zoom'          => 7,
            'name'          => '',
            'addr_ids'		=> array(),
            'addr_context'  => '',
        ),
        (array) $____local_variables
    ));

if (!$id)
{
    if (g()->conf['allow_debug'])
        echo "<p>Google maps should be, well.. somewhere.. but no 'id' was passed to the themplate.</p>";
    return;
}

$latlon = trim($latlon,'()');
@list($lat,$lon) = explode(',',$latlon);
$latlon = sprintf('%f,%f', $lat, $lon);
if (0 == $lat && 0 == $lon)
    $zoom = 1;

// name format
$nf = $name ? $name.'[%s]' : '%s';

if (g()->debug->on('disable','gmaps'))
{
    $v->addOnLoad("$('#$id').html('here be map for choosing location for $nf. initial is: $latlon')");
    return;
}

$input_vars = $____local_variables;
$input_vars['id'] = & $input_vars['input_id'];
$ret = $t->inc('Forms/input', $input_vars);
$coord_name = $ident.'['.$input.']';

$v->addInlineJs( <<< JS
var gmap_{$id};
var gmap_{$id}_params = {};
JS
);

ob_start();
?>
(function(){ // gmap scope

    if (!GBrowserIsCompatible)
        console.log('Google Maps not preset!');
    else if (GBrowserIsCompatible())
    {
        if (!$('#<?=$id?>').length)
        {
            console.error('gmap container (#<?=$id?>) not fount!');
            return;
        }
        /*
        $('#<?=$id?>').after('<div class="gmaps_location">selected location: <input id="<?=$id?>_location" onclick="return false" /><button>copy as my location</button></div>');
        */
        $('#<?=$id?>').next().find('button').click(copyLocationToAddres);
        $('#<?=$id?>').addClass('map');
        gmap_<?=$id?> = new GMap2(document.getElementById('<?=$id?>'));
        var map = gmap_<?=$id?>;
        map.__hg = {};
        map.hg__id = '<?=$id?>';
        //gmap_<?=$id?>.setMapType(G_HYBRID_MAP);
        map.addControl(new GSmallMapControl());
        map.addControl(new GMapTypeControl());
        /*
        var mapMini = new GOverviewMapControl();
        gmap_<?=$id?>.addControl(mapMini);
        mapMini.hide(); // does not work in api 2.x
        */
        gmap_<?=$id?>.enableScrollWheelZoom();
        // init.
        var point = new GLatLng(<?php printf('%F,%F',$lat,$lon) /* should default to 0,0 */?>);
        var zoom = <?=(double)$zoom /* should default to zero */?>;
        gmap_<?=$id?>.setCenter(point, zoom);
        <? if (! (0==$lat && 0==$lon)) : ?>
        hg('gmapsPlaceMarker')(gmap_<?=$id?>, '_group_', {
                point: point,
                title: '<?=$title?>'
            });
        <? endif; ?>
        GEvent.addListener(gmap_<?=$id?>, 'click', function(overlay, point){
            if (null != overlay) // null -- mapa.
                return;
            gmap_<?=$id?>.clearOverlays();
            hg('gmapsPlaceMarker')(gmap_<?=$id?>, '_group_', {
                    point: point,
                    title: '<?=$title?>'
                }/*, geventMarkerChange*/);
            var input = $('input[name="<?=$coord_name?>"]');
            input.val( '('+point.lat()+','+point.lng()+')' );
            input.change();
            gmap_<?=$id?>.panTo(point);
            gmap_<?=$id?>.hg__manually_set = true;
        });
        map.hg__addrs = <?=(!$addr_ids) ? '[]' : ("$('#".join(', #',$addr_ids)."')")?>;
        map.hg__addr_suffix = '<?=$addr_context ? ", $addr_context" : ''?>'
        $(map.hg__addrs).each(function(){
            var me = $(this);
            if (me.is('input, textarea'))
                me.keyup(delayedEventAddrChange);
            else
                me.change(eventAddrChange);
        });
        if (!$('input[name="<?=$coord_name?>"]').val())
            eventAddrChange();
        <?php /*
        if ('<?=$addr_id?>')
        	addr    = $('#<?=$addr_id?>'); //$(':input[name="<?=sprintf($nf,'address')?>"]');
        var country = $('select[name="<?=sprintf($nf,'country')?>"]');
        if (addr && !addr.length)
            addr = null;
        if (!country || !country.length)
            country = null;
        gmap_<?=$id?>_params.addr = addr;
        gmap_<?=$id?>_params.country = country;
        if (addr)
            addr.keyup(delayedEventAddrChange);
        if (country)
            country.change(eventAddrChange);
         */ ?>
    }

/**
 * Call event function after some time afer
 * last event occured.
 */
function delayedEventAddrChange()
{
   if (this.showOnMapTimeout)
       clearTimeout(this.showOnMapTimeout);
   this.showOnMapTimeout = setTimeout(function(){
           eventAddrChange.apply(this, arguments);
       }, 1000);
}

/**
 * Place marker accordingly to the address.
 */
function eventAddrChange()
{
   var map = gmap_<?=$id?>;
/*
   var addr = gmap_<?=$id?>_params.addr;
   var country = gmap_<?=$id?>_params.country;
   var loc = addr ? addr.val() : '';
   if (country)
       loc += (loc?', ':'') + country.val();
*/
   if (map.hg__manually_set)
     return;
   var locs = [];
   $.each(map.hg__addrs, function(){
     var me = $(this);
     var val = me.val();
     if (me.is('select'))
        val = me.find('[value="'+val+'"]').text();
     if (val)
         locs.push(val);
   });
   var loc = locs.join(', ') + map.hg__addr_suffix;
   console.log(loc);
   hg('gmapsGoto')(
            map,
            loc,
            function(point)
            {
                console.log(loc,'is in',point);
                map = gmap_<?=$id?>;
                map.clearOverlays();
                hg('gmapsPlaceMarker')(map, '_group_', {point: point});
                $('input[name="<?=$coord_name?>"]').val(
                    point.lat()+','+point.lng() );
            }
        );
}

/*
function geventMarkerChange(marker)
{
   hg('gmapsGetLocation')(
           marker.getLatLng(),
           function(loc)
           {
               if (loc.Status.code != '200')
                   return false;
               var addr = loc.Placemark[2].address; // FIXME
               $('#<?=$id?>_location').val(addr);
           }
       );
}
*/

/**
 * Copy map location to address
 * doesn't work really good with countries
 */
function copyLocationToAddres(addr)
{
   if (typeof addr != 'string')
       addr = $(this).parent().children('input').val();
   var map_params = gmap_<?=$id?>_params;
   if (!addr)
       return false;
   if (country = map_params.country)
   {
       addr_a = /(.+)\s*,\s*([^,]+)$/.exec(addr);
       if (addr_a)
       {
           addr_a[2] = addr_a[2].toUpperCase();
           switch (addr_a[2]) // kinda sux.
           {
               case 'UK' :
                   addr_a[2] = 'GB';
               case 'USA' :
                   addr_a[2] = 'US';
               break;
           }
           if (country.find('option[value="'+addr_a[2]+'"]'))
           {
               map_params.country.val(addr_a[2]);
           }
           else
           {
               country.find('option').attr('selected', null); //?
               country.find('option:contains("'+addr_a[2]+'")').attr('selected', 'selected');
           }
           addr = addr_a[1];
       }
   }
   map_params.addr.val(addr);
   return false;
}

})(); // gmap scope

<?php
$v->addOnLoad(ob_get_contents());
ob_end_clean();

return $ret
?>
