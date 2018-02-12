<?php
Route::get('/', function () {
    return view('AddDataTracking');
});
Route::post('/upload', 'UploadController@upload');
