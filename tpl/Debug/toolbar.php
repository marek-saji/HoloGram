<?
if ('View' !== get_class($v))
    return; // only HTML view can handle this mighty toolbar!


$v->addCss($this->file('debug','css'));
?>
<div id="debug_toolbar" style="background-color:#cca; width:100%; border:thin solid #886; border-width: thin 0;">
    <dl id="debug_info">
        <dt>app v</dt>
        <dd><?=g()->conf['version']?></dd>
    </dl>
    <div id="debug_switcher">
        <h4>debug:</h4>
        <?php
          if (g()->debug->allowed())
              echo $this->l2a('don\'t','Off') . '; ';
          else
          {
              echo $this->l2a('permit','On') . '; ';
              printf('<small><em>World Health Organization warns: <q>developing with debug-mode disabled is <strong>un<a target="_blank" href="http://42.pl/u/2juC">awesome</a></strong>!</q></em></small>');
          }

          if (g()->debug->allowed())
          {
              $enabled = array_keys((array)$_SESSION[g()->conf['SID']]['DEBUG']);
              foreach ($enabled as $k => &$v)
              {
                  switch ($v)
                  {
                      case 'nonconnecteddb' :
                          $v = 'db';
                          break;
                  }
                   if (!$v)
                      unset($enabled[$k]); // get rid of 'global debug'
                  else
                      $v = sprintf('<a href="%s" title="click to disable">%s</a>',$this->url2a("set",array($v=>"off")),$v);
              }
              if (!empty($enabled))
                  printf('enabled<small title="showing only 1st level &#8212; see $_SESSION['.g()->conf['SID'].'][DEBUG] for details">(?)</small>: %s', join(',',$enabled));
              else
                  printf('nothing enabled');

              printf(' <button onclick="if (x=prompt(\'e.g.\ndb=true,js=0,user,item.controller=1,item.class=0\ndisable.gmaps to disable google maps\ndisable.externalcdn to disable external CDNs (jQuery etc)\sdisable.uniform to disable uniformed forms\ntrans.missing to highlight missing translations\nall=0 to reset\')) window.location.href=(\'%s\'.replace(\'__here__\',x))">change</button>', $this->url2a('set',array('__here__')));
              echo '; ';
              print((g()->debug->get()?$this->l2a('disable global',"set",array('global'=>'off')):$this->l2a('enable global','set',array('global'=>'on'))));
          }
          ?>
    </div> <!-- #debug_switcher -->
    <div id="debug_toolbox">
        <h4>toolbox</h4>
        <ul>
            <?php if (g()->debug->allowed()) : ?>
            <li><?=$this->l2c('data sets', 'DataSet','list',array(),array('title'=>'manage data sets'))?></li>
            <li><?=$this->l2c('developer controller', 'Dev','',array(),array('title'=>'perform some developer magic'))?></li>
            <?php endif; ?>
            <li>
<?php $this->url2c(array('Debug','On'),array('Debug', 'set', array('fav'))) ?>
            </li>
            <li>
                <?php
                echo $this->l2c(
                    'enable favorite debugs',
                    'Debug/on;Debug/set', // HACK
                    '',
                    array('fav'),
                    array('title'=>'turn on debugs defined in conf[favorite debugs]')
                );
                ?>
            </li>
            <?php if (g()->debug->allowed()) : ?>
            <?php if (g()->debug->on('view')) : ?>
            <li><a href="#debug_inc_tree">templates inclusion tree</a></li>
            <?php endif; ?>
            <li><a href="#debug_superglobals">show superglobals</a></li>
            <?php endif; ?>
            <li><a href="javascript:(function(){function%20l(u,i,t,b){var%20d=document;if(!d.getElementById(i)){var%20s=d.createElement('script');s.src=u;s.id=i;d.body.appendChild(s)}s=setInterval(function(){u=0;try{u=t.call()}catch(i){}if(u){clearInterval(s);b.call()}},200)}l('http://leftlogic.com/js/microformats.js','MF_loader',function(){return!!(typeof%20MicroformatsBookmarklet=='function')},%20function(){MicroformatsBookmarklet()})})();" title="find microformats on this page">microformats</a></li>
        </ul>
        <?php if (@g()->conf['alternative base URLs']) : ?>
            <div>
                <h5>elseworlds</h5>
                <ul>
                    <?php
                    $url = $this->url2c(g()->req->getUrlPath());
                    $here = $this->url2c(g()->req->getUrlPath(), '', array(), true); // with host
                    foreach (g()->conf['alternative base URLs'] as $name => $base)
                    {
                        $there = rtrim($base,'/') . $url;
                        if ($here != $there)
                            printf('<li><a href="%s">%s</a></li>', $there, $name);
                    }
                    ?>
                </ul>
            </div>
        <?php endif; ?>
    </div> <!-- #debug_toolbox -->
</div> <!-- #debug_toolbar -->

