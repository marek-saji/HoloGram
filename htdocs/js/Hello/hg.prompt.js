/**
 * Metoda abstrachująca wyświetlanie okienek.
 *
 * Póki co korzysta z impromptu, ale w ogólności chodzi o to, żeby było
 * można to łatwo zmienić (thickbox?).
 * Z ciekawych rzeczy: chowa flash'e i pokazuje je po tym, jak okienko zniknie.
 * @author m.augustynowicz
 */

/**
 * parametry:
 * title
 * i -- array (objęty w []), a w nim obiekty z parametrami:
 *      type -- hidden|text|password|checkbox|textarea|radio|select
 *      label
 *      name
 *      value
 *      options -- obiekt z opcjami dla radio|select
 *      pozostale przekazywane jako atrybuty HTMLa
 * action
 * submit
 * callback
 * @todo ajax, ajah
 */
hg['prompt'].f = function(argv)
{
    if (!hg.isset(argv) || !hg.isset($.prompt))
        return -1;

    var opts = {
        submit: function(btn, jform) {
            var do_post = send_post;
            var ret = true;
            if (hg.isset(argv.submit))
                ret = argv.submit(btn, jform);
            if (do_post && ret && btn)
                jform.find('form').submit();
            return ret; // true closes prompt
        }
    };

    /*
     * arguments
     */
    // title
    if (hg.isset(argv.title))
        var txt = '<h3>'+argv.title+'</h3>';
    else
        var txt = '';
    // buttons
    if (hg.isset(argv.buttons))
        opts.buttons = argv.buttons;
    // i
    if (!hg.isset(argv.i))
        argv.i = new Array(); // empty object
    // TODO ajax and ajah
    var send_post = true;
    if (hg.isset(argv.ajax))
        send_post = false;
    if (hg.isset(argv.ajah))
        send_post = false;
    // callback
    opts.callback = function() {
        if (hg['unhideFlashes'])
            hg('unhideFlashes')();
        if (hg.isset(argv.callback))
        {
            argv.callback.apply(this, arguments);
            send_post = false;
        }
    }

    /*
     * build form
     */
    var form_html = '<form action="" method="post">';
    // action
    if (hg.isset(argv.action))
        form_html += '<input type="hidden" name="action" value="'+argv.action+'" />';
    var form_id = 0;
    $.each(argv.i, function(i, e)
    {
        if (!hg.isset(e.type))
            e.type = 'text';
        if (!hg.isset(e.value))
            e.value = '';
        if (e.type != 'hidden')
        {
            form_html += '<dl class="'+e.type+'">';
            if (hg.isset(e.label))
            {
                label = e.label + ' ';
                delete e.label;
                form_html += '<dd><label for="impromptu_i'+form_id+'">'+
                             label+'</label></dd>';
            }
            form_html += '<dt>';
        }
        var e_type = e.type;
        var attrs = 'id="impromptu_i'+form_id+'" ';
        $.each(e, function(i, attr)
        {
            switch (e.type)
            {
                case 'select' :
                case 'radio' :
                    if ('options'==i)
                        return;
                case 'textarea' :
                    switch (i)
                    {
                        case 'type' :
                        case 'value' :
                            return;
                    }
                break;
            }
            attrs += i + '="' + attr + '" ';
        });
        switch (e.type)
        {
            case 'textarea' :
                form_html += '<textarea '+attrs+'>'+e.value+'</textarea>';
                break;
            case 'select' :
                form_html += '<select name="'+e.name+'">';
                $.each(e.options, function(v, opt)
                {
                    form_html += '<option';
                    if (v==e.value)
                        form_html += ' selected="selected"';
                    form_html += ' value="'+v+'">'+opt+'</option>';
                });
                form_html += '</select>';
                break;
            case 'radio' :
                $.each(e.options, function(v, opt)
                {
                    form_html += '<label><input type="radio"';
                    if (v==e.value)
                        form_html += ' checked="checked"';
                    form_html += ' value="'+v+'" name="'+e.name+'" />'+opt+'</label>';
                });
                break;
            default :
                form_html += '<input '+attrs+' />';
                break;
        }
        if (e.type != 'hidden')
            form_html += '</dt></dl>';
        form_id++;
    });
    form_html += '</form>';
    txt += form_html;

    if (hg['hideFlashes'])
        hg('hideFlashes')();

    $.prompt(txt, opts);

}

/**
 * @author m.augustynowicz
 * @todo i18n
 * @param object t "this" obiektu wywolujacego -- powinien miec atrybut "form",
 *                 ktory chcemy submitowac
 * @param string msg opcjonalny parametr z pytaniem komunikatem do wyswietlenia
 * @return false
 */
hg['confirm'].f = function(t, msg)
{
    if (!hg.isset(msg))
        msg = 'Na pewno?';
    hg('prompt')({
        title: msg,
        buttons: {tak: true, nie: false},
        callback: function(btn) {
            if (btn)
            {
                // rather lame way.
                t = $(t);
                t.attr('onclick','');
                t.unbind('click');
                t.click();
            }
        }
    });
    return false;
}

