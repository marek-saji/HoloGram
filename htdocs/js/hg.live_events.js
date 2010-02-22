/**
 * @todo to be changed to $.live() binds, when I'll switch to jQuery-1.3
 */
$(function() {

// tooltips
$('.tooltip').bind('mouseover',function(){
        hg('tooltip')(this);
    });
$('.tooltip').bind('mouseout',function(){
        hg('tooltipHide')(this);
    });

// thickbox-alike galleries
// ..
// look for this in ODI. possibly.

// confirmations
// ..

});
