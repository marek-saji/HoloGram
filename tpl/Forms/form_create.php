<?php
/**
 * Begining of a form.
 * @author m.augustynowicz
 *
 * (params passed as local variables) 
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
    $attrs['method'] = 'POST';
if (!isset($attrs['id']))
    $attrs['id'] = $ident;
@$attrs['class'] .= ' hg ' . $class;

printf('<form %s>', $f->xmlAttr($attrs));

if ($err_handling) :
?>
<div class="form_errors" id="<?=($ident.'__err')?>" <?=(@empty($errors))?'style="display:none"':''?>>
    <ol>
    <?php
    if (@$errors)
    {
        foreach ($errors as $key => $error)
        {
            printf('<li id="%s+%s">%s</li>', $ident, $key, $error);
        }
    }
    ?>
    </ol>
</div>

<?php
if($ajax)
    g()->view->addOnLoad('$(\'#'.$ident.'\').submit(function(){return hg(\'form_validate\')(\''.$ident.'\');})');

endif; // handle errors

return $attrs;

