<?php
$v = g()->view;

$v->setLang('en');
if (!$v->getTitle())
    $v->setTitle('Experience the new Hologram 2.1! <small>(alpha)</small>');

$v->addCss($this->file('infos','css'));
$v->addCss($this->file('common','css'));

$v->addJs($this->file('jquery-1.3.2.min', 'js'));
$v->addJs($this->file('hg.core', 'js'));
$v->addJs($this->file('hg.definitions', 'js'));
$v->addJs($this->file('hg.live_events', 'js'));
if (g()->debug->on('js'))
    $v->addInlineJs('var hg_debug = true');

$v->addProfile('http://purl.org/uF/2008/03/'); // combined profile

$base_uri = g()->req->getBaseUri();
$v->addInlineJs( <<< JS
var hg_base = '{$base_uri}';
var hg_include_path = hg_base+'js/';

JS
);
