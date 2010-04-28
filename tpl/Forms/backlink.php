<?php
/**
 * Link "back" -- page that you landed to this form from
 * @author m.augustynowicz
 *
 * (parameters passed as assigned local variables)
 * @param string $cancel_label text on cancel button, defaults to "cancel",
 *        link will not get displayed if empty string given
 * @param array $cancel_link one of these:
 *        true -- use referer
 *        false|'' -- don't display
 *        string -- use literally
 *        array describing url to controller:
 *        [0] controller
 *        [1] action
 *        [2] params
 *        or array describing url to action:
 *        [0] action
 *        [1] params
 */
extract(array_merge(
        array(
            'cancel_label'  => 'cancel',
            'cancel_link'   => true, // use referer
        ),
        (array) $____local_variables
    ));

if (true === $cancel_link)
{
    if (!$cancel_link = isset($data) ? $data : g()->req->getReferer())
        $cancel_link = $t->url2c(''); // main page
}
elseif (is_array($cancel_link))
{
    if (2 == sizeof($cancel_link))
        $cancel_link = $t->url2a($cancel_link[0], $cancel_link[1]);
    else
        $cancel_link = $t->url2c($cancel_link[0], $cancel_link[1], $cancel_link[2]);
}
if ($cancel_link && $cancel_label)
    printf('<a href="%s" class="modalClose cancel_label">%s</a>', $cancel_link, $t->trans($cancel_label));

