<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GetdataController;
use App\Http\Controllers\DummyController;

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

Route::get('/', function () {
    return view('welcome');
});
Route::get('image/{storage}/{filename}', 'Controller@image');

Route::get('getpoints/{fixture}',[DummyController::class,'getpoints']);
Route::get('getscore/{fixture}',[DummyController::class,'getscore']);
Route::get('manualy/{fixture}',[DummyController::class,'manualyQueue']);



Route::get('GetFixture', 'CronsController@GetFixture');
Route::get('lineupSet', 'CronsController@lineupSet');
Route::get('redisdata/{id}', 'CronsController@redisData');
Route::get('fixtureCronSet/{id}', 'CronsController@fixtureCronSet');
Route::get('fixtureCronStaticTime/{id}', 'CronsController@fixtureCronStaticTime');

Route::get('GetLineup/{id}', 'CronsController@GetLineup');
Route::get('GetPoint/{id}', 'CronsController@GetPoint');
Route::get('GetScore/{id}', 'CronsController@GetScore');
Route::get('leaderboard/{id}', 'CronsController@leaderboard');
Route::get('sendpayment', 'CronsController@sendpayment');
Route::get('pendingpayment', 'CronsController@pendingpayment');
Route::get('GetSquad/{id}', 'CronsController@GetSquad');
Route::get('SetUserTeamTotal/{id}', 'CronsController@SetUserTeamTotal');
Route::get('CalculateDynamicPrizeBreakup/{id}', 'CronsController@CalculateDynamicPrizeBreakup');
Route::get('GenerateCommission/{id}', 'CronsController@GenerateCommission');
Route::get('AddCommission', 'CronsController@AddCommission');
Route::get('CalculateEarnings', 'CronsController@CalculateEarnings');

Route::get('ContestProcess/{id}', 'CronsController@ContestProcess');

Route::get('Mergeuserdata', 'CronsController@Mergeuserdata');
Route::get('Mergebankdata', 'CronsController@Mergebankdata');
Route::get('Mergepandata', 'CronsController@Mergepandata');
Route::get('Mergereferdata', 'CronsController@Mergereferdata');


//Route::get('reset-password/{token}', 'AuthController@showPasswordResetForm')->name('password.reset');
