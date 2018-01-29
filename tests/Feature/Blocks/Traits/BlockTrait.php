<?php
namespace Tests\Feature\Blocks\Traits;

use Illuminate\Support\Facades\Storage;

trait BlockTrait {
    public function createView($type, $contentString, $name = 'test_block') {
        $this->app['view']->addLocation(storage_path('framework/testing/disks/views'));

        Storage::fake('views');
        Storage::disk('views')->put('themes/default/blocks/'.$type.'/default.blade.php', $contentString);
    }
}
