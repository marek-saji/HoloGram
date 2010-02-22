hg.__flashtoggler = {'hidden_objects' : null}; 

hg['hideFlashes'].f = function()
{
    //hg('unhideFlashes')();
    hg['unhideFlashes'].f();
    hg.__flashtoggler.hidden_objects = $('embed:visible, object:visible');
    //$('embed').css('visibility', 'hidden');
    hg.__flashtoggler.hidden_objects.css('visibility', 'hidden');
}

hg['unhideFlashes'].f = function()
{
    if (hg.__flashtoggler.hidden_objects)
    {
        hg.__flashtoggler.hidden_objects.css('visibility', 'visible');
        hg.__flashtoggler.hidden_objects = null;
    }
}
