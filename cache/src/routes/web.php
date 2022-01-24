<?php
$route = env('PACKAGE_ROUTE', '').'/cache/';
$controller = 'Increment\Common\Cache\Http\CacheController@';

Route::post($route.'create', $controller."insert");
Route::post($route.'retrieve', $controller."retrieveFromRequest");