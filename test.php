<?php

use CSVTimeseries\Writer;

include __DIR__ . "/vendor/autoload.php";

$writer = new \CSVTimeseries\Writer;
$writer->to(__DIR__ . '/tests/data/');

$writer->add(['hello', 'world', '!']);