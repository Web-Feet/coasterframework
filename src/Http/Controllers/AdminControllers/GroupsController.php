<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use Carbon\Carbon;
use CoasterCms\Helpers\Cms\StringHelper;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Models\Block;
use CoasterCms\Models\Language;
use CoasterCms\Models\PageBlock;
use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageGroupAttribute;
use CoasterCms\Models\PageLang;
use CoasterCms\Models\Theme;
use Request;
use View;

class GroupsController extends Controller
{

    public function getPages($groupId)
    {
        $group = PageGroup::preload($groupId);
        if ($group->exists) {
            $pageIds = $group->itemPageIds(false, true);

            $attributes = PageGroupAttribute::where('group_id', '=', $groupId)->get();
            $attributeBlocks = [];
            foreach ($attributes as $attribute) {
                $block = Block::preload($attribute->item_block_id);
                if ($block->exists) {
                    $attributeBlocks[$attribute->item_block_id] = $block;
                }
            }

            $pageRows = '';

            if (!empty($pageIds)) {
                foreach ($pageIds as $pageId) {
                    $pageLang = PageLang::preload($pageId);

                    $showBlocks = [];
                    $canEdit = Auth::action('pages.edit', ['page_id' => $pageId]);
                    $canDelete = Auth::action('pages.delete', ['page_id' => $pageId]);

                    foreach ($attributeBlocks as $attributeBlock) {
                        $pageBlockContent = PageBlock::preloadPageBlockLanguage($pageId, $attributeBlock->id, -1, 'block_id')->content;
                        if (strpos($attributeBlock->type, 'selectmultiple') === 0 && !empty($pageBlockContent)) {
                            // selectmultiple
                            $showBlocks[] = implode(', ', unserialize($pageBlockContent));
                        } elseif ($attributeBlock->type == 'datetime' && !empty($pageBlockContent)) {
                            // datetime
                            $showBlocks[] = (new Carbon($pageBlockContent))->format(config('coaster::date.format.long'));
                        } else {
                            // text/string/select
                            $showBlocks[] = strip_tags(StringHelper::cutString($pageBlockContent, 50));
                        }
                    }

                    $pageRows .= View::make('coaster::partials.groups.page_row', array('pageId' => $pageId, 'page_lang' => $pageLang, 'item_name' => $group->item_name, 'showBlocks' => $showBlocks, 'can_edit' => $canEdit, 'can_delete' => $canDelete))->render();
                }
            }

            $pagesTable = View::make('coaster::partials.groups.page_table', array('rows' => $pageRows, 'item_name' => $group->item_name, 'blocks' => $attributeBlocks))->render();

            $this->layoutData['modals'] = View::make('coaster::modals.general.delete_item');
            $this->layoutData['content'] = View::make('coaster::pages.groups', array('group' => $group, 'pages' => $pagesTable, 'can_add' => $group->canAddItems(), 'can_edit' => $group->canEditItems()));
        }
    }

    public function getEdit($groupId)
    {
        $group = PageGroup::preload($groupId);
        if ($group->exists) {

            $templateSelectOptions = [0 => '-- No default --'] + Theme::get_template_list($group->default_template);
            $blockList = Block::idToLabelArray();

            $this->layoutData['content'] = View::make('coaster::pages.groups.edit', ['group' => $group, 'defaultTemplate' => $group->default_template, 'templateSelectOptions' => $templateSelectOptions, 'blockList' => $blockList]);
        }
    }


    public function postEdit($groupId)
    {
        $group = PageGroup::preload($groupId);
        if ($group->exists) {
            $groupInput = Request::input('group', []);
            foreach ($groupInput as $groupAttribute => $attributeValue) {
                if ($group->$groupAttribute !== null && $groupAttribute != 'id') {
                    if (is_array($attributeValue)) {
                        $attributeValue = isset($attributeValue['select']) ? $attributeValue['select'] : '';
                    }
                    $group->$groupAttribute = $attributeValue;
                }
            }
            $group->save();

            $currentAttributes = [];
            $newAttributes = [];
            foreach ($group->groupAttributes as $currentAttribute) {
                $currentAttributes[$currentAttribute->id] = $currentAttribute;
            }
            $groupPageAttributes = Request::input('groupAttribute', []);

            foreach ($groupPageAttributes as $attributeId => $groupPageAttribute) {
                if ($newAttribute = strpos($attributeId, 'new') === 0 ? new PageGroupAttribute : (!empty($currentAttributes[$attributeId]) ? $currentAttributes[$attributeId] : null)) {
                    $newAttribute->group_id = $group->id;
                    $newAttribute->item_block_id = $groupPageAttribute['item_block_id'];
                    $newAttribute->item_block_order_priority = $groupPageAttribute['item_block_order_priority'];
                    $newAttribute->item_block_order_dir = $groupPageAttribute['item_block_order_dir'];
                    $newAttribute->save();
                    $newAttributes[$newAttribute->id] = $newAttribute;
                }
            }

            $deleteAttributeIds = array_diff(array_keys($currentAttributes), array_keys($newAttributes));
            PageGroupAttribute::whereIn('id', $deleteAttributeIds)->delete();
        }

        return redirect()->route('coaster.admin.groups.edit', ['groupId' => $groupId]);
    }


}