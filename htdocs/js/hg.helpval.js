/**
 * Usage:
 * run the function (don't have to be in document's onload) and it will bind.
 * add to inputs attributes: class="helpval" title="some text"
 * when the helptext is displayed, input gets class helpval_active
 *
 * helptexts gets cleaned before submit, but be careful when submitting via AJAX
 *
 * or you can bind to input of your choice:
 *  helpval($('input#foo'))
 *  helpval(document.getElementById('foo'))
 *
 * @author m.augustynowicz
 *
 * @todo pola są czyszczone przed submit'em, ale jeśli submit się nie powiedzie,
 *       nie są znowu wyoełniane
 */
hg['helpval'].f = function(target)
{
    if (typeof target == 'undefined')
    {
        /** @todo fixme, for some reason .live does not work /: */
        $(function(){
            hg('helpval')(true);
        });
        return;
    }
    else if (typeof target == 'boolean' && target)
        target = $('.helpval:input');
    else
        target = $(target);

    target.each(function(){
        var t = $(this);

        t.focus(function(){
            if (t.val() == t.attr('title'))
                t.val('');
            t.removeClass('helpval_active');
        });
        t.blur(function(){
            if (!t.val())
            {
                t.addClass('helpval_active');
                t.val(t.attr('title'));
            }
        });
        t.blur();

        var form = t.get(0).form;
        if (!form.hg__has_helpval_fix)
        {
            $(form).submit(function(){
                $(this).find(':input').each(function(){
                    var t = $(this);
                    if (t.hasClass('helpval_active') && t.val() == t.attr('title'))
                    {
                        t.val('');
                        t.removeClass('helpval_active');
                    }
                });
                return true; // do submit.
            });
            form.hg__has_helpval_fix = true;
        }
    });
}

