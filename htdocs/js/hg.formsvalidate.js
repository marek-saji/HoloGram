/**
 * Validate field(s) with ajax call.
 *
 * @author w.bojanowski
 * @author a.augustynowicz support for multiple inputs, code cleanup
 * @author b.matuszewski first working version of whole_form validation
 *
 * @param jQuery|DOM|Array|String input input(s) to validate
 *        (as jQuery object(s), String with jQuery selector,
 *        DOM element or array of DOM elements)
 *        You can also pass jQuery with form here to validate all it's inputs
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
hg['input_validate'].f = function(input, err, form, whole_form, no_id)
{
    if (typeof whole_form == 'undefined')
        whole_form = false;

    // are there only forms in input?
    if (input instanceof jQuery && input.is('form') && !input.is(':not(form)'))
    {
        form = input;
        whole_form = true;
        err = null;
        input = form.find('.hg:input[id]:not(:disabled)');
    }

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
        if(t.attr('id') != '')
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

    if (!hg.isset(form))
        form = $(input.get(0).form);
    else
        form = hg.j(form);
    var url = form.attr('action');
    var form_name = form.attr('name');
    var all_errors_count = 0;

    var opts = {
        async: !whole_form,
        data: data,
        success : function(json_data){
            var invalid_fields = $();
            if (json_data.errors && json_data.errors[form_name])
            {
                // errors[form_name] : {field: {error, ..}, field:{}, ...}
                $.each(json_data.errors[form_name], function(field){
                    var values = new Array();
                    var errors_count = 0;
                    // collect error messages
                    $.each(this, function(i, val){
                        values.push(val);
                        errors_count++;
                    });
                    if ('0' == field)
                    {
                        // general form errors
                        var field_input = $();
                        var err = $('#' + form.attr('id') + '__err');
                    }
                    else
                    {
                        // field specific errors
                        if (1 == input.length)
                        {
                            var field_input = input;
                        }
                        else
                        {
                            var field_input = $(':input[name^="'+form_name+'['+field+']"]');
                        }
                        
                        /**
                         * checking if last element from a set of inputs has attr('id')
                         * if so then we take it with suffix '_err' to find field_error
                         * label container.
                         * if not we try to find first input haveing id
                         *
                         * @TODO think about situation when neighter of inputs is haveing id
                         */
                        if(field_input.eq(-1).attr('id') == '')
                        {
                            for(key in field_input)
                            {
                                if($(field_input[key]).attr('id'))
                                {
                                    var attr_id = $(field_input[key]).attr('id');
                                    break;
                                }
                            }
                        }
                        else
                            var attr_id = field_input.eq(-1).attr('id');
                        var err = $('#' + attr_id + '__err');
                    }
                    if (0 >= errors_count)
                    {
                        err
                            .html('')
                            .fadeOut('fast');
                        field_input
                            .removeClass('invalid')
                            .addClass('valid');
                    }
                    else
                    {
                        invalid_fields = invalid_fields.add(field_input);
                        err
                            .html(values.join(', '))
                            .fadeIn('fast');
                        field_input
                            .addClass('invalid')
                            .removeClass('valid');
                    }
                    all_errors_count += errors_count;
                });
            }

            if (0 != all_errors_count)
            {
                console.debug(all_errors_count, 'error(s) in',
                        invalid_fields.length, 'fields');
            }

            // mark rest of fields as valid
            input
                .not(invalid_fields)
                    .removeClass('invalid')
                    .addClass('valid')
                    .each(function(){
                        $('#' + $(this).attr('id') + '__err')
                            .html('')
                            .fadeOut('fast');
                    });

            // handle .validatables_container class
            form
                .find('.validatables_container')
                    .each(function(){
                        var me = $(this);
                        var invalid = (0 != me.find('.invalid').length);
                        me
                            .add($(me.data('also-hightlight-when-invalid')))
                                .toggleClass('invalid', invalid)
                                .toggleClass('valid',  !invalid);
                    });

        if(all_errors_count)
            $.nyroModalSettings({
                width: null,
                height: null
            });
        } // opts.success
    };
    
    if (url)
        opts.url = url;
    
    hg('ajax')(opts);

    return all_errors_count == 0;
}


hg['form_validate'].f = function(ident, err)
{
    /**
     * @todo this is really a terrible way to validate the whole form..
     *        not all validation events are bound to "blur", and it takes ages
     *        and way too many ajax requests.. 
     */
    //return !$(':input[name^="'+ident+'["]').not(':disabled').blur().hasClass('invalid');
    return hg('input_validate')($('form[name="'+ident+'"]'));
    return hg('input_validate')($(':input[id][name^="'+ident+'["]').not(':disabled'),
                err,
                'form[name="'+ident+'"]',
                true
            );
    
    /*
    var err = '#'+ident+'__err';
    try{
    console.log('valid?',hg('input_validate')($('form#'+ident).find(':input[name^="'+ident+'["]'), err, ident, true));
    }catch(e){console.log(e);}
    return false;
    */
}

