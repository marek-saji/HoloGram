<?php

class Debug extends HgBase
{
    static $singleton = true;
    protected $_session = null;
    protected $_evil_monkeys = 0; // count of @
    protected $_old_error_handler = null;

    public function __construct()
    {
        $this->config();
    }

    public function config()
    {
        $this->_session = & $_SESSION[g()->conf['SID']]['DEBUG'];

        if ($this->allowed())
            $old_error_handler = set_error_handler(array($this,'errorHandler'));
    }

    public function whoAmI()
    {
        $this->dump($this->_getCaller());
    }

    /**
     * Checks if debug is allowed
     * @return boolean
     */
    public function allowed()
    {
        return g()->conf['allow_debug'];
    }

    /**
     * Checks if debug is turned on for target.
     * If no target given -- checks for caller class.
     * @author m.augustynowicz
     *
     * @param null|string $target first part of classes name
     *        (i.e. "User" works for UserController, UserModel etc.)
     *        several non-class values allowed:
     *        null (globally on?), 'db' (shortcut for DataBase), 'js'
     * @param null|string $type target type, allowed values (for now): 
     *        class, model, controller
     * @param mixed $attr additional attributes depending on $type value
     *        e.g. controller's path
     * @return boolean true if debug in on for specified target
     */
    public function on($target=null, $type=null, $attr=null)
    {
        switch (true)
        {
            case !$this->allowed() : // debug not allowed
                return false;
            case $this->get() : // global debug enabled
                return true;
            case is_object($target) : // will get parsed
            case null === $target : // will get from debug_backtrace
                list($target,$type,$attr) = $this->_getCaller($target);
                break;
        }
        return $this->get($target, $type, $attr);
    }

    /**
     * Turns debug on/off for given target.
     * @author m.augustynowicz
     *
     * @param bool $state state to switch to.
     * @param null|object|array|string $what target, can be:
     *        null -- set globally,
     * 
     * @return null|boolean null if debug is not allowed,
     *         previous value otherwise.
     */
    public function set($state, $what=null)
    {
        if (!$this->allowed())
            return null;

        switch (true)
        {
            case is_array($what) && $what[0]=='all':
                if (!$state)
                {
                    $this->_session = array();
                    return 42;
                }
                else
                {
                    $what = null; // go to next 'case'
                }
            case null === $what :
                $path = array(
                    null,
                    null,
                    null,
                );
                break;
            case is_object($what) :
                $path = $this->__getSessKey($what);
                break;
            case is_array($what) :
                $path = array(
                        @$what[0],
                        @$what[1],
                        @$what[2]
                    );
                break;
            default :
                $path = array(
                    $what,
                    null,
                    null
                );
                break;
        }

        $this->_translate($path[0], $path[1], $path[2]);

        $prev_value = $this->get($path[0], $path[1], $path[2]);

        if ($state)
            $this->_session[$path[0]][$path[1]][$path[2]] = true;
        else
        {
            unset($this->_session[$path[0]][$path[1]][$path[2]]);
            if (!@$this->_session[$path[0]][$path[1]])
            {
                unset($this->_session[$path[0]][$path[1]]);
                if (!@$this->_session[$path[0]])
                    unset($this->_session[$path[0]]);
            }
        }

        return $prev_value;
    }

    /**
     * Checks if debug is set on for given target.
     * Similar to {@see on}, but doesn't do cool stuff with the backtrace
     * @author m.augustynowicz
     *
     * @param null|string $target
     * @param null|string $type
     * @param mixed $attr
     *
     * @return boolean is debug on there?
     */
    public function get($target=null, $type=null, $attr=null)
    {
        $this->_translate($target, $type, $attr);

        if (!isset($this->_session[$target]))
            $target = null;
        if (!isset($this->_session[$target][$type]))
            $type = null;
        if (!isset($this->_session[$target][$type][$attr]))
            $attr = null;
        return (bool) @$this->_session[$target][$type][$attr];
    }

    /**
     * Add debug-infos. Only if caller is in debug mode.
     * @author m.augustynowicz
     *
     * @param null|string $ident message identifier
     * @param boolean $alwayes_show false show info only when debug is
     *        enabled in calling class.
     *        true adds it for enabled debug in general.
     *        This param can be ommited.
     * @param string $message this and next parameters are passed
     *        to printf-like function.
     * @return mixed whatever Kernel::addInfo() returns or void
     */
    public function addInfo($ident=NULL, $alwayes_show=false, $message='')
    {
        $argv = func_get_args();
        $ident = array_shift($argv);
        if (is_bool($argv[0]))
            $alwayes_show = array_shift($argv);
        else
            $alwayes_show = false;

        if (!$argv)
            return;

        if ($alwayes_show)
            $show = $this->allowed();
        else
        {
            $caller = $this->_getCaller();
            $show = call_user_func_array(array($this,'on'), $caller);
        }

        if ($show)
        {
            array_unshift($argv, $ident, 'debug');
            return call_user_func_array(array(g(),'addinfo'), $argv);
        }
    }

    /**
     * Print debug backtrace.
     *
     * Accepts two parameters in any order.
     * @author m.augustynowicz
     *
     * @param string $msg Message to display.
     * @param integer|array $trace if array given, it's being used instead
     *        of debug_backtrace(), if integer - debug_backtrace() gets shifted
     * @param bool $show_context shall we show context (it may be quite big!)
     * @return void
     */
    public function trace($msg = '', $trace=array(), $show_context=true)
    {
        static $files = array();

        if (!is_string($msg))
        {
            $trace = $msg;
            $msg = '';
        }
        if (!is_int($trace))
            $shift = 1;
        else
        {
            $shift = $trace+1;
            $trace = array();
        }
        if (array()===$trace)
        {
            $trace = debug_backtrace();
            while ($shift--)
                array_shift($trace);
        }

        $hg_dir  = realpath(HG_DIR);
        $app_dir = realpath(APP_DIR);

        static $js_rendered = false;
        if (!$js_rendered)
        {
            echo <<< JS
            <script language="javascript">
            // <![CDATA[
            function hgDebugDebugToggleParent(that)
            {
                that = that.parentNode.parentNode.getElementsByTagName('pre')[0];
                hgDebugDebugToggle(that);
            }
            function hgDebugDebugToggle(that, attr, val1, val2)
            {
                if (typeof attr == 'undefined')
                    attr = 'display';
                if (typeof val1 == 'undefined')
                    val1 = 'table-row';
                if (typeof val2 == 'undefined')
                    val2 = 'none';
                if (typeof $ != 'undefined' && attr == 'display')
                    $(that).slideToggle('fast');
                else
                    that.style[attr] = (that.style[attr] == val1) ? val2 : val1;
            }
            function hgDebugPost(url, q)
            {
                var req = false;
                if (window.XMLHttpRequest)
                    req = new window.XMLHttpRequest;
                else if (window.ActiveXObject)
                    req = new ActiveXObject('Microsoft.XMLHTTP');

                if (req)
                {
                    req.open('post', url, true);
                    req.setRequestHeader( 
                        'Content-Type', 
                        'application/x-www-form-urlencoded; charset=UTF-8' 
                    ); 
                    req.send(q);
                }
            }
            // ]]>
            </script>
JS;
            $js_rendered = true;
        }

        // remember kids, pixel measurement in css is evil. but it's debug mode!
        echo '<table style="font-size: 11px; border-spacing: 0; border: black double thin; border-width: 3px 3px 1px; margin: 1ex; background-color: white;">';
        if ($msg)
            echo "<tr><td colspan=\"2\" style=\"background-color: #bbb\">$msg</td></tr>";

        $cell_style = 'style="border-bottom: thin solid black; padding: .1em; font-size: 11px"';

        echo "<tr style=\"background-color: #ccc\"><th $cell_style>what?<br /><small>(try to mouseover class name)</small></th><th $cell_style>where?<br /><small>(try clicking)</small></th></tr>";
        $odd = true;
        foreach ($trace as &$call)
        {
            echo '<tbody>';
            if ($odd ^= true)
                echo '<tr>';
            else
                echo '<tr style="background-color: #ddd">';
            echo "<td $cell_style>";
            $obj_class = isset($call['object']) ? get_class($call['object']) : false;
            if (@$call['class'])
            {
                $obj_info = array();
                if (@$call['object'] instanceof IController)
                    $obj_info[] = $call['object']->path();
                if ($obj_class && $obj_class != @$call['class'])
                    $obj_info[] = $obj_class;
                if (!$obj_info)
                    $obj_info[] = $call['class'];
                printf('<span title="%s">%s</span>::', join(", ", $obj_info), $call['class']);
            }
            echo @$call['function'];
            if (!@$call['args'])
                echo '()';
            else
            {
                echo '(<small>';
                $args = array();
                foreach ($call['args'] as $arg)
                {
                    switch ($arg_type = gettype($arg))
                    {
                        case "boolean" :
                            $arg = $arg ? 'true' : 'false';
                            break;
                        case "integer" :
                        case "double"  :
                            $arg = "$arg_type $arg";
                            break;
                        case "string"  :
                            if (strlen($arg)>20)
                                $arg = substr($arg,0,20).'&hellip;';
                            $arg = '"'.$arg.'"';
                            break;
                        case "array"   :
                            $arg = 'array('.count($arg).')';
                            break;
                        case "object"  :
                            $arg = "$arg_type ".get_class($arg);
                            break;
                        default :
                            $arg = $arg_type;
                    }
                    $args[] = $arg;
                }
                echo join(', ', $args);
                echo '</small>)';
            }
            echo '</td>';

            $fn = preg_replace('!^'.realpath(preg_quote(APP_DIR).'..').'/!', '', @$call['file']);
            if ($fn)
                echo @"<td $cell_style onclick=\"hgDebugDebugToggleParent(this)\">$fn : {$call['line']}</td>";

            echo '</tr>';

            if (@$call['file'] && $call['file'] != 'Unknown')
            {
                /**
                 * @todo would be nice if there was option to show blames for each line
                 */
                echo '<tr><td colspan="2"><pre style="margin:0;display:none;font-family:mono; white-space:pre; font-size: 1.2em">';
                if (!isset($files[$call['file']]))
                    $files[$call['file']] = file($call['file']);
                $f = $files[$call['file']];
                $n = 3;
                if (1 > $from = $call['line']-$n)
                    $from = 1;
                $to = $call['line'] + $n+1;
                for ($l = $from ; $l<$to && isset($f[$l-1]) ; $l++)
                {
                    if ($l == $call['line']) echo '<strong style="font-weight:bold;font-family:mono; white-space:pre">';
                    printf("%04d: %s", $l, htmlspecialchars($f[$l-1]));
                    if ($l == $call['line']) echo '</strong>';
                }
                printf('<button title="suprised? ask saji how to get this working {;" onclick="hgDebugPost(\'/edit.php\', \'fn=%s&amp;l=%d\');">edit</button>', $call['file'], $call['line']);
                echo '</pre>';
                echo '</td></tr>';
            }

            if ($show_context && @$call['context'])
            {
                echo '<tr><td '.$cell_style.' colspan="2">';
                if (! (defined('ENVIRONMENT') && ENVIRONMENT == LOCAL_ENV))
                    print '(if you\'d be working in local environment, you could view context here)';
                else if ((!g()->req) || g()->req->isAjax())
                    print '(if it was not an AJAX call, you could view context here)';
                else
                {
                    echo '<a href="javascript:void(0)" onclick="css=this.nextSibling.style; css.display=css.display==\'none\'?\'block\':\'none\'">toggle context visibility</a><pre style="display:none">';
                    print_r($call['context']);
                }
                echo '</pre></td></tr>';
            }

            echo '</tbody>';
        }
        echo '</table>';
    }

    /**
     * Gets informations about caller from backtrace.
     * @author m.augustynowicz
     *
     * @param null|object $caller caller object to parse
     * @param integer $backtrace_offset if $caller is not given and is taken
     *        from debug_backtrace, you can specify additional offset
     * @return array array(caller name, caller type, additional attributes)
     */
    protected function _getCaller($caller=null, $backtrace_offset=0)
    {
        // @fixme
        if (null === $caller)
        {
            $offset = $backtrace_offset;
            do
            {
                $offset++;
                $caller = g('Functions')->getCaller($offset);
                //var_dump(get_class($caller['object']));
            }
            while ((!@$caller['object']) && 'include'===$caller['function']);
            $caller = $caller['object'];
        }

        $caller_name = get_class($caller);
        $short_name  = preg_replace('/[A-Z][a-z]+$/', '', $caller_name);
        $caller_vars = get_class_vars($caller_name);
        $attr = null;
        switch (true)
        {
            case $caller instanceof Controller :
                $name = $short_name;
                $type = 'Controller';
                $attr = $caller->path();
                break;
            case $caller instanceof Model :
                $name = $short_name;
                $type = 'Model';
                break;
            /*
            case @$caller_vars['singleton'] :
                $name = $caller_name;
                $type = 'singleton';
                break;
             */
            default :
                $name = $caller_name;
                $type = 'class';
                break;
        }
        return array($name, $type, $attr);
    }

    /**
     * Translates shortcuts and such. And lowercases all.
     *
     * Only 'db' works for now (as 'DataBase')
     *
     * @author m.augustynowicz
     *
     * @param null|string $target
     * @param null|string $type
     * @param mixed $attr
     * @return void works on given parameters via reverences
     */
    protected function _translate(&$target, &$type, &$attr)
    {
        switch ($target)
        {
            case 'db' :
                $target = 'DataBase';
                break;
        }
        $overriding_classes = (array) g()->conf['classes_override'];
        $overrides = array_intersect($overriding_classes, array($target));
        foreach ($overrides as $override => &$foo)
        {
            if (preg_match('/^(.*)([A-Z][a-z]+)$/', $override, $matches))
                if ($matches[2] === $type)
                    $target = $matches[1];
        }
        $target = strtolower($target);
        $type   = strtolower($type);
        $attr   = strtolower($attr);
    }

    /**
     * Custom error handler. Is being registered in constructor.
     * @author m.augustynowicz
     *
     * @param integer $errno contains the level of the error raised
     * @param string $errstr contains the error message
     * @param string $errfile contains the filename that the error was raised in
     * @param int $errline contains the line number the error was raised at
     * @param array $errcontext contain an array of every variable that existed
     *        in the scope the error was triggered in. User error handler must
     *        not modify error context. 
     */
    public function errorHandler ($errno, $errstr, $errfile='', $errline=-1,
                                  array $errcontext=array() )
    {
        if (!$this->allowed())
            return true;

        // when debug is allowed error_reporting should indicate use of "@"
        if (!error_reporting())
        {
            $this->_evil_monkeys++;
            return true;
        }

        switch ($errstr) 
        {
            // but it's not deprecated in php-5.3. ignore this error.
            case 'is_a(): Deprecated. Please use the instanceof operator' :
                return true;
        }

        static $err_consts = array(
                E_ERROR => 'ERROR',
                E_WARNING => 'WARNING',
                E_NOTICE => 'NOTICE',
                E_USER_WARNING => 'USER WARNING',
                E_USER_NOTICE => 'USER NOTICE',
                E_STRICT => 'STRICT STANDARDS ERROR',
                /* php-5.3
                E_DEPRECATED => 'DEPRECATED',
                E_USER_DEPRECATED => 'USER DEPRECATED',
                 */
            );

        if (!$errtype = @$err_consts[$errno])
            $errtype = '<a href="http://php.net/manual/en/errorfunc.constants.php">ERR#'.$errno.'</a>';

        $msg = '<strong style="background-color:orange">['.$errtype.']</strong> '.$errstr;
        $trace = debug_backtrace();
        $trace[0] = array(
                'function' => '<small>(error happened here)</small><br />',
                'file' => $errfile,
                'line' => $errline,
                'context' => $errcontext,
            );
        $this->trace($msg, $trace);
        return true;
    }

    /**
     * Display pretty formatted and collapsable var_dump()
     * @todo make it awesome.
     */
    public function dump()
    {
        if (!$this->allowed())
            return;

        $argv = func_get_args();
        return call_user_func_array('var_dump', $argv);
    }

    /**
     * Display debug statistics.
     */
    public function printStats()
    {
        if (!$this->allowed())
            return;

        echo '<pre style="border: silver solid thin">';
        printf("<a href=\"http://google.com/images?q=evil+monkey\">evil monkeys</a> used: ~%d\n", $this->_evil_monkeys);
        echo '</pre>';
    }

}

