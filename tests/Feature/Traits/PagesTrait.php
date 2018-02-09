<?php 

namespace CoasterCms\Tests\Feature\Traits;

use CoasterCms\Models\PageLang;
use CoasterCms\Models\Template;
use Illuminate\Support\Facades\Storage;

trait PagesTrait {

	public function createTemplateView($name = 'home', $contentString = '<html><body>Hello world!</body></html>')
    {
        $this->app['view']->addLocation(storage_path('framework/testing/disks/views'));

        Storage::fake('views');
        Storage::disk('views')->put('themes/default/templates/'.$name.'.blade.php', $contentString);

        Template::unguard();
        return factory(Template::class)->create([
        	'template' => $name,
        ]);
    }

	public function createPage($title = 'Page title', $pageOptions = [],  $pageLangOptions = [])
	{
		$pageLangOptions = array_merge(['name' => $title, 'url' => '/'], $pageLangOptions);
		$pl = factory(PageLang::class)->create($pageLangOptions);

		$pl->page()->first()->update($pageOptions);

		return $pl;
	}
}