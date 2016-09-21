<?php namespace CoasterCms\Libraries\Blocks;

class Selectmultiple extends Select
{

    public function display($content, $options = [])
    {
        $content = $content ? unserialize($content) : [];
        return parent::display($content, $options);
    }

    public function edit($content)
    {
        $content = @unserialize($content);
        return parent::edit($content);
    }

    public function save($content)
    {
        $content['select'] = !empty($content['select']) ? serialize($content['select']) : '';
        return parent::save($content);
    }

    public function generateSearchText($content)
    {
        $content = @unserialize($content) ?: [];
        return $this->_generateSearchText(...$content);
    }

    public function filter($content, $search, $type)
    {
        $items = !empty($content) ? unserialize($content) : [];
        switch ($type) {
            case 'in':
                foreach ($items as $item) {
                    if (strpos($item, $search) !== false) {
                        return true;
                    }
                }
                return false;
                break;
            default:
                return in_array($search, $items);
        }
    }

}
