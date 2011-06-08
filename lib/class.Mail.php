<?php
/**
 * Mail wrapper
 * @author m.augustynowicz
 */
class Mail extends HgBase
{

    protected $_renderer = null;



    /**
     * Constructor
     * @param array $args:
     *        - [0] Component pass "$this", used for including templates etc
     */
    public function __construct($args)
    {
        if (!(@$args[0]) instanceof Component)
        {
            trigger_error('Invalid [renderer] passed to Mail', E_USER_ERROR);
        }
        $this->_renderer = $args[0];
    }


    /**
     * Send an e-mail
     * @author m.augustynowicz
     *
     * @todo html content
     *
     * @param string|array $recipients recipient in one of forms:
     *        - string with one recipient address,
     *        - array('recipient address' => 'recipient name', 'recipient address', ...)
     * @param string $subject
     * @param string $content
     * @param string|array $from
     *        - string 'from'
     *        - array('from addres' => 'from name')
     * @param array $options
     *        - [Cc] same format as $recipients
     *        - [Bcc] same format as $recipients
     *        - [Reply-To] same format as $from
     * @param array $headers additional headers
     *
     * @return array|bool valid recipients or false on other errors
     */
    public function sendText($recipients, $subject, $content, $from=null, array $options=array(), array $headers=array())
    {
        // trim headers, newlines will be added later
        foreach ($headers as &$header)
        {
            $header = trim($header);
        }
        unset($header);


        // add common headers (will be overwritten, if other supplied)

        $headers = array(
            'X-Mailer: HoloGram',
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=utf-8'
        ) + $headers;


        // sender and recipients

        $recipients = $this->_normalizeAddr($recipients);

        if ($from)
        {
            $from = $this->_normalizeAddr($from);
            $headers[] = "From: {$from}";
        }


        // $options

        foreach (array('Cc', 'Bcc', 'Reply-To') as $header_name)
        {
            if (array_key_exists($header_name, $options))
            {
                $headers[] = "{$header_name}: "
                             . $this->_normalizeAddr($options[$header_name]);
            }
        }


        if (g()->debug->on())
        {
            echo '<dl style="overflow: auto; border: black dotted thin; padding: 1em">';
            printf('<dt>to</dt><dd>%s</dd>',                    htmlspecialchars($recipients));
            printf('<dt>subject</dt><dd>%s</dd>',               htmlspecialchars($subject));
            printf('<dt>HTML content</dt><dd>%s</dd>',          $content);
            printf('<dt>headers</dt><dd><pre>%s</pre></dd>',    htmlspecialchars(var_export($headers, true)));
            echo '</dl>';
        }

        $headers = implode("\r\n", $headers);

        if (mail($recipients, $subject, $content, $headers))
        {
            return sizeof($recipients);
        }
        else
        {
            return false;
        }
    }


    /**
     * Format address according to RFC2822
     * @see http://www.faqs.org/rfcs/rfc2822.html
     *
     * @param string|array $addresses 'address' or array('address' => 'display name', 'address', ...)
     *
     * @return string
     */
    public function _normalizeAddr($addresses)
    {
        if (!is_array($addresses))
        {
            $addresses = array($addresses);
        }

        foreach ($addresses as $k => &$v)
        {
            if (!is_int($k))
            {
                $v = "\"{$v}\" <{$k}>";
            }
        }

        return implode(', ', $addresses);
    }


    /**
     * Send mail obtaining content and subjet from templates
     * @author m.augustynowicz
     *
     * @param string|array $recipients passed to sendText()
     * @param string $tpl_name name of template.
     *        Templates should be placed under mail subdirectory.
     *        Optional, text (alternative to HTML) content of mail may be placed
     *        in template with ".text" suffix.
     *        In main tempklate you should assign a 'mail_subject' variable
     * @param array $tpl_vars template variables
     * @param string $from passed to sendText()
     * @param array $options in addtition to sendText() options
     *        - [layout] layout template to use instead of mail/layout
     * @param string $headers passed to sendText()
     */
    public function send($recipients, $tpl_name, array $tpl_vars=array(), $from=null, array $options=array(), array $headers=array())
    {
        $defaults = g()->conf['mail']['defaults'];

        $from    or $from    = @$defaults['from'];
        $options or $options = (array) @$defaults['options'];
        $headers or $headers = (array) @$defaults['headers'];


        if (!$lay_html = @$options['layout'])
            $lay_html = 'layout';
        unset($options['layout']);
        $lay_html = 'mail/'.$lay_html;
        $lay_text = $lay_html . '.text';
        if (!$this->_renderer->file($lay_text, 'tpl', false))
            $lay_text = $lay_html;


        $tpl_html = 'mail/'.$tpl_name;
        $tpl_text = $tpl_html . '.text';


        // html content

        ob_start();
        $this->_renderer->inc($tpl_html, $tpl_vars);
        $content_html = ob_get_clean();

        // html layout

        ob_start();
        $this->_renderer->inc($lay_html, $tpl_vars + array('mail_content'=>$content_html));
        $content_html = ob_get_clean();
        // warn about empty mail content
        if (empty($content_html))
        {
            trigger_error('Sending e-mail with empty (html) content',
                          E_USER_WARNING );
        }

        // subject

        $subject = $this->_renderer->getAssigned('mail_subject');
        $subject or $subject = @$defaults['subject'];
        $subject = strip_tags($subject);
        // warn about empty mail subject
        if (empty($subject))
        {
            trigger_error('Sending e-mail with empty subject', E_USER_WARNING);
        }

        // text content (optional)

        if (!$this->_renderer->file($tpl_text, 'tpl', false))
        {
            $content_text = null;
        }
        else
        {
            // text content
            ob_start();
            $this->_renderer->inc($tpl_text, $tpl_vars);
            $content_text = ob_get_clean();

            // text layout
            ob_start();
            $this->_renderer->inc($lay_text, $tpl_vars + array('mail_content'=>$content_text));
            $content_text = ob_get_clean();

            // warn about empty mail content
            if (empty($content_text))
            {
                trigger_error('Sending e-mail with empty (text) content',
                              E_USER_WARNING );
            }
        }

        return $this->sendText($recipients, $subject, $content_html, $from, $options, $headers);
    }

}

