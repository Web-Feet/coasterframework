<?php namespace CoasterCms\Helpers\Admin\Tools\Import;

use CoasterCms\Helpers\Cms\Html\DOMDocument;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockSelectOption;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\Page;
use CoasterCms\Models\TemplateBlock;
use CoasterCms\Models\Template;
use CoasterCms\Models\PageBlockRepeaterData;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageVersion;
use CoasterCms\Models\Language;
use Carbon\Carbon;

class WpApi
{
    protected $url = '';
    protected $blog_name = 'blog';
    protected $per_page = 50;
    protected $category_block_name = 'categories';
    protected $templates = [
                          'list_template' => 'blog',
                          'item_template' => 'blog-post',
                          'category_template' => 'blog-category',
                          'search_template' => 'blog-search',
                          'archive_template' =>'blog-archives'
                        ];
    protected $templateObjs = [];
    protected $tag_block_name = 'tags';
    protected $groupPage = null;
    protected $group = null;
    protected $ret = [];

    protected $block;

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

    public function featuredImage($data, $pageLang)
    {
        if (property_exists($data->_embedded, "wp:featuredmedia")) {

            $imData = head($data->_embedded->{"wp:featuredmedia"});

            if ( ! empty($imData->source_url) && $imData->media_type == 'image') {
              $imageBlock = $this->getBlock('image', 'image', 'Main Image');
              $im = $this->saveImageLocally($imData->source_url);
              $alt = empty($imData->alt_text) ? $pageLang->name : $imData->alt_text;
              $imageBlock->setPageId($pageLang->page_id)->getTypeObject()->submit(['source' => $im, 'alt' => $alt]);
              return $imData->source_url;
            }
        }
        return '';
    }
    public function getAuthor($wpAuthorData)
    {
      return $wpAuthorData[0]->name;
    }


    public function getBlock($name, $type = 'selectmultiplewnew', $label = '', $note = '')
    {
      if (isset($this->blocks[$name]))
      {
       return $this->blocks[$name];
      }
      $label = empty($label) ? str_replace('_', ' ', $name) : $label;
      Block::unguard();
      $block = Block::firstOrCreate(['name' => $name, 'label' => ucwords($label), 'note' => $note, 'category_id' => 1, 'type' => $type]);
      Block::reguard();
      TemplateBlock::unguard();
      TemplateBlock::firstOrCreate(['template_id' => $this->getTemplateId('item_template'), 'block_id' => $block->id]);
      TemplateBlock::reguard();
      $this->blocks[$name] = $block;
      return $block;
    }
    public function loopDataAndAddToSelectMultipleWNewBlock($block, $data, $page_id)
    {
      $toSave = [];
      BlockSelectOption::unguard();
      foreach($data AS $item)
      {
        $selectOpt = BlockSelectOption::firstOrCreate(['block_id' => $block->id, 'option' => $item->name, 'value' => strtolower($item->name)]);
        $toSave[] = strtolower($item->name);
      }
      BlockSelectOption::reguard();

      $block->setPageId($page_id)->getTypeObject()->submit(['select' => $toSave, 'custom' => '']);
      return implode(', ', $toSave);
    }

    public function getCategory($data, $page_id)
    {
        $catBlock = $this->getBlock($this->category_block_name);

        $cats = collect($data)->collapse()->where('taxonomy', 'category')->all();

        return $this->loopDataAndAddToSelectMultipleWNewBlock($catBlock, $cats, $page_id);
    }

    private function syncTags($page, $tags)
    {
        $tagBlock = $this->getBlock($this->tag_block_name);
        $tags = collect($tags)->collapse()->where('taxonomy', 'post_tag')->all();

        return $this->loopDataAndAddToSelectMultipleWNewBlock($tagBlock, $tags, $page->id);
    }

    public function getTemplateId($name='')
    {
      if (isset($this->templateObjs[$name])) {
        return $this->templateObjs[$name]->id;
      }
      $tpl = Template::where('template', '=', $this->templates[$name])->first();

      $this->templateObjs[$name] = $tpl;
      return $tpl->id;
    }

    public function createGroupPage($name, $item_name, $template_name, $item_template_name = '')
    {
      if ($item_template_name == 'item_template')
      {
        $this->blogPageGroup = new PageGroup;
        $this->blogPageGroup->name = ucfirst($name);
        $this->blogPageGroup->item_name = $item_name;
        $this->blogPageGroup->default_template = $this->getTemplateId($item_template_name);
        $this->blogPageGroup->save();
      }

      $groupPage = new Page;
      $groupPage->group_container = $this->blogPageGroup->id;
      $groupPage->template = $this->getTemplateId($template_name);

      if ($item_template_name != 'item_template')
      {
        $groupPage->parent = $this->blogPage->id;
      }
      $groupPage->save();

      // Page Lang
      $groupPageLang = new PageLang;
      $groupPageLang->page_id = $groupPage->id;
      $groupPageLang->live_version = 1;
      $groupPageLang->language_id = Language::current();
      $groupPageLang->name = ucfirst($name);
      $groupPageLang->url = str_slug($groupPageLang->name);
      $groupPageLang->save();

      $titleBlock = $this->getBlock('title', 'string', 'Main Title');
      $titleBlock->setPageId($groupPage->id)->getTypeObject()->submit($groupPageLang->name);

      return $groupPage;
    }

    public function getBlogGroupPage()
    {
      $this->blogPageGroup = PageGroup::where('name', '=', $this->blog_name);
      // New Page
      $groupPageLang = PageLang::where('name', '=', $this->blog_name)->first();

      if (empty($groupPageLang))
      {
        // Blog Initialize;
        $this->blogPage = $groupPage = $this->createGroupPage($this->blog_name, 'Post', 'list_template', 'item_template');
        $this->categoryPage = $this->createGroupPage('Categories', '', 'category_template');
        $this->archivePage = $this->createGroupPage('Archive', '', 'archive_template');
        $this->searchPage = $this->createGroupPage('Search', '', 'search_template');
      }
      else
      {
        $groupPage = Page::find($groupPageLang->page_id);
      }
      return $groupPage;
    }

    public function saveImageLocally($image)
    {
      $imagePathArr = explode('/', $image);
      $fullFileName = end($imagePathArr);

      $newUrlPath = 'uploads/images/'.$fullFileName;
      // Open the file to get existing content
      $data = @file_get_contents($image);

      // New file
      $new = public_path($newUrlPath);
      if ( ! file_exists($new)) {
        // Write the contents back to a new file
        file_put_contents($new, $data);
      }

      return '/'.$newUrlPath;
    }

    public function processContent($content)
    {
      $doc = new DomDocument;
      $doc->loadHTML($content);
      $images = $doc->getElementsByTagName('img');
      foreach ($images as $imageNode)
      {
        try {
         $image = $imageNode->getAttribute('src');
         $newUrlPath = $this->saveImageLocally($image);
         $content = str_replace($image, url()->to($newUrlPath), $content);
         $content = preg_replace('/[width|height]=".*"/', '', $content);
         } catch (Exception $e) {

         }

      }
      return $content;
    }

    public function getComments($data, $page)
    {
      $ret = '';
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
          Block::preloadClone('comments')->setPageId($page->id)->getTypeObject()->insertRow($rowData);
          $check = PageBlockRepeaterData::where('content', '=', $comment->content->rendered)->first();
          $idsInserted[$comment->id] = $check->row_id;
          $ret .= 'Comment: '.$comment->content->rendered.', ';
        }
        else
        {
          $idsInserted[$comment->id] = $check->row_id;
          $ret .= 'Comment: '.$comment->content->rendered.', ';
        }
      }
      return $ret;

    }

    public function getMetas($yoastData, $data, $page_id)
    {
      $meta_title = (empty($yoastData->title)) ? $data->title->rendered : $yoastData->title;
      $meta_description = (empty($yoastData->metadesc)) ? substr(strip_tags($data->content->rendered), 0, 140).'...' : $yoastData->metadesc;
      $meta_keywords = (empty($yoastData->metakeywords)) ? $data->title->rendered : $yoastData->metakeywords;
      try {
        $meta_title_block = $this->getBlock('meta_title', 'string', 'Meta Title');
        if (!empty($title_block)) {
            $meta_title_block->setPageId($page_id)->getTypeObject()->save($meta_title);
        }
        $meta_desc_block = $this->getBlock('meta_description', 'string', 'Meta Description');
        if (!empty($meta_desc_block)) {
            $meta_desc_block->setPageId($page_id)->getTypeObject()->save($meta_description);
        }
        $meta_keywords_block = $this->getBlock('meta_keywords', 'string', 'Meta Keywords');
        if (!empty($meta_keywords_block)) {
            $meta_keywords_block->setPageId($page_id)->getTypeObject()->save($meta_keywords);
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
        $res->comments = $comments;
        $res->main_image = $this->featuredImage($data, $pageLang);
        $latestVersion = PageVersion::latest_version($page->id, true);
        if (!empty($latestVersion)) {
            $latestVersion->publish();
        }
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
        $date_block->setPageId($page->id)->getTypeObject()->save($this->carbonDate($data->date)->format("Y-m-d H:i:s"));
      }
      $title_block = Block::where('name', '=', config('coaster::admin.title_block'))->first();
      if (!empty($title_block)) {
          $title_block->setPageId($page->id)->getTypeObject()->save($pageLang->name);
      }
      $content_block = Block::where('name', '=', 'content')->first();
      if (!empty($content_block)) {
          $content_block->setPageId($page->id)->getTypeObject()->save($this->processContent($data->content->rendered));
      }
      $leadText_block = Block::where('name', '=', 'lead_text')->first();
      if (!empty($leadText_block)) {
          $leadText_block->setPageId($page->id)->getTypeObject()->save($data->excerpt->rendered);
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
      $res->comments = $comments;
      $res->main_image = $this->featuredImage($data, $pageLang);
      $latestVersion = PageVersion::latest_version($page->id, true);
      if (!empty($latestVersion)) {
          $latestVersion->publish();
      }
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
        if (empty($posts)) {
          $this->ret[] = 'No posts to import, check the API is installed correctly.';
        }
        return $this->ret;
    }

    protected function getJson($url)
    {
        $response = file_get_contents($url, false);
        return json_decode( $response );
    }
}
