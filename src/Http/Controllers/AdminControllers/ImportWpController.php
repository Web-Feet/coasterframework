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

  function getImport()
  {
    $wpImport = new WpApi(\Request::input('blog_url'));
    $ret = $wpImport->importPosts();

    return response($ret);

  }
}
