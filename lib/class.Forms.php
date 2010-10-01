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
 *                          // declarations of available input fields
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
            {
                $this->__form['inputs'][$fname]['models'][$this->__form['model']][] = $fname;
            }
                
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
            'id' => $f->uniqueId(),
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
        static $rendered = array();
        if (true === @$rendered[$input])
        {
            trigger_error("Re-rendering $input form input.", E_USER_WARNING);
        }
        $rendered[$input] = true;

        $input_def = $this->_getInput($input);

        $data = @ $this->__ctrl->data[$this->__short_ident][$input];
        $errors = $this->_getErrors($input);

        $hg_params = array(
            'input_def' => $input_def,
            'ident'     => $input_def['form_ident'],
            'input'     => $input_def['input_name'],
            'id'        => $input_def['id'],
            'ajax'      => $input_def['ajax'],
            'data'      => $data,
            'errors'    => $errors,
        );
        $params = array_merge($hg_params, $additional_params);

        return $this->__ctrl->inc($input_def['tpl'], $params);
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


    /**
     * Get a complete input field definition
     * @author m.augustynowicz
     *
     * @param string $name valid input field name
     *
     * @return array input field definition containing:
     *         - [form_ident]
     *         - [input_name]
     *         - [models]
     *         - [tpl]
     *         - [id]
     *         - [ajax]
     */
    protected function _getInput($name)
    {
        $input_def = & $this->__form['inputs'][$name];

        if (null === $this->__form['inputs'][$name])
        {
            throw new HgException("Input `$name' not defined in controller variable \$forms!");
        }

        if (true === @$input_def['_generated'])
        {
            return $input_def;
        }


        $input_def['input_name'] = $name;

        $models = & $input_def['models'];

        $tpl = & $input_def['tpl'];
        if (null === $tpl)
        {
            $ctrl_class = get_class($this->__ctrl);
            $form_def = "{$ctrl_class}::\$forms[{$this->__short_ident}][{$name}]";

            $error_prefix = "Template is not defined in {$form_def} definition and ";
            if (empty($models))
            {
                throw new HgException($error_prefix . 'no models given.');
            }
            else if (sizeof($models) > 1)
            {
                throw new HgException($error_prefix . 'more than one models given.');
            }
            $fields = reset($models);
            if (sizeof($fields) > 1)
            {
                throw new HgException($error_prefix . 'more than one field of a model given.');
            }
            unset($err_prefix);

            $model_name = key($models);
            $field_name = reset($fields);
            $model = g($model_name, 'model');
            $field = $model[$field_name];
            if (!$field)
            {
                throw new HgException("{$form_def} supposed to render {$model_name}'s {$field_name} field, but it does not exist.");
            }
            $tpl = 'Forms/' . $field->type();
        }

        $input_def['form_ident'] = $this->getIdent();

        if (isset($this->__form['ajax']))
            $input_def['ajax'] = $this->__form['ajax'];
        else
            $input_def['ajax'] = USE_AJAX_BY_DEFAULT;

        $input_def['id'] = $input_def['input_name']
                . '_' . g('Functions')->uniqueId();


        $input_def['_generated'] = true;

        unset($tpl);
        unset($models);

        return $input_def;
    }

}

