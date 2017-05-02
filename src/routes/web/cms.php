<?php

// catch all (rest must be cms pages)
Route::any('{other}', ['uses' => 'CmsController@generatePage'])->where('other', '.*');