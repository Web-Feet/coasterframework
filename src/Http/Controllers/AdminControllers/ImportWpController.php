<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\AdminLog;
use CoasterCms\Helpers\Admin\Tools\Import\WpApi;
/**
 *
 */
class ImportWpController extends Controller
{
  public function postImport()
  {
    $url = request()->get('blog_url');
    $wpImport = new WpApi($url);
    $ret = $wpImport->importPosts();

    $this->layoutData['content'] = view('coaster::pages.tools.wordpress.import', array('url' => $url, 'can_import' => Auth::action('wpimport'), 'result' => $ret));
  }

  public function getImport()
  {
    $this->layoutData['content'] = view('coaster::pages.tools.wordpress.import', array('url' => '', 'can_import' => Auth::action('wpimport'), 'result' => []));
  }
}
