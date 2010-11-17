<?php
/**
 * <textarea></textarea> with WYSIWYG editing
 * @author m.augustynowicz
 *
 * (params passed as local variables)
 * same as FString_area plus:
 *
 * @param bool $xinha_use_loader show splash screen (xinha specific)
 * @param string $user_content_css css file path to use for content
 * @param string $ck_uploader uri to file uploader (ckeditor specific)
 * @param string $ck_imageuploader uri to image file uploader (ckeditor specific)
 * @param string $ck_flashuploader uri to flash file uploader (ckeditor specific)
 */

extract(array_merge(
        array(
            'xinha_use_loader' => null,
            'user_content_css' => null,
            'ck_uploader'      => null,
            'ck_imageuploader' => null,
            'ck_flashuploader' => null,
        ),
        (array) $____local_variables
    ));

$wysiwyg_parmas = array();

if (null !== $xinha_use_loader)
    $wysiwyg_parmas['use_loader'] = $xinha_use_loader;

if (null !== $user_content_css)
    @$____local_variables['attrs']['data-user-content-css'] = $user_content_css;

if (null !== $ck_uploader)
    @$____local_variables['attrs']['data-ckeditor-uploader'] = $ck_uploader;
if (null !== $ck_imageuploader)
    @$____local_variables['attrs']['data-ckeditor-image-uploader'] = $ck_imageuploader;
if (null !== $ck_flashuploader)
    @$____local_variables['attrs']['data-ckeditor-flash-uploader'] = $ck_flashuploader;



$t->inc('wysiwyg/init', $wysiwyg_parmas);

if (!@$____local_variables['disabled'])
    @$____local_variables['class'] .= ' richedit';

$t->inc('Forms/FMultilineString', $____local_variables);

