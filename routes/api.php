<?php

use App\Http\Controllers\Api\AccountDetailController;
use App\Http\Controllers\Api\ActiveAliasController;
use App\Http\Controllers\Api\ActiveDomainController;
use App\Http\Controllers\Api\ActiveRuleController;
use App\Http\Controllers\Api\ActiveUsernameController;
use App\Http\Controllers\Api\AliasController;
use App\Http\Controllers\Api\AliasRecipientController;
use App\Http\Controllers\Api\AllowedRecipientController;
use App\Http\Controllers\Api\ApiTokenDetailController;
use App\Http\Controllers\Api\AppVersionController;
use App\Http\Controllers\Api\CatchAllDomainController;
use App\Http\Controllers\Api\CatchAllUsernameController;
use App\Http\Controllers\Api\DomainController;
use App\Http\Controllers\Api\DomainDefaultRecipientController;
use App\Http\Controllers\Api\DomainOptionController;
use App\Http\Controllers\Api\EncryptedRecipientController;
use App\Http\Controllers\Api\FailedDeliveryController;
use App\Http\Controllers\Api\InlineEncryptedRecipientController;
use App\Http\Controllers\Api\ProtectedHeadersRecipientController;
use App\Http\Controllers\Api\RecipientController;
use App\Http\Controllers\Api\RecipientKeyController;
use App\Http\Controllers\Api\ReorderRuleController;
use App\Http\Controllers\Api\RuleController;
use App\Http\Controllers\Api\UsernameController;
use App\Http\Controllers\Api\UsernameDefaultRecipientController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'middleware' => ['auth:sanctum', 'verified'],
    'prefix' => 'v1',
], function () {
    Route::controller(AliasController::class)->group(function () {
        Route::get('/aliases', 'index');
        Route::get('/aliases/{id}', 'show');
        Route::post('/aliases', 'store');
        Route::patch('/aliases/{id}', 'update');
        Route::patch('/aliases/{id}/restore', 'restore');
        Route::delete('/aliases/{id}', 'destroy');
        Route::delete('/aliases/{id}/forget', 'forget');
    });

    Route::controller(ActiveAliasController::class)->group(function () {
        Route::post('/active-aliases', 'store');
        Route::delete('/active-aliases/{id}', 'destroy');
    });

    Route::post('/alias-recipients', [AliasRecipientController::class, 'store']);

    Route::controller(RecipientController::class)->group(function () {
        Route::get('/recipients', 'index');
        Route::get('/recipients/{id}', 'show');
        Route::post('/recipients', 'store');
        Route::delete('/recipients/{id}', 'destroy');
    });

    Route::controller(RecipientKeyController::class)->group(function () {
        Route::patch('/recipient-keys/{id}', 'update');
        Route::delete('/recipient-keys/{id}', 'destroy');
    });

    Route::controller(EncryptedRecipientController::class)->group(function () {
        Route::post('/encrypted-recipients', 'store');
        Route::delete('/encrypted-recipients/{id}', 'destroy');
    });

    Route::controller(InlineEncryptedRecipientController::class)->group(function () {
        Route::post('/inline-encrypted-recipients', 'store');
        Route::delete('/inline-encrypted-recipients/{id}', 'destroy');
    });

    Route::controller(ProtectedHeadersRecipientController::class)->group(function () {
        Route::post('/protected-headers-recipients', 'store');
        Route::delete('/protected-headers-recipients/{id}', 'destroy');
    });

    Route::controller(AllowedRecipientController::class)->group(function () {
        Route::post('/allowed-recipients', 'store');
        Route::delete('/allowed-recipients/{id}', 'destroy');
    });

    Route::controller(DomainController::class)->group(function () {
        Route::get('/domains', 'index');
        Route::get('/domains/{id}', 'show');
        Route::post('/domains', 'store');
        Route::patch('/domains/{id}', 'update');
        Route::delete('/domains/{id}', 'destroy');
    });

    Route::patch('/domains/{id}/default-recipient', [DomainDefaultRecipientController::class, 'update']);

    Route::controller(ActiveDomainController::class)->group(function () {
        Route::post('/active-domains', 'store');
        Route::delete('/active-domains/{id}', 'destroy');
    });

    Route::controller(CatchAllDomainController::class)->group(function () {
        Route::post('/catch-all-domains', 'store');
        Route::delete('/catch-all-domains/{id}', 'destroy');
    });

    Route::controller(UsernameController::class)->group(function () {
        Route::get('/usernames', 'index');
        Route::get('/usernames/{id}', 'show');
        Route::post('/usernames', 'store');
        Route::patch('/usernames/{id}', 'update');
        Route::delete('/usernames/{id}', 'destroy');
    });

    Route::patch('/usernames/{id}/default-recipient', [UsernameDefaultRecipientController::class, 'update']);

    Route::controller(ActiveUsernameController::class)->group(function () {
        Route::post('/active-usernames', 'store');
        Route::delete('/active-usernames/{id}', 'destroy');
    });

    Route::controller(CatchAllUsernameController::class)->group(function () {
        Route::post('/catch-all-usernames', 'store');
        Route::delete('/catch-all-usernames/{id}', 'destroy');
    });

    Route::controller(RuleController::class)->group(function () {
        Route::get('/rules', 'index');
        Route::get('/rules/{id}', 'show');
        Route::post('/rules', 'store');
        Route::patch('/rules/{id}', 'update');
        Route::delete('/rules/{id}', 'destroy');
    });

    Route::post('/reorder-rules', [ReorderRuleController::class, 'store']);

    Route::controller(ActiveRuleController::class)->group(function () {
        Route::post('/active-rules', 'store');
        Route::delete('/active-rules/{id}', 'destroy');
    });

    Route::controller(FailedDeliveryController::class)->group(function () {
        Route::get('/failed-deliveries', 'index');
        Route::get('/failed-deliveries/{id}', 'show');
        Route::delete('/failed-deliveries/{id}', 'destroy');
    });

    Route::get('/domain-options', [DomainOptionController::class, 'index']);

    Route::get('/account-details', [AccountDetailController::class, 'index']);

    Route::get('/app-version', [AppVersionController::class, 'index']);

    Route::get('api-token-details', [ApiTokenDetailController::class, 'show']);
});
