<?php namespace CoasterCms\Helpers\Admin\Tools\Import;

use CoasterCms\Helpers\Cms\Theme\BlockManager;
use CoasterCms\Helpers\Cms\Html\DomDocument;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Libraries\Blocks\Repeater;
use CoasterCms\Libraries\Blocks\Selectmultiplewnew;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockRepeater;
use CoasterCms\Models\BlockSelectOption;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageBlockRepeaterData;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageVersion;
use CoasterCms\Models\Language;
use Carbon\Carbon;

class WpApi
{
    protected $url = '';
    protected $blog_name = 'blog';
    protected $per_page = 30;
    protected $category_block_name = 'categories';
    protected $tag_block_name = 'tags';
    protected $groupPage = null;
    protected $group = null;
    protected $ret = [];

    public function __construct($url)
    {
      $this->url = $url;
      $this->groupPage = $this->getBlogGroupPage();
      $this->group = PageGroup::where('id', '=', $this->groupPage->group_container)->first();
      $this->setUpCommentsBlocks();
    }

    public function setUpCommentsBlocks()
    {
      $commentStatusBlock = Block::where('name', '=', 'comment_status')->first();
      BlockSelectOption::unguard();
      $this->commentApprovedId = BlockSelectOption::firstOrCreate(['block_id' => $commentStatusBlock->id, 'option' => 'Approved', 'value' => 'approved'])->id;
      $this->commentNotApprovedId = BlockSelectOption::firstOrCreate(['block_id' => $commentStatusBlock->id, 'option' => 'Awaiting Approval', 'value' => 'awaiting approval'])->id;
      BlockSelectOption::reguard();

    }

    public function featuredImage($data)
    {
        if (property_exists($data, "wp:featuredmedia")) {
            $data = head($data->{"wp:featuredmedia"});
            if (isset($data->source_url)) {
                return $data->source_url;
            }
        }
        return null;
    }
    public function getAuthor($wpAuthorData)
    {
      return $wpAuthorData[0]->name;
    }


    public function getBlock($name, $type = 'selectmultiplewnew')
    {
      $label = str_replace('_', ' ', $name);
      Block::unguard();
      $block = Block::firstOrCreate(['name' => $name, 'label' => ucwords($label), 'note' => 'Select or add new '.$name, 'category_id' => 1, 'type' => $type]);
      Block::reguard();

      return $block;
    }
    public function loopDataAndAddToSelectMultpipleWNewBlock($block, $data, $page_id)
    {
      $toSave = [];
      BlockSelectOption::unguard();
      foreach($data AS $item)
      {
        $selectOpt = BlockSelectOption::firstOrCreate(['block_id' => $block->id, 'option' => $item->name, 'value' => strtolower($item->name)]);
        $toSave[] = strtolower($item->name);
      };
      BlockSelectOption::reguard();
      BlockManager::update_block($block->id, Selectmultiplewnew::save($toSave), $page_id, null);
      return implode(', ', $toSave);
    }

    public function getCategory($data, $page_id)
    {
        $catBlock = $this->getBlock($this->category_block_name);

        $cats = collect($data)->collapse()->where('taxonomy', 'category')->all();

        return $this->loopDataAndAddToSelectMultpipleWNewBlock($catBlock, $cats, $page_id);
    }

    private function syncTags($page, $tags)
    {
        $tagBlock = $this->getBlock($this->tag_block_name);
        $tags = collect($tags)->collapse()->where('taxonomy', 'post_tag')->all();

        return $this->loopDataAndAddToSelectMultpipleWNewBlock($tagBlock, $tags, $page->id);
    }

    public function getBlogGroupPage()
    {
      // New Page
      $groupPageLang = PageLang::where('name', '=', $this->blog_name)->first();
      if (empty($groupPageLang))
      {
        $groupPage = new Page;
        $groupPage->group_container = 1;
        $groupPage->save();

        // Page Lang
        $groupPageLang = new PageLang;
        $groupPageLang->page_id = $groupPage->id;
        $groupPageLang->version = 1;
        $groupPageLang->language_id = Language::current();
        $groupPageLang->name = $this->blog_name;
        $groupPageLang->url = str_slug($groupPageLang->name);
        $groupPageLang->save();
      }
      else
      {
        $groupPage = Page::find($groupPageLang->page_id);
      }
      return $groupPage;
    }

    public function processContent($content)
    {
      $doc = new DomDocument;
      $doc->loadHTML($content);
      $images = $doc->getElementsByTagName('img');
      foreach ($images as $imageNode)
      {
         $image = $imageNode->getAttribute('src');
         $imagePathArr = explode('/', $image);
         $fullFileName = end($imagePathArr);

         $newUrlPath = 'uploads/images/'.$fullFileName;
         try {
           // Open the file to get existing content
           $data = @file_get_contents($image);

           // New file
           $new = public_path($newUrlPath);
           if ( ! file_exists($new)) {
             // Write the contents back to a new file
             file_put_contents($new, $data);
           }

           $content = str_replace($image, url()->to($newUrlPath), $content);
         } catch (Exception $e) {

         }

      }
      return $content;
    }

    public function getComments($data, $page)
    {
      $commentData = collect($this->getJson($this->url . '/wp-json/wp/v2/comments/?post='.$data->id ));
      $idsInserted = [];
      foreach ($commentData as $comment)
      {
        $check = PageBlockRepeaterData::where('content', '=', $comment->content->rendered)->first();
        if ( ! $check)
        {
          $rowData['comment_author'] = $comment->author_name;
          $rowData['comment_url'] = $comment->author_url;
          $rowData['comment_message'] = $comment->content->rendered;
          $rowData['comment_status'] = $comment->status;

          $rowData['comment_parent'] = isset($idsInserted[$comment->parent]) ? $idsInserted[$comment->parent] : 0;

          $rowData['comment_date'] = $this->carbonDate($comment->date)->format('Y-m-d H:i:s');
          $rowInfo = Repeater::insertRow('comments', $page->id, $rowData);
          $idsInserted[$comment->id] = $rowInfo->row_id;
        }
        else
        {
          $idsInserted[$comment->id] = $check->row_id;
        }
      }
    }

    public function getMetas($yoastData, $data, $page_id)
    {
      $meta_title = (empty($yoastData->title)) ? $data->title->rendered : $yoastData->title;
      $meta_description = (empty($yoastData->metadesc)) ? substr(strip_tags($data->content->rendered), 0, 140).'...' : $yoastData->metadesc;
      $meta_keywords = (empty($yoastData->metakeywords)) ? $data->title->rendered : $yoastData->metakeywords;
      try {
        $meta_title_block = Block::where('name', '=', 'meta_title')->first();
        if (!empty($title_block)) {
            BlockManager::$meta_title_block($meta_title_block->id, $meta_title, $page_id); // saves first page version
        }
        $meta_desc_block = Block::where('name', '=', 'meta_description')->first();
        if (!empty($meta_desc_block)) {
            BlockManager::update_block($meta_desc_block->id, $meta_description, $page_id); // saves first page version
        }
        $meta_keywords_block = Block::where('name', '=', 'meta_keywords')->first();
        if (!empty($meta_keywords_block)) {
          BlockManager::update_block($meta_keywords_block->id, $meta_keywords, $page_id); // saves first page version
        }
      } catch (Exception $e) {

      }


    }

    public function createPost($data)
    {
      $pageLang = PageLang::where('name', '=', $data->title->rendered)->first();
      $uporc = 'updated';
      if (empty($pageLang))
      {
          $uporc = 'created';
          $page = new Page;
          $pageLang = new PageLang;
      }
      else
      {
        $page = Page::find($pageLang->page_id);
        $comments  = $this->getComments($data, $page);
        $latestVersion = PageVersion::latest_version($page->id, true);
        if (!empty($latestVersion)) {
            $latestVersion->publish();
        }
        if ( ! empty($data->yoast))
        {
          $this->getMetas($data->yoast, $data, $page->id);
        }
        $res = new \stdClass;
        $res->message = 'Post '.$uporc.': '.$pageLang->name;
        $res->oldLink = $data->link;
        $res->newLink = Path::getFullUrl($page->id);
        $res->categories = 'UPDATE RUN';
        $res->tags = 'UPDATE RUN';
        return $res;
      }
      $page->live = 2;
      $page->live_start = $this->carbonDate($data->date)->format("Y-m-d H:i:s");
      $page->created_at = $this->carbonDate($data->date);
      $page->updated_at = $this->carbonDate($data->modified);
      $page->parent = $this->groupPage->id;
      $page->template = $this->group->default_template;
      $page->save();
      $page->groups()->sync([$this->group->id]);
      $comments  = $this->getComments($data, $page);
      $categories = $this->getCategory($data->_embedded->{"wp:term"}, $page->id);
      // Page Lang
      $pageLang->live_version = 0;
      $pageLang->page_id = $page->id;

      $pageLang->language_id = Language::current();

      $pageLang->name = $data->title->rendered;
      $pageLang->url = str_slug($pageLang->name);
      $pageLang->save();

      $tags = $this->syncTags($page, $data->_embedded->{"wp:term"});
      $date_block = Block::where('name', '=', 'post_date')->first();
      if ( ! empty($date_block)) {
        BlockManager::update_block($date_block->id, $this->carbonDate($data->date)->format("Y-m-d H:i:s"), $page->id);
      }
      $title_block = Block::where('name', '=', config('coaster::admin.title_block'))->first();
      if (!empty($title_block)) {
          BlockManager::update_block($title_block->id, $pageLang->name, $page->id); // saves first page version
      }
      $content_block = Block::where('name', '=', 'content')->first();
      if (!empty($content_block)) {
          BlockManager::update_block($content_block->id, $this->processContent($data->content->rendered), $page->id); // saves first page version
      }
      $leadText_block = Block::where('name', '=', 'lead_text')->first();
      if (!empty($content_block)) {
          BlockManager::update_block($leadText_block->id, $data->excerpt->rendered, $page->id); // saves first page version
      }
      $latestVersion = PageVersion::latest_version($page->id, true);
      if (!empty($latestVersion)) {
          $latestVersion->publish();
      }
      if ( ! empty($data->yoast))
      {
        $this->getMetas($data->yoast, $data, $page->id);
      }
      $res = new \stdClass;
      $res->message = 'Post '.$uporc.': '.$pageLang->name;
      $res->oldLink = $data->link;
      $res->newLink = Path::getFullUrl($page->id);
      $res->categories = $categories;
      $res->tags = $tags;

      return $res;

    }


    protected function carbonDate($date)
    {
        return Carbon::parse($date);
    }
    public function importPosts($page = 1)
    {
        $posts = collect($this->getJson($this->url . '/wp-json/wp/v2/posts/?_embed&filter[orderby]=modified&page=' . $page.'&per_page='.$this->per_page ));

        foreach ($posts as $post) {
            $this->ret[] = $this->createPost($post);
        }
        if (count($posts) == $this->per_page) {
          $this->importPosts($page+1);
        }

        return $this->ret;
    }

    protected function getJson($url)
    {
        $response = file_get_contents($url, false);
        return json_decode( $response );
    }
}
