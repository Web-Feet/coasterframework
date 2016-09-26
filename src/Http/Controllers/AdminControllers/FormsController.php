<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use CoasterCms\Helpers\Cms\Page\Path;
use CoasterCms\Helpers\Cms\View\PaginatorRender;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\Block;
use CoasterCms\Models\FormSubmission;
use CoasterCms\Models\Page;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\Template;
use View;

class FormsController extends Controller
{

    public function getList($pageId = 0)
    {
        $page = Page::find($pageId);
        if (!empty($page)) {
            $block_cats = Template::template_blocks(config('coaster::frontend.theme'), $page->template);
            foreach ($block_cats as $block_cat) {
                foreach ($block_cat as $block) {
                    if ($block->type == 'form') {
                        $form_blocks[] = $block;
                    }
                }
            }
        }
        if (isset($form_blocks)) {
            if (count($form_blocks) == 1) {
                return \redirect()->route('coaster.admin.forms.submissions', ['pageId' => $page->id, 'blockId' => $form_blocks[0]->id]);
            }
            $page_lang_data = PageLang::preload($pageId);
            if (!empty($page_lang_data)) {
                $name = $page_lang_data->name;
                if ($page->parent != 0) {
                    $parent_lang_data = PageLang::preload($page->parent);
                    $name = $parent_lang_data->name . " / " . $name;
                }
            } else {
                $name = '';
            }
            $this->layoutData['content'] = View::make('coaster::pages.forms.list', array('page_name' => $name, 'page_id' => $pageId, 'forms' => $form_blocks));
        } else {
            $this->layoutData['content'] = 'No Forms Found';
        }
        return null;
    }

    public function getSubmissions($pageId = 0, $blockId = 0)
    {
        $block_data = Block::getBlockOnPage($blockId, $pageId);
        if (empty($block_data) || $block_data->type != 'form') {
            \abort('404', 'Form not found on page');
        } else {
            $submission_rows = '';
            $submission_data = new \stdClass;
            $submissions = FormSubmission::where('form_block_id', '=', $blockId)->orderBy('id', 'desc')->paginate(10);
            $i = $submissions->total() - ($submissions->currentPage() - 1) * 10;
            foreach ($submissions as $submission) {
                $submission_data->id = $submission->id;
                $submission_data->numb = $i--;
                $submission_data->content = @unserialize($submission->content);
                if (!empty($submission_data->content)) {
                    foreach ($submission_data->content as $k => $v) {
                        if (is_array($v)) {
                            $submission_data->content[$k] = implode(", ", $v);
                        }
                    }
                } else {
                    preg_match_all('/\"(.*?)\";s:\d*:\"(.*?)\";/si', $submission->content, $matches);
                    foreach ($matches[1] as $k => $field_key) {
                        $submission_data->content[$field_key] = $matches[2][$k];
                    }
                }
                $submission_data->sent = $submission->sent;
                $submission_data->created_at = $submission->created_at;
                $submission_data->from_page = !empty($submission->from_page_id) ? Path::getFullName($submission->from_page_id) : '-';
                $submission_rows .= View::make('coaster::partials.forms.submissions', array('submission' => $submission_data))->render();
            }
            $this->layoutData['content'] = View::make(
                'coaster::pages.forms.submissions',
                array('links' => PaginatorRender::admin($submissions),
                    'submissions' => $submission_rows,
                    'form' => $block_data->label,
                    'can_export' => Auth::action('forms.csv'),
                    'export_link' => route('coaster.admin.forms.csv', ['pageId' => $pageId, 'blockId' => $blockId])
                )
            );
        }
    }

    public function getCsv($pageId = 0, $blockId = 0)
    {
        $block_data = Block::getBlockOnPage($blockId, $pageId);
        if (empty($block_data) || $block_data->type != 'form') {
            \abort('404', 'Form not found on page');
        } else {
            $csv = array();
            $columns = array();
            $column = 2;
            $row = 1;
            $submissions = FormSubmission::where('form_block_id', '=', $blockId)->orderBy('id', 'desc')->get();
            if (!$submissions->isEmpty()) {
                foreach ($submissions as $submission) {
                    $csv[$row] = array();
                    $csv[$row][0] = $submission->created_at;
                    $csv[$row][1] = !empty($submission->from_page_id) ? Path::getFullName($submission->from_page_id) : '-';
                    $form_data = @unserialize($submission->content);
                    if (!empty($form_data)) {
                        foreach ($form_data as $k => $v) {
                            if (!isset($columns[$k])) {
                                $columns[$k] = $column;
                                $column++;
                            }
                            if (is_array($v)) {
                                $v = implode(", ", $v);
                            }
                            $csv[$row][$columns[$k]] = $v;
                        }
                    } else {
                        preg_match_all('/\"(.*?)\";s:\d*:\"(.*?)\";/si', $submission->content, $matches);
                        foreach ($matches[1] as $k => $field_key) {
                            if (!isset($columns[$field_key])) {
                                $columns[$field_key] = $column;
                                $column++;
                            }
                            $csv[$row][$columns[$field_key]] = $matches[2][$k];
                        }
                    }
                    $row++;
                }
                // add row titles
                $csv[0][0] = 'Date/Time';
                $csv[0][1] = 'Page';
                foreach ($columns as $name => $col) {
                    $csv[0][$col] = ucwords($name);
                }
                $numb_columns = count($columns);
                foreach ($csv as $row_id => $csv_row) {
                    for ($i = 0; $i < $numb_columns; $i++) {
                        if (!isset($csv_row[$i])) {
                            $csv[$row_id][$i] = '';
                        }
                    }
                    ksort($csv[$row_id]);
                }
                ksort($csv);
                $block_data = Block::find($blockId);
                header("Content-type: text/csv");
                header("Content-Disposition: attachment; filename=" . $block_data->name . ".csv");
                header("Pragma: no-cache");
                header("Expires: 0");
                $output = fopen("php://output", "w");
                foreach ($csv as $csv_row) {
                    fputcsv($output, $csv_row); // here you can change delimiter/enclosure
                }
                fclose($output);
            }
            exit;
        }
    }

}