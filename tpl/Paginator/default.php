<?php
/**
 * Pagination
 *
 * There is lot's of ways to do that. There is some hash-commented code
 * left as an examples.
 * @author m.augustynowicz
 *
 * @uses $this->_count
 * @uses $this->_perpage
 * @uses $this->_total_pages
 * @uses $this->_current_page
 */

$v->addCss($t->file('paginator', 'css'));

// we will show $neighbour pages on the left and on the right of current page
$neighbour = 1;

if (!$t->_count || $t->_total_pages <= 1)
{
    echo '<div class="empty paginator"></div>';
    return 0;
}
?>

<?php

$total_pages = $t->_count/$t->_perpage;

$start = max($this->_current_page - $neighbour, 1) - 1;
$stop  = min($this->_current_page + $neighbour, $this->_total_pages) + 1;
if (($tmp = $this->_current_page - $start) < $neighbour)
{
    $stop = min($stop+($neighbour-$tmp), $this->_total_pages+1);
}
if (($tmp = $stop - $this->_current_page) < $neighbour)
{
    $start = max($start-($neighbour-$tmp), 0);
}

// generate pages list

#$neighbour = 0;
#$pages = array(
#    $this->trans('%d of %d', $this->_current_page, $this->_total_pages) => $t->_current_page
#);
$pages = array(
    $t->_current_page => $t->_current_page
);
#for (list($a,$b)=array(0,1) ; $neighbour > $c = $a+$b ; $a=$b, $b=$c) // fibbonachi, yay!
for ($c = 1 ; $neighbour >= $c ; $c++)
{
    if ($stop  > $x = $t->_current_page + $c)
        $pages[$x] = $x;
    if ($start < $x = $t->_current_page - $c)
        $pages[$x] = $x;
}
asort($pages);


// first/prev/next/last

$label_first = 'first page';
$label_prev  = $f->tag(
    'code', array(
        'class' => 'symbol',
        'title' => $this->trans('previous page')
    ),
    '&#8678;'
);
$label_next  = $f->tag(
    'code', array(
        'class' => 'symbol',
        'title' => $this->trans('next page')
    ),
    '&#8680;'
);
$label_last  = 'last page';

$ppages = array();
$npages = array();
#$ppages[$label_first] = 1;
$ppages[$label_prev] = $t->_current_page - 1;
if (reset($pages) != 1) // first page is not "1" -- add it
{
    $ppages[1] = 1;
    if (current($pages) != 1+1) // now there is a gap, add "..."
    {
        $ppages['&hellip;'] = null;
    }
}
if (end($pages) != $t->_total_pages) // last page is not "{max}", add it
{
    if (current($pages) != $t->_total_pages - 1) // now there would be a gap, add "..."
    {
        // that space is a drity trick to avoid overwriting "..." from $ppages
        $npages['&hellip; '] = null;
    }
    $npages[$t->_total_pages] = $t->_total_pages;
}
$npages[$label_next] = $t->_current_page+1;
#$npages[$label_last] = $t->_total_pages;

//$pages = $ppages + $pages + $npages;


// display!
?>
<div class="paginator">
    <?php foreach (array($ppages, $pages, $npages) as $pagesgroup) : ?>
        <ul>
            <?php foreach ($pagesgroup as $label => $page) : ?>
                <?php
                $current = $page == $t->_current_page;
                $out_of_boundry = $page<1 || $page>$t->_total_pages;

                $classes = array('page', $f->isInt($label)?'num':'text');
                if ($current)
                    $classes[] = 'active';
                if ($out_of_boundry)
                    $classes[] = 'disabled';
                if ($page == 1)
                    $classes[] = 'first';
                else if ($page == $t->_total_pages)
                    $classes[] = 'last';
                if ($label === $label_prev)
                    $classes[] = 'prev';
                else if ($label === $label_next)
                    $classes[] = 'next';
                ?>

                <li class="<?=join(' ',$classes)?>">
                    <?php if ($current) : ?>
                        <strong><?=$this->trans($label)?></strong>
                    <?php elseif ($out_of_boundry || null === $page) : ?>
                        <span><?=$this->trans($label)?></span>
                    <?php else : ?>
                        <?=$t->l2cInside($t->trans($label), null, '', array($page))?>
                    <?php endif; /* if else $current */ ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endforeach; ?>
</div> <!-- .paginator -->

<?php
return true;

