<?php namespace CoasterCms\Libraries\Blocks;

class Selectmultiple extends Select
{

    /**
     * @var string
     */
    protected $_renderDataName = 'options';

    /**
     * @var string
     */
    protected $_renderRepeatedItemName = 'option';

    /**
     * Unserialize string to php array before returning
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        return $this->_renderDisplayView($options, $this->_defaultData($content));
    }

    /**
     * Unserialize string to php array before returning
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        return parent::edit($this->_defaultData($content));
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
        $searchText = $this->_generateSearchText(...$content);
        return parent::generateSearchText($searchText);
    }

    /**
     * @param mixed $content
     * @return array
     */
    protected function _defaultData($content)
    {
        $content = $content ? @unserialize($content) : [];
        return is_array($content) ? $content : [];
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
