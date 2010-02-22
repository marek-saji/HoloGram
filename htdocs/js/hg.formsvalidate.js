/**
 * Validate field(s) with ajax call.
 *
 * @author w.bojanowski
 * @author a.augustynowicz support for multiple inputs, code cleanup
 *
 * @param jQuery|DOM|Array|String input input(s) to validate
 *        (as jQuery object(s), String with jQuery selector,
 *        DOM element or array of DOM elements)
 * @param jQuery|DOM|String (optional) node to place error messages
 *        (as jQuery object(s), String with jQuery selector
 *        or DOM element)
 * @param jQuery|DOM|String (optional) form to use this field with
 *        (it has to have id attribute present, accepted in the same
 *        forms as err parameter) defaults to (first) input's form
 * @param boolean whole_form specifies if whole form is being validated
 *        or only part of it (CURRENTLY DOES NOT WORK!)
 * @return boolean validation success
 */
hg['input_validate'].f = function(input, err, form, whole_form,no_id)
{
    if (typeof whole_form == 'undefined')
        whole_form = false;

    var data = {
        validate_me: whole_form?'pretty please':'please'
    };
    var data_is_empty = true;

    if (input.length == 0)
        return;

    if (input instanceof jQuery)
        input = input.get();

    if (!(input instanceof Array))
        input = [ input ];

    var value = {};
    var input_sel = new Array();
    $.each(input, function() {
        if (this instanceof String) // WTF?!
            var t = $(this.toString());
        else
            var t = $(this);
        if (t.is(':disabled'))
            return;
        if (t.attr('type') == 'checkbox')
            data[t.attr('name')] = (''==t.attr('checked'))?0:1;
        else if (t.is('.xinha_textarea') && hg && hg.xinha && hg.xinha.editors && hg.xinha.editors[t.attr('id')])
            data[t.attr('name')] = hg.xinha.editors[t.attr('id')].getEditorContent();
        else
            data[t.attr('name')] = t.val();
        data_is_empty = false;
        input_sel.push('#'+t.attr('id'));
    });
    if(no_id != true)
        input = $(input_sel.join(', '));
    else
        input = $(input);

    if (data_is_empty)
    {
        console.debug('nothing to validate');
        return true;
    }

    if (!hg.isset(err))
        err = $('#'+input.attr('id')+'__err');
    else
        err = $(err);

    if (!hg.isset(form))
        form = $(input.get(0).form);
    else
        form = $(form);
    var url = form.attr('action');
    form = form.attr('id');

    var error_count = 0;

    var opts = {
        async: false,
        data: data,
        success : function(json_data){
            if (json_data.errors && json_data.errors[form])
            {
                $.each(json_data.errors[form], function(field){
                    var values = new Array();
                    $.each(this, function(i, val) {
                        values.push(val);
                        error_count++;
                    });
                    if (0 < error_count)
                    {
                        err.html(values.join(', '));
                        err.fadeIn('fast');
                        input.add(input.closest('.validatables_container'))
                                .addClass('invalid').removeClass('valid');
                    }
                });
            }
            if (0 != error_count)
                console.debug(error_count,'error(s)');
            else
            {
                input.add(input.closest('.validatables_container'))
                        .removeClass('invalid').addClass('valid');
                err.text('');
                err.hide();
            }
        }
    };
    
    if (url)
        opts.url = url;
    
    hg('ajax')(opts);

    return error_count == 0;
}


hg['form_validate'].f = function(ident)
{
    /**
     * @todo this is really a terrible way to validate the whole form..
     *        not all validation events are bond to "blur", and it takes ages
     *        and way too many ajax requests.. 
     */
    return !$(':input[name^="'+ident+'["]').not(':disabled').blur().hasClass('invalid');

    /*
    var err = '#'+ident+'__err';
    try{
    console.log('valid?',hg('input_validate')($('form#'+ident).find(':input[name^="'+ident+'["]'), err, ident, true));
    }catch(e){console.log(e);}
    return false;
    */
}

