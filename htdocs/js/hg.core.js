/**
 * Rdzenny javascript Hologramu
 * @author m.augustynowicz
 * @package hologram
 */

if (typeof($)!='function')
{
    // jQuery not loaded.
    var hg  = function () { return function(){return hg['exception'].f;} };
    hg.debug = false;
}
else
{

    // dummy function for when firebug is not available
    // satisfying http://getfirebug.com/console.html
    if (typeof console != 'object')
        console = {};
    if (typeof console.log != 'function')
        console.log = function(){};
    $.each(['info','warn','error','debug','assert','dir','dirxml','group','groupCollapsed'], function(){
        if (typeof console[this] != 'function')
            console[this] = console.log;
    });
    $.each(['trace','groupEnd','time','timeEnd','count','profile','profileEnd'], function(){
        if (typeof console[this] != 'function')
            console[this] = function(){};
    });

    console.info('hg.core initiation');

    /**
     * does a magic trick
     *
     * @todo dodac serializacje bez debug'u (po hg_app_ver)
     *
     * @author m.augustynowicz
     *
     * @param nazwa funkcji do wywolania
     * @return funkcja
     */
    function hg(f)
    {
        console.info('hg(',f,') called' + (hg.debug?', debug enabled':''));
        try
        {
            if (typeof(hg[f].f)!='function')
            {
                var path = hg[f].js;
                if (path.charAt(0)!='/')
                    path = hg.include_path + path;

                if (hg.debug)
                {
                    path += (-1==path.indexOf('?')) ? '?' : '&amp';
                    path += (new Date()).getTime().toString(36);
                }

                if (false)
                //if (hg.debug) // experimental loading.. thought it seems to be working just fine
                {
                    if (!hg.load(path, 'hg["'+f+'"].f'))
                        throw 'failed to load hg('+f+')';
                    else if (hg.debug)
                        console.info('loaded hg(',f,') from',path);
                }
                else
                {
                    $.ajax({
                        type: 'GET',
                        url: path,
                        async: false,
                        success: function(data) {
                            eval(data);
                        },
                        error: function(XMLHttpRequest, textStatus, errorThrown) {
                            var msg = '$.ajax error: ';
                            if (undefined!=textStatus)
                                msg += textStatus;
                            if (undefined!=errorThrown)
                                msg += errorThrown;
                            throw msg;
                            this;
                        }
                    });
                }
            }
            return hg[f].f;
        }
        catch(e)
        {
            if (hg[f]==undefined)
                hg[f] = {};
            return hg[f].f = function()
                {
                    console.error('hg(',f,') failed:', e);
                    if (f!='exception')
                        hg('exception')(e);
                    return function() { return null; }
                }
        }
    }

    /**
     * Loader of javascripts.
     * Function stops only after loading the file (or after max_load_time).
     * @author Marek saji Augustynowicz
     *
     * @param string path path to js file
     * @param mixed wait_for anything that will get declared in that js file
     */
    hg.load = function(path, wait_for)
    {
        //console.info('Loading', path, 'that should contain', wait_for);
        $('head').append('<script id="hg__'+path+'" src="'+path+'"></script>');
        if (typeof wait_for == 'undefined') // don't wait for anything.
            return true;
        var load_start = (new Date()).getTime();
        var return_me = true;
        var interval = setInterval(function(){
            if (eval('typeof '+wait_for) != 'undefined')
                clearInterval(interval);
            else if ((new Date()).getTime() - load_start < hg.load.max_load_time)
                console.warn('still waiting for', wait_for, '..');
            else
            {
                console.error(wait_for, 'failed to load');
                clearInterval(interval);
                return_me = false;
                return;
            }
        }, 50);
        return return_me;
    }
    hg.load.max_load_time = 1000;

    /*
     * Domyslne ustawienia obiektu hg
     */

    // bazowa sciezka do systemu
    hg.base = (typeof hg_base == 'undefined') ? '/' : hg_base;
    // bazowa sciezka do skryptow, uzywana jesli js _nie_ zaczyna sie od '/'
    hg.include_path = (typeof hg_include_path == 'undefined') ? hg.base+'js/' : hg_include_path;
    // tryb debug
    hg.debug = (typeof hg_debug == 'undefined') ? false : hg_debug;


    /*
     * Definicje funkcji
     */

    hg['alert'] = {f:function(a) { alert(a); }};
    

} // if $ is object

hg['exception'] = {f:function(e) {
    if (!hg.debug)
    {
        return;
    }

    hg.console = document.getElementById('hg_messages');
    if (typeof(hg.console)=='undefined' || hg.console == null)
    {
        hg.console = document.createElement('div');
        hg.console.setAttribute('id', 'hg_messages');
        // FIXME position:fixed versus IE
        hg.console.setAttribute('style', 'position:fixed; top:0; right:0; padding: 1ex; background-color: pink; border: silver solid thin; -moz-border-radius: 0 0 0 1ex; opacity: .9;');
        document.getElementsByTagName('body')[0].appendChild(hg.console);
    }
    var e_text = e;
    hg.console.innerHTML += '<p>damn. some failure happened. '+e_text+'</p>';
    setTimeout(function(){
	if (hg.console.lastChild)
	    hg.console.removeChild(hg.console.lastChild);
	if (!hg.console.childNodes.length)
	    hg.console.parentNode.removeChild(hg.console);
    }, 10000);
}};

// some helper functions
hg.isset = function(a) { return typeof(a)!='undefined' && a!=null; };

hg.j = function(a) { b = $(a); return (b.length || (typeof a).toLowerCase() != 'string') ? b : $('#'+a); };

/**
 * Check if given funcion is present
 * @author m.augustynowicz
 *
 * @param string function name
 * @return boolean
 */
hg.present = function(name) {
    if (typeof hg[name] == 'undefined')
        return false;
    return (typeof hg[name].f != 'undefined' || typeof hg[name].js != 'undefined');
}


/**
 * I spy with my little eye.. that some(IE) browsers tend to suck.
 * Oh, but can we do anything about it? YES, WE CAN!
 */
if (!Array.indexOf)
Array.prototype.indexOf = function(foo)
{
    for (var i in this)
        if (this[i] == foo) return i;
    return -1; // not found
}

if (!String.escapeRegExp)
String.escapeRegExp = function()
{
    var specials = new RegExp("[.*+?|()\\[\\]{}\\\\]", "g"); // .*+?|()[]{}\
    return this.replace(specials, "\\$&");
}
