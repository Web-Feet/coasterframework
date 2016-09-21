<?php namespace CoasterCms\Libraries\Blocks;

class Selectmultiple extends Select
{
    /**
     * Unserialize string to php array before returning
     * @param string $content
     * @param array $options
     * @return array|string
     */
    public function display($content, $options = [])
    {
        $content = $content ? unserialize($content) : [];
        return parent::display($content, $options);
    }

    /**
     * Unserialize string to php array before returning
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        $content = @unserialize($content);
        return parent::edit($content);
    }

    /**
     * Serialize data to string before saving
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        return $this->save(!empty($postContent['select']) ? serialize($postContent['select']) : '');
    }

    /**
     * Unserialize string before generating search text
     * @param null|string $content
     * @return null|string
     */
    public function generateSearchText($content)
    {
        $content = @unserialize($content) ?: [];
        return $this->_generateSearchText(...$content);
    }

    /**
     * Filter in now an array search
     * @param string $content
     * @param string $search
     * @param string $type
     * @return bool
     */
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
