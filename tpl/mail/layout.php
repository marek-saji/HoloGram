<?php
if (!$this->getAssigned('mail_subject'))
{
    $this->assign('mail_subject', $this->trans('Mail from %s', g()->conf['site_name']));
}
?>
<?=$mail_content?>
