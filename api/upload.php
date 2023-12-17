<?php

include_once __DIR__.'/../FileSubmitController.php';

$fsc = new FileSubmitController();
$resp = $fsc->dataSubmit(array_merge($_REQUEST,$_FILES));

// Set the Content-Type header to JSON
header('Content-Type: application/json');
print_r($resp);