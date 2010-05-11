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
$conf['unix'] = array(
    'ffmpeg-mp3' => array(
        'path' => 'ffmpeg',
        'args' => '-y',
        'args_args' => array(
            '-f mp3 -acodec libmp3lame'
        )
    ),
    'ffmpeg-mp4' => array(
        'path' => 'ffmpeg',
        'args' => '-y',
        'args_args' => array(
            '-f mp4 -acodec libmp3lame -vcodec mpeg4'
        )
    ),
    'ffmpeg-jpeg' => array(
        'path' => 'ffmpeg',
        'args' => '-y',
        'args_args' => array(
            '-f mjpeg'
        )
    ),
);

