<?php

Route::group(['middleware' => ['web'], 'namespace' => '\Top20ofe\TwoFactorAuthentication\Http\Controllers'], function () {
    Route::get('verify-2fa', 'TwoFactorAuthenticationController@verifyTwoFactorAuthentication')->name('verify-2fa');
    Route::post('verify-2fa', 'TwoFactorAuthenticationController@verifyToken');
    Route::get(config('2fa-config.setup_2fa'), 'TwoFactorAuthenticationController@setupTwoFactorAuthentication')->name('setup-2fa');
    Route::post(config('2fa-config.enable_2fa'), 'TwoFactorAuthenticationController@enableTwoFactorAuthentication');
    Route::post(config('2fa-config.disable_2fa'), 'TwoFactorAuthenticationController@disableTwoFactorAuthentication');
});
