<?php

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

Route::namespace('Web')->group(function(){

	Route::get('/', 'RooteController@index')->name('root'); 
	Route::get('/crawler', 'WebCrawlerController@index')->name('crawler.index'); 
	Route::get('/corona', 'CoronaController@index')->name('corona.index'); 
	Route::get('/coronaSummary', 'CoronaController@summary')->name('corona.summary'); 
	Route::get('/shoe_list', 'ShoeController@listshoes')->name('shoe.listshoes'); 
	Auth::routes();
	Route::get('/home', 'HomeController@index')->name('home');
}); 


