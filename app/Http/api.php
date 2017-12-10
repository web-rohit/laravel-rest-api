<?php
header('Access-Control-Allow-Origin:  *');
header('Access-Control-Allow-Methods:  POST, GET, OPTIONS, PUT, DELETE, PATCH');
header('Access-Control-Allow-Headers:  Content-Type, X-Auth-Token, Origin, Authorization');
header("Content-Type: application/json", true);

Route::group(['prefix' => 'api/v1', 'middleware' => ['domainCheck']], function () {
	//\Log::info("in loop");   
	Route::get('login/{email}', array('uses' => 'Api\RestApiController@verifyemail'));
    //Route::get('login/{email}', 'Api\RestApiController@verifyemail');
    Route::post('register', 'Api\RestApiController@register');
	 Route::get('getsizes/{product_id}', 'Api\RestApiController@getproductsize');

    Route::group(['middleware' => ['auth:api']], function () {
       // Route::get('getsizes', 'Api\RestApiController@getproductsize');
    });
});