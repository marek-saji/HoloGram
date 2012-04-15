HoloGram
========

HoloGram is a PHP framework designed by developers for developers
with ease of debugging in mind.

Created, but no longer actively developed by
[HolonGlobe](http://holonglobe.com).



Setting up a new app
--------------------

1. Prepate directory structure:

   - `conf/`   here you will store your local configuration files
   - `src/`    here you will put source code in a moment
   - `upload/` here all uploaded files will be stored

2. Clone HoloGram into `src/hg/`

       git clone https://github.com/HolonGlobe/HoloGram.git src/hg/

3. Copy application scheme into `src/app/`

       cp -rv src/hg/app-scheme/app src/

4. Follow instructions from `src/app/README.markdown`


### Make your developement easier

1. Copy `conf.debug.php` to your local config directory.

   You may edit `conf[debug][shortcuts]` and set links to commonly
   visited URLs, both in-site (e.g. `array('Admin', 'loginAs',
   array('test-user'))`) and links to different sites (bug tracker
   etc).

