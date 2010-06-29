<?php
/**
 * Plik zawiera klase Forms do tworzenia i obslugi formularzy 
 *
 * @author w.bojanowski
 * @version 1
 * @package hologram2.1
 */

if (!defined('USE_AJAX_BY_DEFAULT'))
    define('USE_AJAX_BY_DEFAULT', true);


/**
 * Klasa do tworzenia i obslugi formularzy
 * @author w.bojanowski
 * @author a.augustynowicz simplification of inputs declaration and use of Kernel::$infos
 * @version 1
 * @package hologram2.1
 *
 */
class Forms extends HgBase
{

/**
 *  @example
 *  property $forms that should be placed in component that renders form
 *  <code>
 *  public $forms = array(
 *              'register_user' => array( // form name.
 *                                        // try not to choose common name for this
 *                                        // as <form /> gets this name as id
 *                          'ajax' => false, // use fancy ajax validation? (default: true)
 *                          'upload' => true, // files will get uploaded by this form
 *                          'model' => 'User', // default model for fields if none supplied
 *                          // declarations of avaliable input fields
 *                          'inputs' => array(
 *                              // login from default model
 *                              'login',
 *                              // email from other model
 *                              'email' => 'UsersMails',
 *                              // email validated by several models' email field
 *                              'email22' => array('UsersMails', 'UsersSomethings'),
 *                              // input called address validated by addr field in UsersAddressessModel
 *                              'address' => array('addr' => 'UsersAddresses'),
 *                              // field from several models, with different names
 *                              'surname' => array(
 *                                  'last_name' => 'Users',
 *                                  'surname' => array('UsersAddresses', 'UsersUsers'),
 *                              ),
 *                              // in any array form you can additionally specify alternative template
 *                              'description' => array(
 *                                  '_tpl' => 'FString_area',
 *                              ),
 *                              // field not related to any fields in any models
 *                              'non-model-input' => array(
 *                                  '_tpl' => 'sth',
 *                                  'fields' => false, // input is not related to any model
 *                              )
 *                              // most elastic form (warning: got literally copied!)
 *                              'input_name' => array('models'=>array(
 *                                  'model1' => array('field1','field2'),
 *                                  'model2' => array('field3')
 *                              )),
 *                          )
 *              )
 *  );
 *  </code>
 *
 *  NOTE: all this mess got parsed in Component::_fixFormsFormat()
 *
 */

    public static $singleton=false;
    protected static $_used_ids = array();
    private $__ident;
    private $__model;
    private $__ctrl;
    private $__form;
    
    
    /**
     * @param array $params accepted params:
     *        [0] form id
     *        [1] object of Component that is using this form
     */
    public function __construct(array $params=array())
    {
        if(!$params[1] instanceof Component)
            throw new HgException("Second form constructor parameter must bee instance of Component!");
        $this->__ctrl = $params[1];
        $this->__short_ident = $params[0];
        $this->__ident = $this->__ctrl->getFormsIdent($this->__short_ident);

        if(isset($params[2]) && $params[2] instanceof Model)
        {
            $ds = $params[2];
            $this->__form['model'] = $ds->getName();
            $fields = $ds->getFields();
            foreach($fields as $fname => $field)
                $this->__form['inputs'][$fname] = array('models'=>array($this->__form['model']=>array($fname)));
                
            $this->__form['ajax'] = false;
            $this->__ctrl->forms[$this->__short_ident] = $this->__form;
        }
        elseif(!isset($this->__ctrl->forms[$this->__short_ident]))
            throw new HgException("Form not defined in controller!");
        else
            $this->__form = & $this->__ctrl->forms[$this->__short_ident];
        
        if ($model = @$this->__form['model'])
            $this->__model = g($model,'model');
        parent::__construct($params);
    }

    
    /**
     * Renders begining of the form.
     *
     * @todo update when we will use file fields
     *
     * @return void
     */
    public function create($action='', array $additional_params=array())
    {
        $f = g('Functions');

        $params = array_merge(array(
            'id' => $f->uniqueId($this->__ident),
            'ident'=>$f->ASCIIfyText($this->__ident),
            'ajax'=>isset($this->__form['ajax'])?$this->__form['ajax']:USE_AJAX_BY_DEFAULT,
            'errors' => $this->getErrors(),
            'action'=>$action,
            'has_files' => @$this->__form['upload']
        ), $additional_params);
        return $this->__ctrl->inc('Forms/form_create',$params);
    }
    
    /**
     * Renders end of the form.
     * @return void
     */
    public function end()
    {
        return $this->__ctrl->inc('Forms/form_end', array(
            'ident' => $this->__ident,
            'form'  => $this,
        ));
    }
    
    /**
     * Renders form field using definition from Component::$forms.
     *
     * @param string $input key in $forms[inputs]
     * @param array $additional_params additional params to pass to the template
     * @return whatever template returns
     */
    public function input($input, array $additional_params = array())
    {
        if(!isset($this->__form['inputs'][$input]))
            throw new HgException("Input `$input' not defined in controller variable \$forms!");

        $input_def = & $this->__form['inputs'][$input];
            
        $tpl = @$input_def['tpl'];        

        if (null === $tpl)
        {
            if (@empty($input_def['models']))
                throw new HgException("Template is not defined in controller variable \$forms definition and no models given!");
            else if (sizeof($input_def['models'])>1)
                throw new HgException("Template is not defined in controller variable \$forms definition and more than one models given!");
            else if (sizeof(reset($input_def['models']))>1)
                throw new HgException("Template is not defined in controller variable \$forms definition and more than one field of a model given!");
        }

        $data = @ $this->__ctrl->data[$this->__short_ident][$input];
        $errors = $this->_getErrors($input);

        /*
        $id0 = $id = g('Functions')->ASCIIfyText();
        $suffix = '';
        do
        {
            if (!isset(self::$_used_ids[$id]))
                break;
            $suffix++;
            $id = $id0.'__'.$suffix;
        }
        while(true);
        self::$_used_ids[$id] = true;
         */
        $id = g('Functions')->uniqueId($this->__ident.'_'.$input);
            
        $sys_params = array('ident'=>$this->__ident,
                            'input'=>$input,
                            'id' => $id,
                            'input_def' => $input_def,
                            'data'=>$data,
                            'errors'=>$errors,
                            'ajax'=>isset($this->__form['ajax']) ? $this->__form['ajax'] : USE_AJAX_BY_DEFAULT
                        );
        $params = array_merge($sys_params, $additional_params);

        if($tpl!==null)
            $ret = $this->__ctrl->inc($tpl, $params);
        else
        {
            // only one item in [models]
            list($field) = reset($input_def['models']);
            $model = key($input_def['models']);
            $ret = $this->__ctrl->inc('Forms/'.g($model, 'model')->getField($field)->type(),$params);
        }

        return $ret;
    }

    /**
     * Wrapper for deprecated method name.
     * Created 2009-09-10
     * @author m.augustynowicz
     */
    public function create_all($action='')
    {
        g()->debug->addInfo('deprecated Forms::create_all()', true,
                'Method '.__CLASS__.__FUNCTION__
                . '() is deprecated due it\' incorrect name and will be removed &#8212; update your code' );
        return $this->createAll($action);
    }
    
    public function createAll($action='')
    {
        echo "<div>";
        $this->create($action);
        foreach($this->__form['inputs'] as $input => $data)
        {
            echo "<div style='width:500px;text-align:right;'><span>$input: </span>";
            $type = $this->__model->getField($input)->type().'?';
            if (isset($data['tpl']))
                $type .= " ({$data['tpl']})";
            printf('<small>(%s)</small>', $type);
            $this->input($input);
            echo "</div>";
        }
        echo "<input type='hidden' name='ds' value='".$this->__form['model']."' >";
        echo "<input type='hidden' name='ident' value='".$this->__short_ident."' >";
        echo "<input type='submit' value='send' >";
        echo "</div>";
        $this->end();
    }

    /**
     * Gets form's id.
     *          
     * @return string $this__ident value
     */         
    public function getIdent()
    {
        return $this->__ident;
    }

    public function getShortIdent()
    {
        return $this->__short_ident;
    }

    /**
     * Retrieves form's errors.
     * @author m.augustynowicz
     * @param boolean $flush delete after retrieving
     * @return array
     */
    public function getErrors($flush=true)
    {
        return $this->_getErrors('0', $flush);
    }

    /**
     * Retrieves error messages for given input from Kernel's infos
     * @author m.augustynowicz
     *
     * @param string $input input name, "0" is special value for the whole form
     * @param boolean $flush delete after retrieving
     * @return array
     */
    protected function _getErrors($input="0", $flush=true)
    {
        if (@empty(g()->infos['forms']))
            return array();

        $errors = array();

        $err_prefix = "{$this->__ident} {$input} ";
        $err_prefix_len = strlen($err_prefix);
        foreach (g()->infos['forms'] as $info_id => $info)
        {
            // I guess that's faster that preg_match()
            if (substr($info_id,0,$err_prefix_len) !== $err_prefix)
                continue;

            $errors[substr($info_id,$err_prefix_len)] = $info;
            if ($flush)
                unset(g()->infos['forms'][$info_id]);
        }

        if ($flush && empty(g()->infos['forms']))
            unset(g()->infos['forms']);

        return $errors;
    }

}

