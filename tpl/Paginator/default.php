<?php
/**
 * Pagination
 * @author m.augustynowicz
 *
 * @uses $this->_count
 * @uses $this->_perpage
 * @uses $this->_total_pages
 * @uses $this->_current_page
 */

$v->addCss($t->file('paginator', 'css'));

// we will show $neighbour pages on the left and on the right of current page
$neighbour = 5;

if (!$t->_count || $t->_total_pages <= 1)
{
    echo '<div class="empty paginator"></div>';
    return 0;
}
?>

<div class="paginator">
<?php

$total_pages = $t->_count/$t->_perpage;

$start = max($this->_current_page - $neighbour, 0);
$stop  = min($this->_current_page + $neighbour, $this->_total_pages+1);
if (($tmp = $this->_current_page - $start) < $neighbour)
{
    $stop = min($stop+($neighbour-$tmp), $this->_total_pages+1);
}
if (($tmp = $stop - $this->_current_page) < $neighbour)
{
    $start = max($start-($neighbour-$tmp), 0);
}

// generate pages list

$pages = array($t->_current_page => $t->_current_page);
#for (list($a,$b)=array(0,1) ; $neighbour > $c = $a+$b ; $a=$b, $b=$c) // fibbonachi, yay!
for ($c = 0 ; $neighbour*3 > $c++ ;)
{
    if ($stop  > $x = $t->_current_page + $c)
        $pages[$x] = $x;
    if ($start < $x = $t->_current_page - $c)
        $pages[$x] = $x;
}
asort($pages);


// first/prev/next/last

$label_first = 'first page';
$label_prev = '&lt;';
$label_next = '&gt;';
$label_last = 'last page';

$ppages = array();
$npages = array();
//$ppages[$label_first] = 1;
$ppages[$label_prev] = $t->_current_page-1;
if (reset($pages) != 1)
{
    unset($pages[key($pages)]);
    reset($pages);
    $ppages[1] = 1;
    if (current($pages) != 1+1)
    {
        unset($pages[key($pages)]);
        $ppages['&hellip;'] = null;
    }
}
if (end($pages) != $t->_total_pages)
{
    if (current($pages) != $t->_total_pages - 1)
    {
        unset($pages[key($pages)]);
        end($pages);
        $npages['&hellip;'] = null;
    }
    unset($pages[key($pages)]);
    $npages[$t->_total_pages] = $t->_total_pages;
}
$npages[$label_next] = $t->_current_page+1;
//$npages[$label_last] = $t->_total_pages;

// and merge hem wit $pages
foreach ($pages as $k=>$p)
    $ppages[$k] = $p;
foreach ($npages as $k=>$p)
    $ppages[$k] = $p;
$pages = $ppages;


// display!

foreach ($pages as $label => $page)
{
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

    printf('<span class="%s">', join(' ',$classes));
    if ($current)
        printf('<strong>%s</strong>', $t->trans($label));
    elseif ($out_of_boundry || null === $page)
        printf('<span>%s</span>', $t->trans($label));
    else
        printf('%s', $t->l2cInside($t->trans($label), null, '', array($page)), $label);
    echo '</span>';
}
?>
</div> <!-- .paginator -->

<?php
return true;

