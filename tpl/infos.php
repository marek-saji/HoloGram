<?php
$classy_infos = & g()->infos;

if (!g()->debug->allowed())
    unset($classy_infos['debug']);
else
{
    if (!@empty($classy_infos['forms']))
        array_unshift($classy_infos['forms'], '(these infos should not be displayed here!)');
}

if (!$classy_infos)
    return;

print '<div id="infos">';
foreach ($classy_infos as $class => $infos)
{
    if (empty($infos))
        continue;
    printf('<ol class="%s">', urlencode($class));
    foreach ($infos as $ident => $info)
    {
        if (g()->debug->allowed() && !is_int($ident))
            $info = sprintf('%s <small>(info id: %s)</small>', $info, $ident);
        printf('<li id="info_%s_%s">%s</li>', 
                urlencode($class), urlencode($ident), $info);
    }
    printf('</ol>');
}
print '</div>';

$classy_infos = array();

