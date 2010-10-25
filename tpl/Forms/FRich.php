<?php
/**
 * <textarea></textarea> with WYSIWYG editing
 * @author m.augustynowicz
 *
 * (params passed as local variables) 
 * same as FString_area
 * @param null|boolean $use_loader Use Xinha's splash screen?
 */

extract(array_merge(
        array(
            // works for: xinha
            'use_loader'  => null,
            'css'         => null,
        ),
        (array) $____local_variables
    ));

$wysiwyg_parmas = compact('use_loader', 'css');

if (null !== $use_loader)
    $wysiwyg_parmas['use_loader'] = $use_loader;

if (null === $css)
    $css = $this->file('user_content', 'css');
@$____local_variables['attrs']['data-user-content-css'] = $css;

$t->inc('wysiwyg/init', $wysiwyg_parmas);

if (!@$____local_variables['disabled'])
    @$____local_variables['class'] .= ' richedit';

$t->inc('Forms/FString_area', $____local_variables);

