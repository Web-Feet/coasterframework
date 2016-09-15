<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\User;
use Auth;
use Request;

class Selectuser extends Select
{

    public function display($content)
    {
        if (is_numeric($content)) {
            $user = User::find($content);
        }
        return !empty($user) && $user->getName() ? $user->getName() : $content;
    }

    public function edit($content)
    {
        $users = [];
        foreach (User::all() as $user) {
            $users[$user->id] = $user->getName() . ' (#'.$user->id.')';
        }
        $this->_editExtraViewData['customName'] = array_key_exists($content, $users) ? '' : $content;
        $this->_editExtraViewData['selectOptions'] = [0 => '-- Custom User --'] + $users;
        $content = $this->_editExtraViewData['customName'] ? 0 : ($content ?: Auth::user()->id);
        return parent::edit($content);
    }

    public function submit($postDataKey = '')
    {
        $updatedBlockIds = [];
        if ($customUsers = Request::input($postDataKey . $this->_editClass . 'Custom')) {
            foreach ($customUsers as $blockId => $customUser) {
                if ($customUser) {
                    $updatedBlockIds[] = $blockId;
                    $this->save($customUser);
                }
            }
        }
        if ($selectedUsers = Request::input($postDataKey . $this->_editClass)) {
            foreach ($selectedUsers as $blockId => $selectedUserId) {
                if (!in_array($blockId, $updatedBlockIds)) {
                    $updatedBlockIds[] = $blockId;
                    $this->save($selectedUserId);
                }
            }
        }
    }

    public function search_text($content)
    {
        if ($content) {
            $userAliases = User::userAliases();
        }
        return !empty($userAliases[$content]) ? $userAliases[$content] : null;
    }

}