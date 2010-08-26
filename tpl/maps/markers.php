<?php
/**
 * (Google) Map widget showing markers returned by an AJAX controller.
 * It should be placed inside a search form.
 * @author m.augustynowicz
 *
 * @uses maps/init template should be available at this location
 *
 * (parameters passed as assigned variables)
 * @param string $id (required) id of the div to embed map to
 *        (shound have height i width, also "overflow: hidden" would be ok)
 * @param string $ctrl (required) AJAX controller name
 * @param int $clusters_id id of clusters group
 * @param float $latlon initial latitude and longitude ('(0,0)' format)
 * @param integer $zoom initial zoom level
 */

$this->inc('maps/init');

extract(array_merge(
        array(
            'id'            => null,
            'ctrl'          => null,
            'clusters_id'   => 1,
            'latlon'        => '(0,0)',
            'zoom'          => 1,
        ),
        (array) $____local_variables
    ));

if (!$id)
{
    if (g()->debug('js'))
        echo "<p>Google maps should be, well.. somewhere.. but no 'id' was passed to the themplate.</p>";
    return;
}
if (!$ctrl)
{
    if (g()->debug('js'))
        echo "<p>Google maps should be, well.. somewhere.. but no 'ctrl' was passed to the themplate.</p>";
    return;
}

/**
 * Default values
 */
$latlon = trim($latlon,'()');
list($lat,$lon) = explode(',',$latlon);
$latlon = sprintf('%f,%f', $lat, $lon);

// should we even bother trying?
if (g()->debug->on('js','gmaps','disable'))
{
    $v->addOnLoad("$('#$id').html('here be map. doing magic tricks. (currently not.)')");
    return;
}

$v->addInlineJs("var gmap_$id;");
?>

<input type="hidden"
       name="clusters_id"
       id="gmap_<?=$id?>_clusters_id"
       value="<?=$clusters_id?>" />

<?php
ob_start();
?>
if (!GBrowserIsCompatible)
    console.error('Google Maps not preset!');
else if (GBrowserIsCompatible())
{
    $('#<?=$id?>').addClass('map');
    gmap_<?=$id?> = new GMap2(document.getElementById('<?=$id?>'));
    var map = gmap_<?=$id?>;
    map.__hg = {};
    map.hg__id = '<?=$id?>';
    //map.setMapType(G_HYBRID_MAP);
    map.addControl(new GLargeMapControl());
    map.addControl(new GMapTypeControl());
    var mapMini = new GOverviewMapControl();
    map.addControl(mapMini);
    map.enableScrollWheelZoom();

    var point = new GLatLng(<?=$lat.','.$lon?>);
    var zoom = <?=$zoom?>;
    map.setCenter(point, zoom);
    map.hg__msgs = $('#<?=$id?>_messages');
    map.hg__markers_ctrl = '<?=$ctrl?>';
    map.hg__markers_url = '<?=BASE.$ctrl?>/getClusters';
    map.hg__markers_form = $('#gmap_<?=$id?>_clusters_id').closest('form');
    hg('gmapsPlaceMarkers')(map);
    map.hg__markers_form.find(':input').bind('keyup mouseup change', function (){
            hg('gmapsDelayedEventPlaceMarkers')(gmap_<?=$id?>);
        });
    GEvent.addListener(map, "moveend", hg('gmapsDelayedEventPlaceMarkers'));
    GEvent.addListener(map, "zoomend", hg('gmapsDelayedEventPlaceMarkers'));
}
<?php
$v->addOnLoad(ob_get_contents());
ob_end_clean();

