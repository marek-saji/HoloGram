Installation
------------

1. prepare upload directory:

       mkdir -m 0700 upload/
       chown www-data:www-data upload/

1. local configuration

  1. database credentials

         cp src/app/conf/conf.db.php conf/
         edit conf/conf.db.php

  1. API keys

         cp src/app/conf/conf.keys.php conf/
         edit conf/conf.keys.php

1. configure apache

  1. create vhost pointing to $PWD/src/app/htdocs/
  1. create alias 'hg/' poining to $PWD/src/hg/htdocs/
  1. allow .htaccess magic tricks
  1. make sure `mod_rewrite` is on

1. http://host.name/, enable favourite debugs

1. head to /DataSet/list and create all models

1. head to /Dev and launch `Setup` action

1. in production environment

   1. switch to production robots.txt

          mv src/app/htdocs/robots{.prod,}.txt

