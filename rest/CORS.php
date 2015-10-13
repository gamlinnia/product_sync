<?php

$app->response->headers->set('Access-Control-Allow-Origin', '*');
$app->response->headers->set('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept');
$app->map('/:x+', function($x) {
    global $app;
    $app->response->setStatus(200);
})->via('OPTIONS');