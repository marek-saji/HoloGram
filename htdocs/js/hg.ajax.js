/**
 * HoloGram AJAX handing.
 * @author m.augustynowicz
 *
 * USAGE:
 * <code>
 * // use to handle HG ajax requests. response's data is expected
 * // be JSON of certain structure
 * hg('ajax'({
 *     'url' : 'specify if you want to use different than current',
 *     'node' : 'if specified, data.html from response will be placed in that \
 *               node. can be either jQuery or selector or DOM node or id',
 *     'dataPrefix' : 'when passing any POST data, specify something like \
 *                     foo[bar][xx] to change all keys in data from \
 *                     "key" to "foo[bar][xx][key]"',
 *     // rest of the params got passed to $.ajax(), but some of them got
 *     // overriden: dataType is always json, type is always post;
 *     // error and success got injected with tome special HG-lovin'
 * });
 * </code>
 */

if ('undefined' == typeof hg.ajax.data)
    hg.ajax.data = [];
if ('undefined' == typeof hg.ajax.id)
    hg.ajax.id = 0;


// it may have not been declared
if (!hg['ajaxHandleResponse'])
    hg['ajaxHandleResponse'] = {};
if (!hg['ajaxDOMReady'])
    hg['ajaxDOMReady'] = {};
if (!hg['ajaxEvalJS'])
    hg['ajaxEvalJS'] = {};


/**
 * Make an in-HoloGram ajax request
 * See declaration of params variable for available params
 */
hg['ajax'].f = function(given_params)
{
    var params = {
        node : null, // where to place data.html (jQuery|DOMElement|selector)
        dataPrefix : '', // prefix data keys with this.
        success : function(data, textStatus, XHR){},
        error : function(XHR, textStatus, errorThrown){}, // this "data" has only html property
        // rest of parameters passed to $.ajax
        // (you may want to set url and data here)
        // @see http://api.jquery.com/jQuery.ajax/
        async: true
    };
    $.extend(params, given_params,{
        dataType: 'json',
        type: 'POST',
        // HG values
        success: function(data, textStatus, XHR){
            var ajax_id = hg('ajaxHandleResponse')(data);
            if (this.node)
            {
                hg.j(this.node).html(data.html);
                delete data.html;
            }
            if (given_params.success)
            {
                given_params.success.apply(this, arguments);
            }
            hg('ajaxDOMReady')(ajax_id);

        },
        error: function(XHR, textStatus, errorThrown){
            if (hg.debug)
            {
                var data = {html: '<div id="pre-display_stuff" class="ajax error"><div onclick="$(this).parent().toggleClass(\'foo\')" title="switch pre-display stuff visibility" id="pre-display_stuff_switcher" style="color:red">x</div>'+XHR.responseText+'</div>'};
                var ajax_i = hg('ajaxHandleResponse')(data);
                hg('ajaxDOMReady')(ajax_i);
            }
            if (given_params.error)
            {
                given_params.error.apply(this, arguments);
            }
        }
    });
    
    if (params.data && params.dataPrefix)
    {
        params.data = {};
        $.each(given_params.data, function(name){
            params.data[params.dataPrefix + name.replace(/^([^[]+)/, '[$1]')] = this.toString();
        });
        hg.foo = params.data;
    }

    $.ajax(params);
}


/**
 * Executing inline js-es, adding link and style tags, and storing data
 * @param Object data JSON returned by HoloGram
 */
hg['ajaxHandleResponse'].f = function(data)
{
    var head = $('head');
    var i = ++hg.ajax.id;
    hg.ajax.data[i] = data;

    if (data.js)
    {
        hg('ajaxEvalJS')(data.js);
        delete data.js;
    }

    if (data.links)
    {
        $.each(data.links, function(i){
            /**
             * @todo fix this. it should match something
             *       alse than "href" (some HoloGram resource id maybe?)
             *       current way does not work in debug mode
             */
            if ($('link[href^="'+this.href+'"]').length)
                return;
            var link = $('<link />');
            $.each(this, function(name){
                link.attr(name, this);
            });
            head.append(link);
        });
        delete data.links;
    }

    if (data.css)
    {
        // crippled! no duplicate checking.
        var css = '';
        $.each(data.css, function(i){
            css += "\n\n" + this;
        });
        var style = $('<style type="text/css"></style>');
        style.text("/* <[CDATA[ */\n"+css+"\n/* ]]> */");
        head.append(style);
        delete data.css;
    }

    if (!data.html)
        data.html = '';
    else if (hg.debug)
    {
        /**
         * WARNING: this does not work as it should be.
         * sometimes jQuery's html() fails miserably. /:
         */
        var tmp_node = $('<div />', {css: {display: 'none'}});
        tmp_node
            .appendTo('body')
            .html(data.html);
        tmp_node.children('#pre-display_stuff.ajax')
            .detach()
            .appendTo('body');
        data.html = tmp_node.html();
        tmp_node.remove();
    }

    return i;
}

/**
 * This should be called, when changes in dom after ajax
 * request has been made. It launches OnLoad js code and
 * id debug mode, appends data.html at the end of document.
 */
hg['ajaxDOMReady'].f = function(i)
{
    if (hg.ajax.data[i].onload)
    {
        hg('ajaxEvalJS')(hg.ajax.data[i].onload);
        delete hg.ajax.data[i].onload;
    }

    if (hg.debug && hg.ajax.data[i].html)
    {
        var id = 'ajax_out'+i;
        $('<div/>', {
            id: id,
            'class': 'debug ajax',
            html: hg.ajax.data[i].html
        }).appendTo('#foot_debug');
        delete hg.ajax.data[i].html;
    }

    // why outside that if statement?
    // because it may have already been placed in DOM
    $('#pre-display_stuff.ajax:not(.hg_positioned)')
        .addClass('hg_positioned')
        .last() // if there's >1, DOMReady was not called form them.
        .css('left', '1.5em')
        .find('#pre-display_stuff_switcher')
            .css({top:(i*1.5)+'em', left:'0'})
            .html('<strong>('+i+')</strong>');

    if (hg.ajax.data[i].title)
    {
        document.title = hg.ajax.data[i].title;
    }
}

/**
 * Evaluate array of javascript codes
 * @param Array|Object that
 */
hg['ajaxEvalJS'].f = function(that)
{
    $.each(that, function(i){
        var js = this.toString();
        try
        {
            eval.call(window, js);
        }
        catch(e)
        {
            if (hg.debug)
                console.log('this code failed:',js, e);
        }
    });
}

