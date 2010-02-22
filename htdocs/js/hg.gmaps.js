/**
 * Handling Google Maps.
 * @author m.augustynowicz
 */


/**
 * Shining new function that initializes single map.
 * @uses body[class=js]
 *
 * @param string id
 * @param Object map_params see declaration of params
 */
hg['gmapsSetup'].f = function(id, map_params)
{
    if (!$('body').is('.js'))
    {
        $(function(){hg('gmapsSetup')(map_params);});
        return null;
    }

    var map_div = $('#'+id)
            .addClass('map')
            .css({'min-width':'7em','min-height':'7em'});
    if (!map_div.length)
    {
        console.error('Map div is missing!');
        return false;
    }

    var params = {
        zoom : 1,
        lat  : 0,
        lon  : 0,
        type : 'G_HYBRID_MAP',
        markers : {},
        fetch : true,
        bind_form : false,
        update_on_move : false,
        update_url : null,
        update_delay : 1000,
        map_events : {},
        marker_events : {},
        msg_loading : 'loading..'
    };
    $.extend(params, map_params);

    // so many things may go wrong here.. let's check for all of them

    if (!id)
    {
        console.error('No map ID passed to gmapsSetup');
        return false;
    }
    
    if (!GBrowserIsCompatible)
    {
        console.error('Google Maps not preset!');
        return false;
    }

    if (!GBrowserIsCompatible())
    {
        map_div.append('<p>This browser is not Google Maps compatible!</p>');
        console.error('Browser is not Google Maps compatible!');
        return false;
    }

    var map = new GMap2(map_div[0]);
    map.__hg = {
        'id'  : id,
        'div' : map_div,
        'params' : params
    };
    map_div.data('hg__gmap', map);

    try
    {
        eval('map.setMapType('+params.type+')');
    }
    catch(e)
    {
        console.error('Incorrect Google Maps type:',params.type,', falling back to hybrid');
        map.setMapType(G_HYBRID_MAP);
    }


    $.each(params.controls, function(){
        try
        {
            eval('map.addControl(new '+this+'());');
        }
        catch(e)
        {
            console.error('Failed to add control:',this);
        }
        if (this == 'GLargeMapControl')
            map_div.addClass('large');
        if (this == 'GSmallMapControl')
            map_div.addClass('small');
    });

    if (params.wheel_zoom)
        map.enableScrollWheelZoom();
    else
        map.disableScrollWheelZoom();

    map.__hg.permanentMarkers = params.markers;
    // when no lat and lon given, determine it from markers
    if (params.markers && !(~~params.lat && ~~params.lon))
    {
        var bounds = {N:null, E:null, W:null, S:null};
        $.each(params.markers, function(){
            if (null===bounds.N || this.lat > bounds.N)
                bounds.N = this.lat;
            if (null===bounds.S || this.lat < bounds.S)
                bounds.S = this.lat;
            if (null===bounds.W || this.lng > bounds.W)
                bounds.W = this.lng;
            if (null===bounds.E || this.lng < bounds.E)
                bounds.E = this.lng;
        });
        if (bounds.N == bounds.S || bounds.W == bounds.E) // all in the same place
        {
            params.lat = bounds.N;
            params.lon = bounds.W;
            if (!params.zoom)
                params.zoom = 10;
        }
        else // in different locations
        {
            params.lat = (bounds.N-3*bounds.S)/2;
            params.lon = (bounds.W-3*bounds.E)/2;
            // same thing with zoom level
            if (!params.zoom)
            {
                bounds = new GLatLngBounds(new GLatLng(bounds.N,bounds.E),
                                           new GLatLng(bounds.S,bounds.W) );
                params.zoom = map.getBoundsZoomLevel(bounds);
            }
        }
    }

    map.setCenter(new GLatLng(params.lat, params.lon),
                  params.zoom );

    /**
     * Add message control
     */
    function MsgControl(loading_msg){
        if (!loading_msg)
            loading_msg = 'loading..';
        this._loading_msg = '<span class="loading">'+loading_msg+'</span>';
    };
    MsgControl.prototype = new GControl();
    MsgControl.prototype.set = function(val){
        if (val)
            this._div.html(val).show();
        else
            this._div.hide().html('');
    };
    MsgControl.prototype.loading = function(){
        this.set(this._loading_msg);
    };
    MsgControl.prototype.hide = function(){this._div.hide();};
    MsgControl.prototype.show = function(){this._div.show();};
    MsgControl.prototype.printable = function(){return true;};
    MsgControl.prototype.selectable = function(){return false;};
    MsgControl.prototype.initialize = function(map){
        this._map = map;
        this._div = $('<div />', {
            id : map.__hg.id+'__messages',
            'class' : 'map_messages',
            css : {
                color: 'white',
                padding: '1ex'
            }
        }).hide();
        map.getContainer().appendChild(this._div[0]);
        return this._div[0];
    };
    MsgControl.prototype.getDefaultPosition = function(){
        if (this._map.__hg.div.hasClass('small'))
            var size = new GSize(55, 30);
        else
            var size = new GSize(90, 30);
        return new GControlPosition(G_ANCHOR_TOP_LEFT, size);
    };
    map.__hg.msg = new MsgControl();
    map.addControl(map.__hg.msg);

    map.__hg.mapEvents = params.map_events;
    map.__hg.markerEvents = params.marker_events;

    if ($.isPlainObject(map.__hg.mapEvents || null))
    {
        console.debug('binding map events:',map.__hg.mapEvents);
        $.each(map.__hg.mapEvents, function(name,callback){
            if (typeof callback != 'function')
            {
                var hg_name = callback;
                callback = function(){
                    if (this instanceof GMarker)
                        return;
                    return hg(hg_name).apply(this, arguments);
                };
            }
            GEvent.addListener(map, name, callback);
        });
    }

    map.__hg.fetch = params.fetch;

    if (map.__hg.permanentMarkers)
    {
        $.each(map.__hg.permanentMarkers, function(){
            hg('gmapsPlaceMarker')(map, '_permanent_markers', this, params.markerEvents);
        });
    }

    map.__hg.update_url = params.update_url;

    map.__hg.update_delay = params.update_delay;

    map.__hg.form = null;
    if (params.bind_form)
    {
        var form = null;
        if (!params.form)
        {
            form = map_div.closest('form');
            if (!form.length)
                console.info('Map has no form parents');
        }
        else
        {
            form = $(params.form);
            if (!form.length)
            {
                form = $('#'+params.form);
                if (!form.length)
                    console.error('Incorrect form specified:',params.form);
            }
        }
        map.__hg.form = form;
        if (form.length)
        {
            form.find('.hg:input').bind('keyup mouseup change', function(event){
                hg('gmapsDelayedUpdate')(map, event.type);
            });
        }
    } // if bind_form

    if (params.update_on_move)
    {
        GEvent.addListener(map, 'moveend', function(){
            hg('gmapsDelayedUpdate')(this, 'move');
        });
        GEvent.addListener(map, 'zoomend', function(){
            hg('gmapsDelayedUpdate')(this, 'zoom');
        });
        GEvent.addListener(map, 'movestart', function(){
            if (this.__hg._update_timeout)
                clearTimeout(this.__hg._update_timeout);
        });
        GEvent.addListener(map, 'zoomstart', function(){
            if (this.__hg._update_timeout)
                clearTimeout(this.__hg._update_timeout);
        });
    }
    hg('gmapsUpdate')(map);
}

/**
 * Launch gmapsUpdate after some time.
 * Also, reset the clock, every time this function is called.
 *
 * @param GMap2 map
 * @param string event_type
 * @param boolean force update even if onChange callback returns false
 */
hg['gmapsDelayedUpdate'].f = function(map, event_type, force)
{
    if (typeof map == 'undefined')
        map = this;
    if (!(map instanceof GMap2))
    {
        console.error('No Google Map passed to gmapsDelayedUpdate');
        return;
    }

    var params = map.__hg;

    if (params.mapEvents.beforechange)
    {
        var do_update = hg(params.mapEvents.beforechange)(map, event_type);
        if (!force && false === do_update)
        {
            console.info('beforechange callback returned', do_update, ' -- not updating.');
            return false;
        }
    }

    if (params._update_timeout)
        clearTimeout(params._update_timeout);
    params._update_timeout = setTimeout(function(){
        hg('gmapsUpdate')(map, event_type, force)
    }, params.update_delay);
}

/**
 * Update map on change.
 * Fetch markers, adds them to _markers group etc.
 *
 * @param GMap2 map
 * @param string event_type probably passed from event.type
 * @param boolean force update even if onChange callback returns false
 */
hg['gmapsUpdate'].f = function(map, event_type, force)
{
    clearTimeout(map.__hg._update_timeout);

    var params = map.__hg;

    var span = hg['gmapsGetSpan'].f(map);

    console.debug('map update launched on', map, span);

    if (!params.fetch)
        return true;

    if (params.mapEvents.change)
    {
        var do_update = hg(params.mapEvents.change)(map, event_type);
        if (!force && false === do_update)
        {
            console.info('onChange callback returned', do_update, ' -- not updating.');
            return false;
        }
    }

    var data = {};
    var data_prefix = '_maps';
    if (params.form)
        data_prefix = params.form.attr('id') + '['+data_prefix+']';
    data_prefix = data_prefix + '[' + params.id + ']';
    data[data_prefix+'[span][bounds][N]'] = span.bounds.N;
    data[data_prefix+'[span][bounds][E]'] = span.bounds.E;
    data[data_prefix+'[span][bounds][S]'] = span.bounds.S;
    data[data_prefix+'[span][bounds][W]'] = span.bounds.W;
    data[data_prefix+'[span][lat]'] = span.lat;
    data[data_prefix+'[span][lon]'] = span.lng;
    data[data_prefix+'[zoom]'] = map.getZoom();

    var ajax_params = {
        data: data,
        error: function(xhr, textStatus, errorThrown)
        {
            if (params.mapEvents.afterchange)
            {
                hg(params.mapEvents.afterchange)(map, event_type);
            }
        },
        success: function(data, textStatus)
        {
            console.groupCollapsed('placing markers on the map (',params,')');
            map.__hg.msg.set(data.msg || '');

            hg('gmapsClearGroup')(map, '_markers');
            var count = 0;
            if (data.markers)
            {
                $.each(data.markers, function(){
                    hg('gmapsPlaceMarker')(map, '_markers', this, params.markerEvents);
                    count++;
                });
            }
            if (params.mapEvents.afterchange)
            {
                hg(params.mapEvents.afterchange)(map, event_type, data);
            }
            console.groupEnd();
            console.info('.. placed', count, 'markers');
        }
    };

    if (params.update_url)
        ajax_params.url = params.update_url;

    if (params.form)
    {
        if (!ajax_params.url && params.form.attr('action'))
            ajax_params.url = params.form.attr('action');
        map.__hg.form.find('.hg:input').each(function(){
            var me = $(this);
            if (!me.attr('name'))
            {
                console.warn("There's .hg:input with no name attribute set: ", this);
                return;
            }
            if (me.hasClass('helpval_active')) // see hg.helpval
                return;
            if (me.is(':checkbox:not(:checked), :radio:not(:selected)'))
                return;
            data[me.attr('name')] = me.val();
        });
    }

    map.__hg.msg.loading();
    hg('ajax')(ajax_params);
}

/**
 * Remove group of markers from a map.
 * @param GMap2 map
 * @param string group name
 */
if (!hg['gmapsClearGroup']) hg['gmapsClearGroup'] = {};
hg['gmapsClearGroup'].f = function(map, group)
{
    var marker;
    if (map && map.__hg && map.__hg.markers && map.__hg.markers[group])
    {
        while (marker = map.__hg.markers[group].shift())
            map.removeOverlay(marker);
    }
}

/**
 * get visibility span
 * @param map
 * @param factor (optional. deprecated?)
 * @todo get rid of factor param
 * @return object (floats)
 */
hg['gmapsGetSpan'].f = function(map, factor)
{
    var bounds = map.getBounds();
    var boundsNorthEast = bounds.getNorthEast();
    if (typeof factor == 'undefined')
        factor = hg['gmapsGetSpan'].factor;
    var boundsSouthWest = bounds.getSouthWest();
    var latSpan = Math.abs(boundsNorthEast.lat() - boundsSouthWest.lat());
    var lngSpan = Math.abs(boundsNorthEast.lng() - boundsSouthWest.lng());
    var zoom = map.getZoom();
    var center = map.getCenter();
    /*
    latSpan *= factor;
    lngSpan *= factor;
    */
    return {
        lat: latSpan, lng: lngSpan,
        bounds: {
            N: boundsNorthEast.lat(), E: boundsNorthEast.lng(),
            S: boundsSouthWest.lat(), W: boundsSouthWest.lng()
        },
        zoom: zoom,
        center: {
            lat: center.lat(),
            lng: center.lng()
        }
    };
}

/**
 * Place marker on the map
 * @param GMap2 map
 * @param string group name
 * @param Object marker
 *        [point]
 *        [lat]
 *        [lng]
 *        [title]
 *        [tooltip]
 *        [color]
 *        [label]
 *        [image]
 * @param Objects events callbacks to bind
 */
hg['gmapsPlaceMarker'].f = function(map, group, marker, events)
{
    if (!marker)
        return null;

    marker._map = map;

    if (typeof marker.point != 'undefined')
        var point = marker.point;
    else
        var point = new GLatLng(marker.lat, marker.lng);
    var use_tooltip = ( hg.isset(marker.tooltip) && marker.tooltip && hg.present('tooltip') );
    var markerOptions = {};
    if (!use_tooltip && hg.isset(marker.title) && marker.title)
        markerOptions.title = marker.title;
    if (typeof marker.color == 'undefined')
        marker.color = '#00ff00'; // default colour
    if (marker.image)
    {
        if ('G_DEFAULT_ICON' == marker.image)
            marker.image = G_DEFAULT_ICON.image;
        markerIcon = new GIcon(G_DEFAULT_ICON, marker.image);
        markerIcon.iconSize = new GSize(
                marker.image_width  || G_DEFAULT_ICON.iconSize.width,
                marker.image_height || G_DEFAULT_ICON.iconSize.height
            );
        markerIcon.shadow = '';
    }
    else if (marker.label)
    {
        var markerIcon = MapIconMaker.createLabeledMarkerIcon({
            primaryColor: marker.color,
            label: marker.label
        });
    }
    else
    {
        var markerIcon = MapIconMaker.createMarkerIcon({
            primaryColor: marker.color
        });
    }
    markerOptions.icon = markerIcon;

    var gmarker = new GMarker(point, markerOptions);
    gmarker.__hg = marker;
    if (events)
    {
        var events = $.isPlainObject(events) ? events : {click:events};
        console.debug('binding events:',events);
        $.each(events, function(name,callback){
            if (typeof callback != 'function')
            {
                var hg_name = callback;
                callback = function(){
                    if (!(this instanceof GMarker))
                        return;
                    return hg(hg_name).apply(this, arguments);
                };
            }
            GEvent.addListener(gmarker, name, callback);
        });
    }
    else if (marker.url)
    {
        GEvent.addListener(gmarker, 'click', function(marker, latlng){
            window.location.href = this.__hg.url;
        });
    }

    map.addOverlay(gmarker);

    if (!map.__hg.markers)
        map.__hg.markers = {};
    if (!map.__hg.markers[group])
        map.__hg.markers[group] = new Array();
    map.__hg.markers[group].push(gmarker);

    if (use_tooltip)
    {
        // we assume that tooltip will be bond with $.live('.tooltip')
        var jgmarker = $(hg('_gmapsGetElementByMarker')(gmarker));
        var jgmarker_id = jgmarker.attr('id');
        $('<div class="tooltip_content" id="'+jgmarker_id+'_tooltip">'+marker.tooltip+'</div>').appendTo('body');
        jgmarker.attr('hg:tooltip', '#'+jgmarker_id+'_tooltip');
        jgmarker.addClass('tooltip');
    }
    if (typeof callback == 'function')
        callback(gmarker);
    return gmarker;
}

/**
 * Get's marker's DOM element.
 * In Firefox -- area; in other browsers -- img
 * @author m.augustynowicz
 *
 * @param jQuery marker
 * @param tagname what element to get (if none given first will try to find 'area', then 'img)
 * @return dom element
 */
hg['_gmapsGetElementByMarker'] = {f : function(marker, tagname)
{
    if (hg.isset(marker.Fq) && hg.isset(marker.Fq[0]) && marker.Fq[0].nodeType==1)
      return marker.Fq[0];
    var answer = null;
    $.each(marker, function(key){
            if (this instanceof Array)
            {
                var ans = hg('_gmapsGetElementByMarker')(this, tagname?tagname:'area');
                if (null != ans)
                    answer = ans;
            }
            else if (typeof this.tagName != 'undefined' && this.tagName.toLowerCase() == tagname)
                    answer = this;
            if (null != answer)
                return false; // break $.each
        });
    if (!tagname && !answer)
        answer = hg('_gmapsGetElementByMarker')(marker, 'img');
    return answer;
}};

hg['gmapsGoto'].f = function (map, location, callback)
{
    var geocoder = new GClientGeocoder();
    geocoder.getLatLng(
        location,
        function (point) // callback
        {
            if (point)
                map.panTo(point);
            if (typeof callback == 'function')
              callback(point);
        }
    );
  }

hg['gmapsGetLocation'].f = function (point, callback)
{
    if (typeof point == 'string')
        point = new GLatLng(point); // FIXME
    var geocoder = new GClientGeocoder();
    geocoder.getLocations(
            point,
            callback
        );
}

hg['gmapsDelayedEventPlaceMarkers'].f = function (map)
{
    if (typeof map == 'undefined')
        map = this;
    if (map.hg__delayedEventPlaceMarkers_timeout)
        clearTimeout(map.hg__delayedEventPlaceMarkers_timeout);
    if (typeof map.getZoom != 'function')
    {
        if (hg.debug)
            console.log('gmapsDelayedEventPlaceMarkers did not found a map.');
        return;
    }
    var span = hg['gmapsGetSpan'].f(map, 0);
    if (hg.debug)
        console.log('zoom:', map.getZoom(),
                    ', bounds:', span.bounds,
                    ', will load markers in:', hg['gmapsDelayedEventPlaceMarkers'].delay );
    map.hg__delayedEventPlaceMarkers_timeout = 
        setTimeout(function()
            {
                hg('gmapsPlaceMarkers')(map);
            }, hg['gmapsDelayedEventPlaceMarkers'].delay);
}
hg['gmapsPlaceMarkers'].f = function (map)
{
    if (!map.getZoom)
        return;
    var center = map.getCenter();
    var span = hg('gmapsGetSpan')(map);
    var ctrl = map.hg__markers_ctrl;
    var params = {};
    params['zoom'] = map.getZoom();
    params['lat'] =  center.lat();
    params['lng'] = center.lng();
    params['latSpan'] = span.lat;
    params['lngSpan'] = span.lng;
    map.hg__markers_form.find(':input').each(function() {
            var t = $(this);
            if (! t.attr('name'))
                return;
            if (!t.hasClass('helpval_active') && (!(t.attr('type')=='checkbox' && !t.attr('checked'))) && (!(t.attr('type')=='radio' && !t.attr('selected'))))
                params[t.attr('name')] = t.val();
        });
    if (!hg.isset(map.hg__loading_count))
        map.hg__loading_count = 0;
    map.hg__loading_count++;
    map.hg__msgs.html('<div class="ajax_loading">loading..</div>');
    $.ajax({
        type: 'POST',
        url: map.hg__markers_url,
        data: params,
        success: function(data, textStatus)
            {
                // @todo it should be in one overlay /:
                hg('gmapClearMarkerGroup')(map, '_group');

                var markers = data.markers;
                var len = markers.length;
                console.log('fetched', len, 'markers');
                for (var i=0 ; i<len ; i++)
                {
                    markers[i] = hg('gmapsPlaceMarker')(map, '_group_', markers[i]);
                }
            },
        complete: function(req, textStatus)
            {
                if (--map.hg__loading_count<=0)
                {
                    map.hg__loading_count = 0;
                    map.hg__msgs.text('');
                }
            },
        dataType: 'json'
    });
}

hg['gmapsToggleOverlay'] =
{
    overlays : {
        'wikipedia_en' : {uri:'org.wikipedia.en'},
        'panoramio'    : {uri:'com.panoramio.all'}
    },
    f : function(name, map) {
        if (!hg.isset(map))
            map = this;
        if (!hg.isset(map.hg__overlays))
            map.hg__overlays = {};
        if (!hg.isset(hg['gmapsToggleOverlay'].overlays[name]))
        {
            console.log('gmapsToggleOverlay notice: tried to switch unknown overlay!');
            return null;
        }
        if (hg.isset(map.hg__overlays[name]))
        {
            map.removeOverlay(map.hg__overlays[name]);
            delete map.hg__overlays[name];
            return false;
        }
        else
        {
            map.addOverlay(map.hg__overlays[name] = new GLayer(hg['gmapsToggleOverlay'].overlays[name].uri));
            return true;
        }
    }
}

/**
 * If "this" is not HTMLElement
 * When no 2nd parameter is passed -- asks user to enter destination,
 * if it's present -- shows directions
 */
hg['gmapsGetDirections'].f = function(map)
{
    var me = $(this);
    var url = me.attr('href');
    map = hg.j(map).data('hg__gmap');
    var id = map.__hg.id + '__get_direction';
    if (hg.j(id).length)
    {
        map.__hg.msg.set();
        return;
    }

    map.__hg.msg.set('<form id="'+id+'"></form>');
    var form = hg.j(id);
    $('<input />',{'id':id+'__source'}).appendTo(form);
    $('<input />',{'type':'submit', 'value':'ok'}).appendTo(form);
    form.bind('submit', {'url':url}, function(e){
        var saddr = hg.j(id+'__source').val();
        window.open(e.data.url.replace('saddr=','saddr='+saddr));
        map.__hg.msg.set();
    });
}

