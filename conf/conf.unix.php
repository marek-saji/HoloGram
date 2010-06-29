<?php
/**
 * unix tools
 *
 * each of conf[unix][COMMAND] can be:
 * boolean -- just determine if COMMAND is available;
 * array -- it's available, you can set additional params:
 *      [path] -- path to use
 *      [args] -- additional params (remember to escapeshellarg()!)
 *      [args_args] -- if present, users' args will be replaced by
 *          the result of vsprintf([user_args], [args_args])
 */

// file(1)

$conf['unix']['file'] = true;


// ffmpeg(1)

// audio/* to mp3
$conf['unix']['ffmpeg-mp3'] = array(
    'path' => 'ffmpeg',
    'args' => '-y',
    'args_args' => array(
        '-f mp3 -acodec libmp3lame'
    )
);
// video/* to mp4
$conf['unix']['ffmpeg-mp4'] = array(
    'path' => 'ffmpeg',
    'args' => '-y',
    'args_args' => array(
        // based on http://flowplayer.org/forum/7/12671
        '-f mp4 -vcodec libx264 -me_method hex -me_range 18 -subq 7 -qmin 20 -qmax 51 -qcomp 0.7 -acodec libfaac -ab 80kb -ar 48000 -ac 2'
    )
);
// video/* to ogv
$conf['unix']['ffmpeg2theora'] = array(
    'path' => 'ffmpeg2theora'
);
// video/* to jpeg (one frame)
$conf['unix']['ffmpeg-jpeg'] = array(
    'path' => 'ffmpeg',
    'args' => '-y',
    'args_args' => array(
        '-f mjpeg'
    )
);


// figlet(1), with fallbacks
$conf['unix']['figlet'] = array(
    // first try figlet(1), then toilet(1) and fall back to plain echo
    'path' => '$(which figlet || which toilet || echo echo)'
);

