<?php

// catch all (rest must be cms pages)
Route::any('{other}', ['middleware' => ['web', 'coaster.pagebuilder.init'], 'uses' => 'CmsController@generatePage'])->where('other', '.*');