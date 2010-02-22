<?php
/**
 * (Google) Map widget for showing some (e.g. one) point on the map.
 * It embeds into given <div />.
 * @author m.augustynowicz
 *
 * @uses maps/init template should be avaliable at this location
 *
 * (parameters passed as assigned variables)
 * @param string $id (required) id of the div to embed map to
 *        (shound have height i width, also "overflow: hidden" would be ok)
 * @param boolean $wheel_zoom enable zooming with mouse wheel scroll?
 * @param array $markers markers to be displayed
 *        'latlon' coordinates (format: "(X,Y)" or "X,Y")
 *        rest of parameters is passed to hg.gmapsPlaceMarker
 *        (title, tooltip, color, ..)
 * @param integer $zoom initial zoom (if more than one marker given,
 *        map will be zoomed to show them, and only them)
 */

$this->inc('maps/init');

extract(array_merge(
        array(
            'wheel_zoom'    => true,
            'zoom'          => sizeof($markers) ? 7 : 1,
            'markers'       => array(),
        ),
        (array) $____local_variables
    ));

if (!$id)
{
    if (g()->debug->on('js'))
        echo "<p>Google maps should be, well.. somewhere.. but no 'id' was passed to the themplate.</p>";
    return;
}
$id = $f->ASCIIfyText($id);

// should we even bother trying?
if (g()->debug->on('disable','gmaps'))
{
    $v->addOnLoad("$('#$id').html('here be map displaying location.')");
    return;
}


$v->addInlineJs("var gmap_$id;");


ob_start();
?>

/**
 * show some location(s) on the map, begin
 */
  if (!GBrowserIsCompatible)
      console.log('Google Maps not preset!');
  else if (GBrowserIsCompatible())
  {
    $('#<?=$id?>').addClass('map');
    gmap_<?=$id?> = new GMap2(document.getElementById('<?=$id?>'));
    var map = gmap_<?=$id?>;
    map.__hg = {};
    map.hg__id = '<?=$id?>';
    //map.setMapType(G_HYBRID_MAP);
    map.addControl(new GSmallMapControl());
    map.addControl(new GMapTypeControl());
    /*
    var mapMini = new GOverviewMapControl();
    map.addControl(mapMini);
    mapMini.hide(); // does not work in api 2.x
    */
    <?php if ($wheel_zoom) : ?>
    map.enableScrollWheelZoom();
    <?php endif; ?>
    // init.
    var point = new GLatLng(0,0);
    var marker_opts = {};
    var zoom = <?=$zoom?>;

    map.setCenter(point, zoom);
    /**
     * @todo zrobić wyśrodkowywanie do pointów tak jak w SIC
     *       i tak, żeby działało
     */
    <?php
    foreach ($markers as &$marker)
    {
        if (!isset($marker['latlon']) || !$marker['latlon'])
            //$latlon = '0,0';
            continue;
        else
            $latlon = trim($marker['latlon'],'()');
        @list($lat,$lon) = explode(',',$latlon);
        $latlon = sprintf('%F,%F', $lat, $lon);
        if (!isset($lat_max))
        {
            $lat_max = $lat_min = $lat;
            $lon_max = $lon_min = $lon;
        }
        $lat_min = min($lat_min,$lat);
        $lon_min = min($lon_min,$lon);
        $lat_max = max($lat_max,$lat);
        $lon_max = max($lon_max,$lon);
    ?>
    markerOpts = <?=json_encode($marker)?>;
    markerOpts.tooltip = '<?=@$marker['title']?>';
    markerOpts.point = point = new GLatLng(<?=$latlon?>);
    hg('gmapsPlaceMarker')(map, '_group_', markerOpts);
    <?php
    } // foreach $markers
    if (sizeof($markers)>1)
    {
        printf("zoom = map.getBoundsZoomLevel(new GLatLngBounds(new GLatLng(%f,%f), new GLatLng(%f,%f))) - 1;\n",
               $lat_min, $lon_min, $lat_max, $lat_max );
        printf("point = new GLatLng(%f,%f);\n",
               ($lat_min+$lat_max)/2.0, ($lon_min+$lon_max)/2.0 );
    }
    else if (@($lat==0 && $lon==0))
    {
        echo "\$('#$id').hide();";
    }
    ?>
    map.setCenter(point, zoom);
  }


<?php
$v->addOnLoad(ob_get_contents());
ob_end_clean();
?>

