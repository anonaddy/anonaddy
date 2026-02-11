<?php

use App\Http\Controllers\AliasExportController;
use App\Http\Controllers\AliasImportController;
use App\Http\Controllers\AliasSeparatorController;
use App\Http\Controllers\Auth\ApiAuthenticationController;
use App\Http\Controllers\Auth\BackupCodeController;
use App\Http\Controllers\Auth\ForgotUsernameController;
use App\Http\Controllers\Auth\PersonalAccessTokenController;
use App\Http\Controllers\Auth\TwoFactorAuthController;
use App\Http\Controllers\Auth\WebauthnController;
use App\Http\Controllers\Auth\WebauthnEnabledKeyController;
use App\Http\Controllers\BannerLocationController;
use App\Http\Controllers\BrowserSessionController;
use App\Http\Controllers\DarkModeController;
use App\Http\Controllers\DeactivateAliasController;
use App\Http\Controllers\DefaultAliasDomainController;
use App\Http\Controllers\DefaultAliasFormatController;
use App\Http\Controllers\DefaultRecipientController;
use App\Http\Controllers\DefaultUsernameController;
use App\Http\Controllers\DisplayFromFormatController;
use App\Http\Controllers\DomainVerificationController;
use App\Http\Controllers\EmailSubjectController;
use App\Http\Controllers\FromNameController;
use App\Http\Controllers\LoginRedirectController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\SaveAliasLastUsedController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\ShowAliasController;
use App\Http\Controllers\ShowDashboardController;
use App\Http\Controllers\ShowDomainController;
use App\Http\Controllers\ShowFailedDeliveryController;
use App\Http\Controllers\ShowRecipientController;
use App\Http\Controllers\ShowRuleController;
use App\Http\Controllers\ShowUsernameController;
use App\Http\Controllers\SpamWarningBehaviourController;
use App\Http\Controllers\StoreFailedDeliveryController;
use App\Http\Controllers\TestAutoCreateRegexController;
use App\Http\Controllers\UseReplyToController;
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

// API login route needs CSRF middleware so that it can pass it to api/auth/mfa
Route::controller(ApiAuthenticationController::class)->prefix('api/auth')->group(function () {
    Route::post('/login', 'login');
    Route::post('/mfa', 'mfa');
});

Route::controller(ForgotUsernameController::class)->group(function () {
    Route::get('/username/reminder', 'show')->name('username.reminder.show');
    Route::post('/username/email', 'sendReminderEmail')->name('username.email');
});

Route::get('/login/2fa', [TwoFactorAuthController::class, 'index'])->name('login.2fa.index')->middleware(['2fa', 'auth']);
Route::post('/login/2fa', [TwoFactorAuthController::class, 'authenticateTwoFactor'])->name('login.2fa')->middleware(['2fa', 'throttle:3,1', 'auth']);

Route::controller(BackupCodeController::class)->group(function () {
    Route::get('/login/backup-code', 'index')->name('login.backup_code.index');
    Route::post('/login/backup-code', 'login')->name('login.backup_code.login');
});

Route::group([
    'middleware' => array_filter(array_merge(
        config('webauthn.middleware', ['web']),
        [
            config('webauthn.auth_middleware', 'auth').':'.config('webauthn.guard', 'web'),
        ]
    )),
    'domain' => config('webauthn.domain', null),
    'prefix' => config('webauthn.prefix', 'webauthn'),
], function () {
    Route::controller(WebauthnController::class)->group(function () {
        Route::get('keys', 'index')->name('webauthn.index');
        Route::get('keys/create', 'create')->name('webauthn.create');
        Route::post('keys', 'store')->name('webauthn.store');
        Route::delete('keys/{id}', 'delete'); // To override delete method and allow route caching
        Route::post('keys/{id}', 'destroy')->name('webauthn.destroy');
    });

    Route::controller(WebauthnEnabledKeyController::class)->group(function () {
        Route::post('enabled-keys', 'store')->name('webauthn.enabled_key.store');
        Route::post('enabled-keys/{id}', 'destroy')->name('webauthn.enabled_key.destroy');
    });
});

Route::middleware(['auth', 'verified', '2fa'])->group(function () {
    Route::get('/', [ShowDashboardController::class, 'index'])->name('dashboard.index');

    Route::controller(ShowAliasController::class)->group(function () {
        Route::get('/aliases', 'index')->name('aliases.index');
        Route::get('/aliases/{id}/edit', 'edit')->name('aliases.edit');
    });

    Route::controller(ShowRecipientController::class)->group(function () {
        Route::get('/recipients', 'index')->name('recipients.index');
        Route::get('/recipients/{id}/edit', 'edit')->name('recipients.edit');
        Route::post('/recipients/alias-count', 'aliasCount')->name('recipients.alias_count');
    });

    Route::controller(ShowDomainController::class)->group(function () {
        Route::get('/domains', 'index')->name('domains.index');
        Route::get('/domains/{id}/edit', 'edit')->name('domains.edit');
    });
    Route::get('/domains/{id}/check-sending', [DomainVerificationController::class, 'checkSending']);

    Route::controller(ShowUsernameController::class)->group(function () {
        Route::get('/usernames', 'index')->name('usernames.index');
        Route::get('/usernames/{id}/edit', 'edit')->name('usernames.edit');
    });

    Route::get('/deactivate/{alias}', [DeactivateAliasController::class, 'deactivate'])->name('deactivate');

    Route::get('/rules', [ShowRuleController::class, 'index'])->name('rules.index');

    Route::get('/failed-deliveries', [ShowFailedDeliveryController::class, 'index'])->name('failed_deliveries.index');

    Route::post('/test-auto-create-regex', [TestAutoCreateRegexController::class, 'index'])->name('test_auto_create_regex.index');
});

Route::group([
    'middleware' => ['auth', '2fa'],
    'prefix' => 'settings',
], function () {
    Route::controller(SettingController::class)->group(function () {
        Route::get('/', 'show')->name('settings.show');
        Route::get('/security', 'security')->name('settings.security');
        Route::get('/api', 'api')->name('settings.api');
        Route::get('/data', 'data')->name('settings.data');
        Route::get('/account', 'account')->name('settings.account');
        Route::post('/account', 'destroy')->name('account.destroy');
    });

    Route::controller(DefaultRecipientController::class)->group(function () {
        Route::post('/default-recipient', 'update')->name('settings.default_recipient');
        Route::post('/edit-default-recipient', 'edit')->name('settings.edit_default_recipient');
    });

    Route::post('/default-username', [DefaultUsernameController::class, 'update'])->name('settings.default_username');

    Route::post('/default-alias-domain', [DefaultAliasDomainController::class, 'update'])->name('settings.default_alias_domain');

    Route::post('/default-alias-format', [DefaultAliasFormatController::class, 'update'])->name('settings.default_alias_format');

    Route::post('/alias-separator', [AliasSeparatorController::class, 'update'])->name('settings.alias_separator');

    Route::post('/display-from-format', [DisplayFromFormatController::class, 'update'])->name('settings.display_from_format');

    Route::post('/login-redirect', [LoginRedirectController::class, 'update'])->name('settings.login_redirect');

    Route::post('/from-name', [FromNameController::class, 'update'])->name('settings.from_name');

    Route::post('/email-subject', [EmailSubjectController::class, 'update'])->name('settings.email_subject');

    Route::post('/banner-location', [BannerLocationController::class, 'update'])->name('settings.banner_location');

    Route::post('/spam-warning-behaviour', [SpamWarningBehaviourController::class, 'update'])->name('settings.spam_warning_behaviour');

    Route::post('/store-failed-deliveries', [StoreFailedDeliveryController::class, 'update'])->name('settings.store_failed_deliveries');

    Route::post('/dark-mode', [DarkModeController::class, 'update'])->name('settings.dark_mode');

    Route::post('/save-alias-last-used', [SaveAliasLastUsedController::class, 'update'])->name('settings.save_alias_last_used');

    Route::post('/use-reply-to', [UseReplyToController::class, 'update'])->name('settings.use_reply_to');

    Route::post('/password', [PasswordController::class, 'update'])->name('settings.password');

    Route::delete('/browser-sessions', [BrowserSessionController::class, 'destroy'])->name('settings.browser_sessions');

    Route::controller(TwoFactorAuthController::class)->group(function () {
        Route::post('/2fa/enable', 'store')->name('settings.2fa_enable');
        Route::post('/2fa/regenerate', 'update')->name('settings.2fa_regenerate');
        Route::post('/2fa/disable', 'destroy')->name('settings.2fa_disable');
    });

    Route::post('/2fa/new-backup-code', [BackupCodeController::class, 'update'])->name('settings.new_backup_code');

    Route::controller(PersonalAccessTokenController::class)->group(function () {
        Route::get('/personal-access-tokens', 'index')->name('personal_access_tokens.index');
        Route::post('/personal-access-tokens', 'store')->name('personal_access_tokens.store');
        Route::delete('/personal-access-tokens/{id}', 'destroy')->name('personal_access_tokens.destroy');
    });

    Route::get('/aliases/export', [AliasExportController::class, 'export'])->name('aliases.export');

    Route::post('/aliases/import', [AliasImportController::class, 'import'])->name('aliases.import');
});
