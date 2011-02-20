<?php
/**
 * JavaScript libraries.
 * @author m.augustynowicz
 *
 * Possible options:
 *
 * - [autoload] don't load in View, when this === false
 * - [filename] local filename, sprintfed with (version, min)
 * - [cdn_path] CDN path, sprintfed with (protocol, version, min)
 *   "protocol" is suffixed with "://"
 * - [version] version of a plugin
 * - [css] array with [filename] and [cdn_path] sprintfed with the same
 *   arguments as javascript [filename] and [cdn_path]
 * - [onload] javascript to execute onload
 * - [min] by default, when minified version is used (outside debug),
 *   ".min" string is used to sprintf paths; you can overwrite it here
 * - [ie] wrap in IE conditional (e.g. "lt IE 9" or just "IE")
 * - [html5] include depending on html5 being rendered
 * - [debug] include depending on javascript debug being enabled
 */

$conf['js-libs'] = array(

    /**
     * jQuery itself
     * @url http://jquery.com
     */
    'jquery' => array(
        'filename' => 'jquery-%s%s',
        'cdn_path' => '%sajax.googleapis.com/ajax/libs/jquery/%s/jquery%s.js',
        'version' => '1.4.3',
    ),

    /**
     * jQuery UI
     * @url http://jqueryui.com
     */
    /*
    'jquery.ui' => array(
        'filename' => 'jquery-ui-%s%s',
        'cdn_path' => '%sajax.googleapis.com/ajax/libs/jqueryui/%s/jquery-ui%s.js'
        'version' => '1.7.2',
        'css' => array(
            'filename' => 'jquery-ui',
            'cdn_path' => ('%sajax.googleapis.com/ajax/libs/jqueryui/%s/themes/overcast/jquery-ui.css',
        ),
    ),
    'jquery.ui.i18n' => array(
        'filename' => 'jquery-ui-i18n',
        'cdn_path' => '%s://jquery-ui.googlecode.com/svn/tags/%s/ui/minified/i18n/jquery-ui-i18n%s.js'
        'version' => '1.7.2',
    ),
     */

    /**
     * nyroModal: really neat modal windows
     * @url http://nyromodal.nyrodev.com/
     */
    'jquery.nyroModal' => array(
        'filename' => 'jquery.nyroModal-%s',
        'css' => array(
            'filename' => 'jquery.nyroModal',
        ),
        'version' => '1.6.2',
        'onload' => '$.nyroModalSettings({debug: hg.debug})'
    ),

    /**
     * cluetip: tooltips
     * @url http://plugins.learningjquery.com/cluetip/
     */
    /*
    'jquery.cluetip' => array(
        'filename' => 'jquery.cluetip-%s',
        'css' => array(
            'filename' => 'jquery.cluetip'
        ),
        'version' => '1.0.3',
    ),
     */

    /**
     * jQuery linnt: make jquery more verbal about errors and warnings
     * @urlhttp://james.padolsey.com/javascript/jquery-lint/
     */
    /*
    'jquery.lint' => array(
        'debug' => true,
        'cdn_path' => 'http://github.com/jamespadolsey/jQuery-Lint/raw/master/jquery.lint.js'
    ),
     */

    /**
     * HoloGram Core: provide `hg` object
     */
    'hg.core' => array(
        'filename' => 'hg.core',
    ),

    /**
     * HoloGram live events: bind events to DOM objects
     */
    'hg.live_events' => array(
        'filename' => 'hg.live_events',
    ),

    /**
     * Uniform: sexy forms with jQuery
     * @url http://pixelmatrixdesign.com/uniform/
     */
    /*
    'jquery.uniform' => array(
        'filename' => 'jquery.uniform-%s%s',
        'version' => '1.5',
        'css' => array(
            'filename' => 'jquery.uniform-%s.default',
        ),
        // fix for IE and tip floats in holoforms
        'onload' => '$(".holoform li.field").css({ "z-index"  : function(i){ return 99999-i; }, "position" : "relative" });',
    ),
     */


    /**
     * ie-css3 javascript library: fix some CSS selectors
     * @url http://www.keithclark.co.uk/labs/ie-css3/
     * NOTE: there's also ie-css3 htc adding support for some attributes
     *       laying in css/ {@url http://fetchak.com/ie-css3/}
     */
    'ie-css3' => array(
        'version' => '0.9.7b',
        'filename' => 'ie-css3-%s.min',
    ),

    /**
     * DD roundies: round corners for non-awesome browsers
     * @url http://www.dillerdesign.com/experiment/DD_roundies/
     */
    # USAGE:
    # div.foo
    # {
    #   border-radius: 1em;
    #   -moz-border-radius: 1em;
    #   -khtml-border-radius: 1em;
    #   -webkit-border-radius: 1em;
    #   -ie-border-radius: expression(DD_roundies('div.foo', '10px'));
    #   /* DD_roundies accepts only px! (and "0") */
    # }
    /*
    'DD_roundies' => array(
        'filename' => 'DD_roundies_%s%s',
        'version' => '0.0.2a',
        'min' => '-min',
    ),
     */

    /**
     * html5.js is for, well.. html5
     * @url http://code.google.com/p/html5shiv/
     */
    'html5shiv' => array(
        'version' => 'r15',
        'filename' => 'html5-%s',
        'cdn_path' => '%shtml5shiv.googlecode.com/svn-history/%s/trunk/html5.js',
        'ie' => 'lt IE 9',
        'html5' => true,
    ),

);


switch (true)
{
    case strpos($_SERVER['HTTP_USER_AGENT'],'Firefox') :
    case strpos($_SERVER['HTTP_USER_AGENT'],'Gecko') :
    case strpos($_SERVER['HTTP_USER_AGENT'],'Chrome') :
    case strpos($_SERVER['HTTP_USER_AGENT'],'Chromium') :
    case strpos($_SERVER['HTTP_USER_AGENT'],'Safari') :
    case strpos($_SERVER['HTTP_USER_AGENT'],'Presto') :
        break;
    default :
        /**
         * Firebug Lite: provide `console` object.
         * @url http://getfirebug.com/firebuglite
         */
        $conf['js-libs']['firebug-lite'] = array(
            'version' => '1.2',
            'cdn_path' => '%sgetfirebug.com/releases/lite/%s/firebug-lite-compressed.js',
        );
}

