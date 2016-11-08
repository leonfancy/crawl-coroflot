#!/usr/bin/env php

<?php

use Slim\Spider;
use Slim\Stat;

require 'vendor/autoload.php';

$spider = new Spider(new Stat());
$spider->run();
