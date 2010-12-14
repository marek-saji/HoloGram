<?php
/**
 * Pages controller.
 * Controller will present one of multiple pages. Each is accessed with a specific action. 
 */
class PagesController extends Component
{
    public static $singleton=true;
    protected $_action = 'default';
    protected $_layout = 'main';
    protected $_params = array();
    protected $_convert_from = 'ÀÁÂÃÄÅĀĂĄǍǺÆǼÇĆĈĊČĎĐÐĒĔĖĘĚÈÉÊËĜĞĠĢĤĦÌÍÎĨĪĬĮİÏǏĴĶĹĻĽĿŁÑŃŅŇÒÓÔÕÖŌŎŐǑǾØŒŔŖŘŚŜŞŠŢŤŦÙÚÛÜŨŪŬŮŰŲǓǕǗǙǛŴÝŶŸŹŻŽ';
    protected $_convert_to = 'AAAAAAAAAAAAACCCCCDDDEEEEEEEEEGGGGHHIIIIIIIIIIJKLLLLLNNNNOOOOOOOOOOOORRRSSSSTTTUUUUUUUUUUUUUUUWYYYZZZ';
    protected $_views_layouts = array('View' => 'main', 'AjaxView' => 'main_ajax');

    public function present()
    {
        $layout = & $this->_views_layouts[get_class(g()->view)];
        if (null === $layout)
            $layout = $this->_layout;

        // lowercase last in path

        $layout_arr = explode('/', $layout);
        $last = strtolower(end($layout_arr));
        $layout_arr[key($layout_arr)] = $last;

        $this->_layout = implode('/',$layout_arr);
        $this->inc($this->_layout);
    }
}
