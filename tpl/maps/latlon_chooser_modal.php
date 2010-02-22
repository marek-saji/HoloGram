<?php
/**
 * (Google) Map widget for selecting location.
 * It embeds into given <div /> and prints hidden "latlon" field
 * (in js-debug-mode, it's not hidden.
 *
 * This is variation of a regular latlon_chooser, with bigger map
 * poping up in modal window (nyroModal). Selected location is
 * syncronized between those two.
 *
 * @todo make only one latlon_chooser, with param defining if we want
 *       a bigger map.
 * @todo and clean up the mess in the code.
 *       and naming of div's (i.e. div that is target for poping up the modal)
 *
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
 */

$this->inc('maps/init');

extract(array_merge(
        array(
            'latlon'        => '(0,0)',
            'title'         => '',
            'zoom'          => 7,
            'name'          => '',
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
$nf = (@$name) ? $name.'[%s]' : '%s';

// should we even try doing anything?
if (g()->debug->on('js','gmaps','disable'))
{
    $v->addOnLoad("$('#$id').html('here be map for choosing location for $nf. initial is: $latlon')");
    return;
}
?>

<input type="<?=
                g()->debug->on('js')?'text':
                'hidden'?>"
       name="<?=sprintf($nf,'latlon')?>"
       value="<?=$latlon?>" />

<?php

$v->addInlineJs( <<< JS
var gmap_{$id};
var gmap_{$id}_params = {};
var gmap_{$id}__big;
var gmap_{$id}__big_params = {};
JS
);

ob_start();
?>
    if (!GBrowserIsCompatible)
        console.log('Google Maps not preset!');
    else if (GBrowserIsCompatible())
    {
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
        var point = new GLatLng(<?=((double)$lat).','.((double)$lon) /* should default to 0,0 */?>);
        var zoom = <?=(double)$zoom /* should default to zero */?>;
        gmap_<?=$id?>.setCenter(point, zoom);
        <? if (! (0==$lat && 0==$lon)) : ?>
        hg('gmapsPlaceMarker')(gmap_<?=$id?>, '_group_', {
                point: point,
                title: '<?=$title?>'
            });
        <? endif; ?>
        GEvent.addListener(gmap_<?=$id?>, 'click', function(overlay, point){
            setOnlyMarkerOnTheMap.call(gmap_<?=$id?>,overlay,point);
            setOnlyMarkerOnTheMap.call(gmap_<?=$id?>__big,overlay,point);
        });

        // the big map
        
        $('#<?=$id?>').after('<p style="text-align:center"><a href="#<?=$id?>__big_modal">show bigger map</a></p>');
        $('#<?=$id?>').after('<div id="<?=$id?>__big"></div>');
<?php
$small_id = $id;
$id .= '__big';
?>
        /*
        $('#<?=$id?>').after('<div class="gmaps_location">selected location: <input id="<?=$id?>_location" onclick="return false" /><button>copy as my location</button></div>');
        */
        //$('#<?=$id?>').next().find('button').click(copyLocationToAddres);
        //$('#<?=$id?>').addClass('map');
        $('#<?=$id?>').css({height: '1000px'});
        $('#<?=$id?>').wrap('<div id="<?=$id?>_wrapper"></div>');
        $('#<?=$id?>').wrap('<div id="<?=$id?>_modal" style="display:none"></div>');
        $('a[href$="#<?=$id?>_modal"]').click(function(e){
            $(this).nyroModalManual({
                endShowContent: function(elts, settings){
                    // container has been resized
                    if (settings.endResize)
                        settings.endResize.apply(this, arguments);

                    // we shall center!
                    var latlon = $('input[name="<?=sprintf($nf,'latlon')?>"]').val();
                    latlon = /^\(?([0-9.-]+),([0-9.-]+)\)?$/.exec(latlon);
                    if (!latlon)
                        latlon = [0,0,0];
                    var point = new GLatLng(latlon[1],latlon[2]);
                    console.log('centering to',latlon,point);
                    gmap_<?=$id?>.setCenter(point);
                },
                endResize: function(elts, settings){
                    var height = $('#nyroModalContent').height();
                    var width  = $('#nyroModalContent').width();
                    var map = $('#<?=$id?>');
                    map.siblings().each(function(){
                        height -= $(this).height();
                    });
                    //if (map.height() > height)
                        map.height(height);
                    if (map.width() > width)
                        map.width(width-10);
                    gmap_<?=$id?>.checkResize();
                },
                endRemove: function() {
                    $('#<?=$id?>_modal_msg').text('The location has been chosen');
                }
            });
            return false;
        });
        $('#<?=$id?>_modal').append(
            '<div class="toolbar"><button class="nyroModalClose">done</button></div>'
        );

        gmap_<?=$id?> = new GMap2(document.getElementById('<?=$id?>'));
        var map = gmap_<?=$id?>;
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
        var point = new GLatLng(<?=((double)$lat).','.((double)$lon) /* should default to 0,0 */?>);
        var zoom = <?=(double)$zoom /* should default to zero */?>;
        gmap_<?=$id?>.setCenter(point, zoom);
        <? if (! (0==$lat && 0==$lon)) : ?>
        hg('gmapsPlaceMarker')(gmap_<?=$id?>, '_group_', {
                point: point,
                title: '<?=$title?>'
            });
        <? endif; ?>
        GEvent.addListener(gmap_<?=$id?>, 'click', function(overlay, point){
            setOnlyMarkerOnTheMap.call(gmap_<?=$small_id?>,overlay,point);
            setOnlyMarkerOnTheMap.call(gmap_<?=$id?>,overlay,point);
        });
        var addr    = $(':input[name="<?=sprintf($nf,'address')?>"]');
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
    }

/**
 */
function setOnlyMarkerOnTheMap (overlay, point)
{
    var map = this;
    console.log('setOnlyMarkerOnTheMap',map, point);
    if (null != overlay) // null -- mapa.
        return;
    map.clearOverlays();
    hg('gmapsPlaceMarker')(map, '_group_', {
            point: point,
            title: '<?=$title?>'
        }/*, geventMarkerChange*/);
    $('input[name="<?=sprintf($nf,'latlon')?>"]').val(
        '('+point.lat()+','+point.lng()+')' );
    map.panTo(point);
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
   var addr = gmap_<?=$id?>_params.addr;
   var country = gmap_<?=$id?>_params.country;
   var loc = addr ? addr.val() : '';
   if (country)
       loc += (loc?', ':'') + country.val();
   hg('gmapsGoto')(
            map,
            loc,
            function(point)
            {
                map = gmap_<?=$id?>;
                map.clearOverlays();
                hg('gmapsPlaceMarker')(map, '_group_', {point: point});
                $('input[name="<?=sprintf($nf,'latlon')?>"]').val(
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

<?php
$v->addOnLoad(ob_get_contents());
ob_end_clean();
?>

