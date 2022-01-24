<?php
$route = env('PACKAGE_ROUTE', '').'/payloads/';
$controller = 'Increment\Common\Payload\Http\PayloadController@';
Route::post($route.'create', $controller."create");
Route::post($route.'create_with_images', $controller."createWithImages");
Route::post($route.'retrieve', $controller."retrieve");
Route::post($route.'retrieve_all', $controller."retrieveAll");
Route::post($route.'retrieve_subscriptions', $controller."retrieveSubscriptions");
Route::post($route.'retrieve_with_validations', $controller."retrieveWithValidation");
Route::post($route.'retrieve_with_images', $controller."retrieveWithImage");
Route::post($route.'retrieve_by_id', $controller."retrieveById");
Route::post($route.'update', $controller."update");
Route::post($route.'update_with_images', $controller."createWithImages");
Route::post($route.'delete', $controller."delete");
Route::post($route.'delete_with_images', $controller."removeWithImage");
Route::post($route.'get_valid_id', $controller."checkValidId");
Route::post($route.'upload_valid_id', $controller."uploadValidId");
Route::post($route.'create_faqs', $controller."createFaqs");
Route::post($route.'create_category', $controller."createCategory");
Route::post($route.'get_category', $controller."getCategory");
Route::post($route.'get_resource', $controller."getResource");
Route::post($route.'create_industry', $controller."createIndustry");
Route::post($route.'faqs', $controller."retrieveFaqs");
Route::post($route.'create_currency', $controller."createCurrency");
Route::post($route.'get_currency', $controller."getCurrency");
Route::post($route.'email_preverify', $controller."preVerifyEmail");
Route::get($route.'test', $controller."test");
// Route::post($route.'retrieveRecipients', $controller."retrieveRecipients");