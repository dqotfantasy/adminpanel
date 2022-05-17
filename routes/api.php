<?php

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

Route::post('ws', 'PaymentController@withdrawWebhook');
Route::post('handleEvent', 'Controller@handleEvent');

Route::group(['prefix' => 'auth'], function () {

    // Public Routes
    Route::get('me', 'AuthController@me');
    Route::post('login', 'AuthController@login');
    Route::post('forgot-password', 'AuthController@forgotPassword')->name('password.email');
    Route::post('reset-password', 'AuthController@resetPassword')->name('password.update');

    Route::get('logout', 'AuthController@logout');
});

// Protected Routes
Route::group(['middleware' => 'auth:sanctum'], function () {

    Route::get('dashboard', 'DashboardController@index');
    Route::post('update/password', 'AuthController@changePassword');

    Route::get('contests/categories', 'ContestController@categories');
    Route::get('contests/contest-template', 'ContestController@contestTemplate');
    Route::get('contests/fixtures', 'ContestController@fixtures');
    Route::get('contests/user_teams', 'ContestController@userTeams');
    Route::apiResource('contests', 'ContestController');

    Route::get('fantasy_points/types', 'FantasyPointController@types');
    Route::get('users/bank_accounts', 'UserController@bankAccounts');
    Route::get('get', 'SettingController@get');
    Route::post('set', 'SettingController@set');
    Route::get('export-payment', 'PaymentController@getExport');
    Route::get('export-system-user', 'SystemUserData@getExport');
    Route::get('export-user-join', 'UserController@getExportJoinUser');

    Route::get('private-contests/categories', 'PrivateContestController@categories');
    Route::get('private-contests/contest-template', 'PrivateContestController@contestTemplate');
    Route::get('private-contests/fixtures', 'PrivateContestController@fixtures');
    Route::get('private-contests/user_teams', 'PrivateContestController@userTeams');
    Route::post('notifications/sendAll', 'NotificationController@sendAll');
    Route::get('contests/cancel/{id}', 'ContestController@contestCancel');
    Route::get('fixtures/winners', 'FixtureController@winners');
    Route::get('fixtures/contests/{id}', 'FixtureController@contests');
    Route::post('merchant_transactions/settings', 'MerchantTransactionController@settings');
    Route::get('system-user-detail', 'SystemUserData@index');
    Route::post('system-user-detail', 'SystemUserData@systemUserSave');
    Route::get('system-user-detail/edit_team', 'SystemUserData@userteamlist');
    Route::post('system-user-detail/edit_team', 'SystemUserData@editTeam');
    Route::get('checkqueue/{fixtureid}', 'CheckQueueController@index');
    Route::post('checkqueue/{fixtureid}', 'CheckQueueController@index');
    Route::get('all-checkqueue', 'CheckQueueController@queyeFind');
    Route::post('all-checkqueue', 'CheckQueueController@queyeFind');
    Route::get('earning-manager', 'EarningManagerController@index');
    Route::post('earning-manager', 'EarningManagerController@index');

    Route::get('subadmin-user', 'SubadminUserController@index');
    Route::post('subadmin-user', 'SubadminUserController@store');
    Route::get('join-match-user', 'UserController@joinedMatch');
    Route::get('all-referal-user', 'ReferalUserController@index');
    Route::get('promoter-user', 'PromoterUserController@index');
    Route::get('promoter-income-info', 'PromoterUserController@promoterInfo');

    Route::get('earning-manager/competition', 'CompetitionController@liveCompetition');
    Route::get('banners/competition', 'CompetitionController@liveCompetition');
    Route::get('banners/fixtures', 'FixtureController@fixtureget');

    Route::get('export-user', 'UserController@getExport');
    Route::get('leaderboard-detail', 'LeaderboardController@userDetail');

    Route::get('export-referal-user', 'ReferalUserController@getExport');

    Route::apiResource('competitions', 'CompetitionController');
    Route::apiResource('fixtures', 'FixtureController');
    Route::apiResource('contest-categories', 'ContestCategoryController');
    Route::apiResource('contest-templates', 'ContestTemplateController');

    Route::apiResource('rank_categories', 'RankCategoryController');
    Route::apiResource('fantasy_points', 'FantasyPointController')->only(['index', 'update']);
    Route::apiResource('private-contests', 'PrivateContestController');
    Route::apiResource('merchant_transactions', 'MerchantTransactionController');
    Route::apiResource('merchant_commissions', 'MerchantCommissionController');
    Route::apiResource('squads', 'SquadController')->only(['update']);
    Route::apiResource('users', 'UserController');
    Route::apiResource('userwallet', 'UserwalletController');
    Route::apiResource('bank_accounts', 'BankAccountController')->only(['index', 'store', 'update']);
    Route::apiResource('pan_cards', 'PanCardController')->only(['index', 'store', 'update']);
    Route::apiResource('payments', 'PaymentController')->only(['index', 'update']);
    Route::apiResource('user_match', 'UserMatchController')->only(['index', 'update']);
    Route::apiResource('states', 'StateController')->only(['index', 'update']);
    Route::apiResource('settings', 'SettingController');
    Route::apiResource('banners', 'BannerController')->only(['index', 'show', 'store', 'update','destroy']);
    Route::apiResource('blogs', 'BlogController');
    Route::apiResource('pages', 'PageController')->only(['index', 'show', 'update']);
    Route::apiResource('faqs', 'FaqController');
    Route::apiResource('notifications', 'NotificationController')->only(['index']);
    Route::apiResource('coupons', 'CouponController');
    Route::apiResource('tds', 'TdsController');
    Route::apiResource('leaderboard', 'LeaderboardController');
    Route::get('export-earning-manager', 'EarningManagerController@getExport');




});
