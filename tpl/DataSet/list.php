<?php
foreach ($libs as $title => $path_data)
{
    if (!$title)
        $title = 'Local';
    else if ($title == HG_DIR)
        $title = 'HoloGram';

    if ($path_data['alias'])
        $title .= ' <small>('.$path_data['alias'].')</small>';

    printf('<h3>%s models</h3>', $title);

    echo '<ul>';
    if (!$path_data['models'])
        echo '<li><em>no models found</em></li>';
    else
    {
        foreach ($path_data['models'] as $name => $model)
        {
            if($model['class'] != 'abstract')
                printf('<li class="%s"><span class="name">%s</span>, %s</li>',
                       $model['class'],
                       $t->l2a($name, 'show', array($name)),
                       $t->l2a('[+]', 'add', array($name), array('title'=>'insert row'))
                      );
            else
                echo '<li class="asbtract"><span class="name">'.$name.'</span></li>';
        }
    }
    echo '</ul>';

}
?>

<small>
    <h3>legend:</h3>
    <ul>
        <li class="abstract "><span class="name">abstract </span></li>
        <li class="correct  "><span class="name">correct  </span></li>
        <li class="missing  "><span class="name">missing  </span></li>
        <li class="incorrect"><span class="name">incorrect</span></li>
    </ul>
</small>

