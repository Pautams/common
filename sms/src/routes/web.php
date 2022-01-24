<?php
$route = env('PACKAGE_ROUTE', '').'/sms/';
$controller = 'Increment\Common\Sms\Http\SMSController@';
Route::post($route.'send_from_request', $controller."sendFromRequest");
Route::post($route.'send', $controller."send");
