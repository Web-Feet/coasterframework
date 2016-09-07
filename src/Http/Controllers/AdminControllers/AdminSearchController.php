<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\Backup;
use Illuminate\Http\Request;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageBlock;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageGroupPage;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\Language;
class AdminSearchController extends Controller
{

  function search(Request $request)
  {
    $q = $request->get('q');
    $searchEntity = $request->get('search_entity');

    $searchres = $searchEntity::adminSearch($q);
    if ($searchres->count() == 0)
    {
      return '<p>No items match your search.</p>';
    }

    return Page::getPageListView($searchres);
  }
}
