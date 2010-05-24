/**
 * Creates and displays tooltip for given dom element.
 * Should be bond to onmousein event to all ".tooltip"
 * There are three possibilities to do a tooltip:
 * 1. <div class="tooltip" title="help text">(?)</div>
 * 2. <div class="tooltip_wrapper">
 *      <span class="tooltip">(?)</span>
 *      <div class="tooltip_content"><h6>help</h6><p>html tooltip</p></div>
 *    </div>
 * 3. <span hg__tooltip="#tooltip_content_selector">(?)</span>
 *
 * @author m.augustynowicz
 *
 * @param t "this" object
 * @param e event object (not used)
 * @return void
 */
hg['tooltip'].f = function(t, e)
{
    if (typeof t == 'undefined')
    {
        $('.tooltip')
            .live('mouseenter', function(){hg('tooltip')(this);})
            .live('mouseleave', function(){hg('tooltipHide')(this);})
        ;
        return;
    }

    t = $(t);
    var title = t.attr('title');
    var rel = t.attr('hg__tooltip');
    if (rel)
    {
        var tooltip = $(rel);
    }
    else
    {
        if (title)
            t.wrap('<div class="tooltip_wrapper" style="margin:0;padding:0;"></div>');
        var wrapper = t.parents('.tooltip_wrapper:first');
        if (title)
        {
            wrapper.append('<div class="tooltip_content">'+t.attr('title')+'</div>');
            t.attr('title','');
        }
        var tooltip = wrapper.children('.tooltip_content:first');
    }

    $.openDOMWindow({ 
            height: 'auto',
            width: '15em',
            positionType: 'anchored',
            anchoredClassName: 'helpTooltip',
            anchoredSelector: t,
            windowSourceID: tooltip,
            positionTop: 7,
            positionLeft: 0
        }); 
}

/**
 * Hide tooltip related to given object.
 * @author m.augustynowicz
 *
 * @param t "this" object
 * @return void
 */
hg['tooltipHide'].f = function(t)
{
    t = $(t);
    $.closeDOMWindow({ 
            anchoredClassName: 'helpTooltip',
            anchoredSelector: t,
            windowSourceID:   t
        }); 
}

