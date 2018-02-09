<?php
namespace CoasterCms\Tests\Feature\Blocks\Traits;

use Illuminate\Support\Facades\Storage;

trait BlockTrait {
    
    public function createView($type, $contentString)
    {
        $this->app['view']->addLocation(storage_path('framework/testing/disks/views'));

        Storage::fake('views');
        Storage::disk('views')->put('themes/default/blocks/'.$type.'/default.blade.php', $contentString);
    }

    public function assertViewNotFound($viewResult, $blockName, $blockType = 'string')
    {
    	$this->assertContains('Template not found for '.$blockType.' block: '.$blockName.'<br />Tried #1 themes.default.blocks.'.$blockType.'.'.$blockName.'<br />Tried #2 themes.default.blocks.'.$blockType.'.default', $viewResult);
    }
}
