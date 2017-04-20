<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::group(['prefix' => 'voice', 'middleware' => 'twilio'], function () {
    Route::post('enterZipcode', 'VoiceController@showEnterZipcode')->name('enter-zip');

    Route::post('zipcodeWeather', 'VoiceController@showZipcodeWeather')->name('zip-weather');

    Route::post('dayWeather', 'VoiceController@showDayWeather')->name('day-weather');

    Route::post('credits', 'VoiceController@showCredits')->name('credits');
});

Route::group(['prefix' => 'sms', 'middleware' => 'twilio'], function () {
    Route::any('weather', 'SmsController@showWeather')->name('weather');
});