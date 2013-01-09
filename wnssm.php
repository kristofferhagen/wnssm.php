#!/usr/bin/env php
<?php

if (posix_getuid() !== 0) {
	// Make sure we are running as root
	echo "Must be run as root!";
	echo PHP_EOL;
	exit;
}

if ($_SERVER['argc'] < 2) {
    // Show usage information if run without arguments
    echo './wssm.php INTERFACE [--essid=ESSID] [--address=ADDRESS]';
    echo PHP_EOL;
    exit;
}

// Parse named arguments
function options($options)
{
    $options = func_get_args();
    $values  = array();

    for ($i = 1; $i < $_SERVER['argc']; $i++) {
        if ( ! isset($_SERVER['argv'][$i])) {
            break;
        }

        $opt = $_SERVER['argv'][$i];

        if (substr($opt, 0, 2) !== '--') {
            continue;
        }

        $opt = substr($opt, 2);

        if (strpos($opt, '=')) {
            list ($opt, $value) = explode('=', $opt, 2);
        } else {
            $value = NULL;
        }

        if (in_array($opt, $options)) {
            $values[$opt] = $value;
        }
    }

    return $values;
}

$interface = $_SERVER['argv'][1];
$options   = options('essid', 'address');

// Main program
function main($interface, $options)
{
    exec('iwlist '.$interface.' scanning', $out);

    $out = implode("\n", $out);
    $out = str_replace('                    ', '', $out);
    $cells_raw = explode('          Cell ', $out);
    unset($cells_raw[0]);

    $cells = array();

    foreach ($cells_raw as $cell) {
        $cell_id = substr($cell, 0, 2);

        if (preg_match('/Address: ((?:[0-9a-f]{2}[:-]){5}[0-9a-f]{2})/i', $cell, $matches)) {
            $cells[$cell_id]['address'] = $matches[1];
        }

        if (preg_match('/Channel:(\d)/', $cell, $matches)) {
            $cells[$cell_id]['channel'] = $matches[1];
        }

        if (preg_match('/Frequency:(.*)\s\(/', $cell, $matches)) {
            $cells[$cell_id]['frequency'] = $matches[1];
        }

        if (preg_match('/Quality\=([0-9]{2}\/[0-9]{2})/', $cell, $matches)) {
            $cells[$cell_id]['quality'] = $matches[1];
        }

        if (preg_match('/Signal level\=(.*)/', $cell, $matches)) {
            $cells[$cell_id]['level'] = $matches[1];
        }

        if (preg_match('/ESSID:"([^"]+)"/', $cell, $matches)) {
            $cells[$cell_id]['essid'] = $matches[1];
        }
    }

    foreach ($cells as $key => $cell) {
        if ((isset($options['address']) && !in_array($cell['address'], explode('/', $options['address'])))
        ||    (isset($options['essid']) && !in_array($cell['essid'], explode('/', $options['essid'])))) {
            unset($cells[$key]);
            continue;
        }

        $address   = $cell['address'];
        $channel   = $cell['channel'];
        $frequency = $cell['frequency'];
        $quality   = $cell['quality'];
        $essid     = $cell['essid'];

        $quality_parts   = explode('/', $quality);
        $quality_len_max = 24;
        $quality_len     = $quality_parts[1] / $quality_len_max;
        $quality_val     = round($quality_parts[0] / $quality_len);
        $quality_max     = round($quality_parts[1] / $quality_len);
        $quality_min     = 1;
        $quality_str     = NULL;

        for ($i = $quality_min; $i < $quality_val; $i++) {
            $quality_str .= '|';
        }
        for ($i = $quality_max; $i > $quality_val; $i--) {
            $quality_str .= ' ';
        }

        $pos_1 = ($quality_len_max / 3) * 0;
        $pos_2 = ($quality_len_max / 3) * 1;
        $pos_3 = ($quality_len_max / 3) * 2;
        $pos_4 = ($quality_len_max / 3) * 3;

    	// Color signal quality bars
        $quality_str = substr_replace($quality_str, "\033[0m",    $pos_4, 0);
        $quality_str = substr_replace($quality_str, "\033[32;1m", $pos_3, 0);
        $quality_str = substr_replace($quality_str, "\033[33;1m", $pos_2, 0);
        $quality_str = substr_replace($quality_str, "\033[31;1m", $pos_1, 0);

        echo ' ';
        echo $cell['essid'];

        $len = strlen($cell['essid']);
        for ($i = $len; $i < 20; $i++) {
            echo ' ';
        }

        echo $cell['address'];

        echo ' [' . $quality_str . '] ';
        echo $cell['quality'];
        echo ' ';
        echo "\033[1m" . $cell['level'] . "\033[0m";
        echo PHP_EOL;
    }
}

main($interface, $options);