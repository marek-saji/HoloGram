/**
 * Set of functions for operating on part of URL after the hash
 * (so called anchor) -- to set and get properties
 * @author m.augustynowicz
 */


if (!hg.anchorparams)
{
    hg.anchorparams = {
        params : {}, // for storeing parsed params
        i : 0 // last numeric index in params
    };
}

/**
 * Setter.
 *
 * If only `a' is present -- adds it.
 * If both are present -- set's named param
 * If a is plain object -- merge it
 * @param url 
 * @param a
 * @param b pass null to unset
 * @return void
 */
hg['anchorparamsSet'].f = function(url,a,b)
{
    var params = hg('anchorparamsGet')();
    var new_params = {};
    if (typeof b == 'undefined')
    {
        if ($.isPlainObject(a))
            $.extend(params, a);
        else
            params[hg.anchorparams.i++] = a;
    }
    else if (b)
        params[a] = b;
    else
        delete params[a];
    
    var str = [];
    $.each(params, function(i){
        var me = this.toString();
        if (parseInt(i).toString() != i) // !int
            me = i + '=' + me;
        str.push(me);
    });
    str = str.join(',').replace(/,*$/,'');
    str = '#?'+str;
    window.location.hash = str;
}

/**
 * Getter.
 *
 * If no `name' passed -- returns object with all params
 * @param url
 * @param name
 * @return one value or all params
 */
hg['anchorparamsGet'].f = function(url,name)
{
    var hash = window.location.hash.substring(1);
    hg.anchorparams.params = {};
    hg.anchorparams.i = 0;
    if ('?'==hash.substring(0,1))
    {
        $.each(hash.substring(1).split(','), function(){
            var me = this.toString();
            var match = /(.+)=(.*)/.exec(me);
            if(!match)
                hg.anchorparams.params[hg.anchorparams.i++] = me;
            else
                hg.anchorparams.params[match[1]] = match[2];
        });
    }

    if ($.isEmptyObject(hg.anchorparams.params))
        hg.anchorparams.i = 0;
    
    if (typeof name == 'undefined')
        return hg.anchorparams.params;
    else if (typeof hg.anchorparams.params[name] == 'undefined')
        return null;
    else
        return hg.anchorparams.params[name];
}

