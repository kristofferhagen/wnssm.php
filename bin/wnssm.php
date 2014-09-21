#!/usr/bin/env php
<?php

use KristofferHagen\Wnssm\WnssmApplication;

require __DIR__ . '/../vendor/autoload.php';

$application = new WnssmApplication();
$application->run();
