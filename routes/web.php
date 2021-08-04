<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

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

Auth::routes(['verify' => true, 'register' => config('anonaddy.enable_registration')]);
Route::get('/username/reminder', 'Auth\ForgotUsernameController@show')->name('username.reminder.show');
Route::post('/username/email', 'Auth\ForgotUsernameController@sendReminderEmail')->name('username.email');

Route::post('/login/2fa', 'Auth\TwoFactorAuthController@authenticateTwoFactor')->name('login.2fa')->middleware(['2fa', 'throttle', 'auth']);

Route::get('/login/backup-code', 'Auth\BackupCodeController@index')->name('login.backup_code.index');
Route::post('/login/backup-code', 'Auth\BackupCodeController@login')->name('login.backup_code.login');

Route::group([
    'middleware' => config('webauthn.middleware', []),
    'domain' => config('webauthn.domain', null),
    'prefix' => config('webauthn.prefix', 'webauthn'),
], function () {
    Route::get('keys', 'Auth\WebauthnController@index')->name('webauthn.index');
    Route::post('register', 'Auth\WebauthnController@create')->name('webauthn.create');
    Route::delete('{id}', 'Auth\WebauthnController@destroy')->name('webauthn.destroy');
    Route::post('enabled-keys', 'Auth\WebauthnEnabledKeyController@store')->name('webauthn.enabled_key.store');
    Route::delete('enabled-keys/{id}', 'Auth\WebauthnEnabledKeyController@destroy')->name('webauthn.enabled_key.destroy');
});

Route::middleware(['auth', 'verified', '2fa', 'webauthn'])->group(function () {
    Route::get('/', 'ShowAliasController@index')->name('aliases.index');

    Route::get('/recipients', 'ShowRecipientController@index')->name('recipients.index');
    Route::post('/recipients/email/resend', 'RecipientVerificationController@resend');

    Route::get('/domains', 'ShowDomainController@index')->name('domains.index');
    Route::get('/domains/{id}/check-sending', 'DomainVerificationController@checkSending');

    Route::get('/usernames', 'ShowAdditionalUsernameController@index')->name('usernames.index');

    Route::get('/deactivate/{alias}', 'DeactivateAliasController@deactivate')->name('deactivate');

    Route::get('/rules', 'ShowRuleController@index')->name('rules.index');

    Route::get('/failed-deliveries', 'ShowFailedDeliveryController@index')->name('failed_deliveries.index');
});


Route::group([
    'middleware' => ['auth', '2fa', 'webauthn'],
    'prefix' => 'settings'
], function () {
    Route::get('/', 'SettingController@show')->name('settings.show');
    Route::post('/account', 'SettingController@destroy')->name('account.destroy');

    Route::post('/default-recipient', 'DefaultRecipientController@update')->name('settings.default_recipient');
    Route::post('/edit-default-recipient', 'DefaultRecipientController@edit')->name('settings.edit_default_recipient');

    Route::post('/default-alias-domain', 'DefaultAliasDomainController@update')->name('settings.default_alias_domain');

    Route::post('/default-alias-format', 'DefaultAliasFormatController@update')->name('settings.default_alias_format');

    Route::post('/from-name', 'FromNameController@update')->name('settings.from_name');

    Route::post('/email-subject', 'EmailSubjectController@update')->name('settings.email_subject');

    Route::post('/banner-location', 'BannerLocationController@update')->name('settings.banner_location');

    Route::post('/catch-all', 'CatchAllController@update')->name('settings.catch_all');

    Route::post('/use-reply-to', 'UseReplyToController@update')->name('settings.use_reply_to');

    Route::post('/password', 'PasswordController@update')->name('settings.password');

    Route::delete('/browser-sessions', 'BrowserSessionController@destroy')->name('browser-sessions.destroy');

    Route::post('/2fa/enable', 'Auth\TwoFactorAuthController@store')->name('settings.2fa_enable');
    Route::post('/2fa/regenerate', 'Auth\TwoFactorAuthController@update')->name('settings.2fa_regenerate');
    Route::post('/2fa/disable', 'Auth\TwoFactorAuthController@destroy')->name('settings.2fa_disable');

    Route::get('/aliases/export', 'AliasExportController@export')->name('aliases.export');
});
