<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\Theme\BlockManager;
use CoasterCms\Models\User;
use Auth;
use Request;

class Selectuser extends _Base
{
    public static $blocks_key = 'blockUser';

    public static function display($block, $block_data, $options = array())
    {
        if (is_numeric($block_data)) {
            $user = User::find($block_data);
            $userName = $user->name ?: $user->email;
        } else {
            $userName = $block_data;
        }
        return parent::display($block, $userName, $options);
    }

    public static function edit($block, $block_data, $page_id = 0, $parent_repeater = null)
    {
        $users = [];
        foreach (User::all() as $user) {
            $users[$user->id] = $user->name ?: $user->email;
        }
        $blockData = new \stdClass;
        $blockData->custom = is_numeric($block_data) ? '' : $block_data;
        $blockData->users = [0 => '-- Custom User --'] + $users;
        $blockData->selected = is_numeric($block_data) ? $block_data : ($blockData->custom ? 0 : Auth::user()->id);
        return parent::edit($block, $blockData, $page_id, $parent_repeater);
    }

    public static function submit($page_id, $blocks_key, $repeater_info = null)
    {
        $updatedBlockIds = [];
        $customUsers = Request::input($blocks_key . 'Custom') ?: [];
        foreach ($customUsers as $blockId => $customUser) {
            if ($customUser) {
                $updatedBlockIds [] = $blockId;
                BlockManager::update_block($blockId, $customUser, $page_id, $repeater_info);
            }
        }
        $userIds = Request::input($blocks_key) ?: [];
        foreach ($userIds as $blockId => $userId) {
            if (!in_array($blockId, $updatedBlockIds)) {
                BlockManager::update_block($blockId, $userId, $page_id, $repeater_info);
            }
        }

    }

}