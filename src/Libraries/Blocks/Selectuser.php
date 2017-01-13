<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\User;
use Auth;

class Selectuser extends Select
{
    /**
     * Display name (convert user id to name if not custom)
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        if (is_numeric($content)) {
            $user = User::find($content);
        }
        return !empty($user) && $user->getName() ? $user->getName() : $content;
    }

    /**
     * Display user select plus custom input
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        $users = [];
        foreach (User::all() as $user) {
            $users[$user->id] = $user->getName() . ' (#'.$user->id.')';
        }
        $this->_editViewData['customName'] = array_key_exists($content, $users) ? '' : $content;
        $this->_editViewData['selectOptions'] = [0 => '-- Custom User --'] + $users;
        $content = $this->_editViewData['customName'] ? 0 : ($content ?: Auth::user()->id);
        return parent::edit($content);
    }

    /**
     * Save custom user name, if empty save selected user id
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        return $this->save($postContent['custom'] ?: (!empty($postContent['select']) ? $postContent['select'] : ''));
    }

    /**
     * Convert user id to name if not custom
     * @param null|string $content
     * @return null|string
     */
    public function generateSearchText($content)
    {
        $userAliases = $content ? User::userAliases() : [];
        $userName = (is_numeric($content) && !empty($userAliases[$content])) ? $userAliases[$content] : $content;
        return parent::generateSearchText($userName);
    }

}