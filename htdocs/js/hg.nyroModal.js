/**
 * Live binds modal to every present and future element
 * matching openSelector.
 *
 * Also, hides everything matching '.modal'.
 * Should be called after DOM has been loaded, with nyroModal present
 * @author m.augustynowicz
 *
 * @return true on success
 */
hg['nyroModalInit'].ajax_i = null;
hg['nyroModalInit'].f = function()
{
    if (typeof $.nyroModalSettings == 'undefined')
    {
        console.error('Tried to initialize nyroModal, but it was not found!');
        return false;
    }

    // these will be shown by nyroModal
    $('.modaled').hide();

    var prevSettings = $.fn.nyroModal.settings;
    var prev_openSelector = $.fn.nyroModal.settings.openSelector;
    var prev_closeSelector = $.fn.nyroModal.settings.closeSelector;

    var prev_title = document.title;

    var open_selector = '.modal, a.thickbox, a[rel^="lightbox"]';
    if (prev_openSelector)
        open_selector += ', ' + prev_openSelector;

    $.nyroModalSettings({
        // don't display title on image
        addImageDivTitle: false,
        // bind to these
        openSelector: '',
        // Regex to find images
		regexImg: '[^\\.]\\.(jpg|jpeg|png|tiff|gif|bmp)\s*(?:\\?|$)',
        // selector to close the modal
        closeSelector: prevSettings.closeSelector+', .modalClose',
        // Value added when a form or Ajax is sent with a filter content
        selIndicator: 'modalSel',
        // min size. let's make it small.
        minWidth:  48,
        minHeight: 48,
        // hide flashes before displaying background
        showBackground: function(elts,settings,callback){
            if (hg['hideFlashes'])
                hg('hideFlashes')();
            elts.bg.css({opacity:0}).fadeTo(500, 0.75, callback);
        },
        // focus form inputs if any
        endShowContent: function(eltrs,settings){
            var content = $('#nyroModalContent');
            if (hg['nyroModalInit'].ajax_i)
            {
                var ajax_i = hg['nyroModalInit'].ajax_i;
                hg('ajaxDOMReady')(ajax_i);
                content.addClass(hg['ajax'].data[ajax_i].class);
            }
            content
                .find(':input:first')
                    .focus();
            hg['nyroModalInit'].ajax_i = null;
        },
        // .. and re-show them afterwards
        endRemove: function(){
            if (hg['unhideFlashes'])
                hg('unhideFlashes')();
            document.title = prev_title;
        },
        // ajax related things
        ajax: {
            data: {'hg+id_offset': window.hg_id_offset},
            type: 'POST',
            // will treat response as HTML string
            dataType: 'html',
            // and parse it with this callback:
            dataFilter: function(rawData, type){
                data = $.parseJSON(rawData);
                hg['nyroModalInit'].ajax_i = 
                        hg('ajaxHandleResponse').call(this, data);
                htmlData = data.html;
                data.html = '';
                return htmlData;
            }
        }
    });

    // it's alive, aLIVE!
    $('.modal').live('click', function(e){
        e.preventDefault();
        $.nyroModalManual($.extend({}, $.fn.nyroModal.settings, {from: this}));
    });

    return true;
}

