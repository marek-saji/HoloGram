<?php
/**
 * Begining of a form.
 * @author m.augustynowicz
 *
 * (params passed as local variables) 
 * @param string $id id attribute (overpowers attr[id])
 * @param array $attrs html attributes for <form /> tag
 * @param null|string $action
 * @param string $class
 * @param boolean $autocomplete pass false to disable autocompletion
 * @param boolean $has_files will this form be used to upload files?
 * @param boolean $err_handling handle hg-errors
 * @param boolean $ajax use ajax to validate form
 *
 * @return array with <form /> attributes
 */
extract(array_merge(
        array(
            'id'            => null,
            'ident'         => null,
            'attrs'         => array(),
            'action'        => null,
            'class'         => '',
            'autocomplete'  => true,
            'has_files'     => false,
            'err_handling'  => true,
            'ajax'          => true,
        ),
        (array) $____local_variables
    ));

if ($action)
    $attrs['action'] = $action;
if (false === $autocomplete)
    $attrs['autocomplete'] = 'off';
if ($has_files)
    $attrs['enctype'] = 'multipart/form-data';
if (!isset($attrs['method']))
    $attrs['method'] = 'post';
if (isset($id))
    $attrs['id'] = $id;
else
    $id = $attrs['id'];
if (isset($ident))
    $attrs['name'] = $ident;
@$attrs['class'] .= ' hg ' . $class;

printf('<form %s>', $f->xmlAttr($attrs));

if ($err_handling) :
?>
<div class="form_errors" id="<?=($id.'__err')?>" name="<?=$ident?>" <?=(@empty($errors))?'style="display:none"':''?>>
    <?php
    if (@$errors)
    {
        echo '<ol>';
        foreach ($errors as $key => $error)
        {
            printf('<li id="%s+%s">%s</li>', $id, $key, $error);
        }
        echo '</ol>';
    }
    ?>
</div>

<?php
if($ajax)
{
    g()->view->addOnLoad(<<< JS
        $('#{$id}').submit(function(e)
        {
            var form = $(this);
            var msg = form.find('.sending-form-message');
            var buttons = form.find('input[type="submit"], input[type="reset"]');
            msg.show();
            buttons
                .attr('disabled', 'disabled')
                .addClass('disabled');

            var ret = hg('form_validate')('{$ident}');
            if (!ret)
            {
                msg.hide();
                buttons
                    .removeAttr('disabled')
                    .removeClass('disabled');

                var first_invalid = $('.invalid:input:first');
                if (0 < first_invalid.length)
                {
                    var pos = first_invalid.offset().top - 10;
                    (pos < 0) && (pos = 0);
                    $('body').animate({scrollTop: pos}, 'slow');
                }
            }
            return ret;
        });
JS
    );
}

endif; // handle errors

return $attrs;

