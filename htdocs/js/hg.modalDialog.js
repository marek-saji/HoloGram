/**
 * @fixme does not resize with browser window
 */
hg['modalDialogImg'].f = function(that)
{
    if (!that) that = this;

    var source = $('#__hg_modalDialogImg');
    if (!source.length)
    {
        $('body').append('<div id="__hg_modalDialogImg" style="display:none"></div>');
        var source = $('#__hg_modalDialogImg');
    }

    var viewPortHeight = self.innerHeight || document.documentElement.clientHeight || document.body.clientHeight;
    var viewPortWidth  = self.innerWidth  || document.documentElement.clientWidth  || document.body.clientWidth;
    var img = $('<img src="'+$(that).attr('href')+'" />').css('display','none');
    img.css({
        maxHeight: (viewPortHeight-100)+'px',
        maxWidth:  (viewPortWidth-100)+'px',
        cursor:    'pointer'
    });
    img.click(function(){$.closeDOMWindow();});
    source.html('').append($('<div class="modalDialogImg" onclick="$.closeDOMWindow();"></div>').append(img));

    $.openDOMWindow({
        windowSourceID: '#__hg_modalDialogImg',
        positionType: 'centered'
    });

    return true;
}

