<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Models\Block;
use URL;
use View;

class Image extends AbstractBlock
{

    /**
     * @var string
     */
    protected $_renderDataName = 'image';

    /**
     * Image constructor.
     * @param Block $block
     */
    public function __construct(Block $block)
    {
        parent::__construct($block);
        $this->_displayViewDirs[] = 'images';
    }

    /**
     * Display image, image can be cropped with croppa
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        $imageData = $this->_defaultData($content);

        if (empty($imageData->file)) {
            return '';
        }

        if (empty($imageData->title)) {
            if (empty($options['title'])) {
                $fileName = substr(strrchr($imageData->file, '/'), 1);
                $imageData->title = str_replace('_', ' ', preg_replace('/\\.[^.\\s]{3,4}$/', '', $fileName));
            } else {
                $imageData->title = $options['title'];
            }
        }

        $imageData->extra_attrs = '';
        $ignoreAttributes = ['height', 'width', 'group', 'view', 'title', 'croppaOptions', 'version'];
        foreach ($options as $option => $val) {
            if (!in_array($option, $ignoreAttributes)) {
                $imageData->extra_attrs .= $option . '="' . $val . '" ';
            }
        }

        $imageData->group = !empty($options['group']) ? $options['group'] : '';
        $imageData->original = URL::to($imageData->file);

        $height = !empty($options['height']) ? $options['height'] : null;
        $width = !empty($options['width']) ? $options['width'] : null;
        if ($height || $width) {
            $croppaOptions = !empty($options['croppaOptions']) ? $options['croppaOptions'] : [];
            $imageData->file = str_replace(URL::to('/'), '', $imageData->file);
            $imageData->file = \Croppa::url($imageData->file, $width, $height, $croppaOptions);
        } else {
            $imageData->file = $imageData->original;
        }

        return $this->_renderDisplayView($options, $imageData);
    }
    
    /**
     * @param array $options
     * @return string
     */
    public function displayDummy($options)
    {
        $imageData = $this->_defaultData(null);
        $imageData->extra_attrs = '';
        $imageData->group = '';
        $imageData->original = '';
        return $this->_renderDisplayView($options, $imageData);
    }

    /**
     * Load image block data with domain relative paths
     * @param string $content
     * @return string
     */
    public function edit($content)
    {
        $imageData = $this->_defaultData($content);
        $imageData->file = str_replace(URL::to('/'), '', $imageData->file);
        return parent::edit($imageData);
    }

    /**
     * Save image block data
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        if (!empty($postContent['alt']) || !empty($postContent['source'])) {
            $imageData = $this->_defaultData('');
            $imageData->title = !empty($postContent['alt']) ? $postContent['alt'] : '';
            $imageData->file = !empty($postContent['source']) ? $postContent['source'] : '';
        } else {
            $imageData = '';
        }
        return $this->save($imageData ? serialize($imageData) : '');
    }

    /**
     * Add image filename and image title data to search
     * @param null|string $content
     * @return null|string
     */
    public function generateSearchText($content)
    {
        $content = $this->_defaultData($content);
        $searchText = $this->_generateSearchText($content->title, basename($content->file));
        return parent::generateSearchText($searchText);
    }

    /**
     * Return valid image data
     * @param $content
     * @return \stdClass
     */
    protected function _defaultData($content)
    {
        $content = @unserialize($content);
        if (empty($content) || !is_a($content, \stdClass::class)) {
            $content = new \stdClass;
        }
        $content->file = empty($content->file) ? '' : $content->file;
        $content->title = empty($content->title) ? '' : $content->title;
        return $content;
    }

    /**
     * Return image file for exporting with theme data
     * @param string $content
     * @return array
     */
    public function exportFiles($content)
    {
        $content = $this->_defaultData($content);
        if ($content->file && (strpos($content->file, '/') === 0 || strpos($content->file, URL::to('/')) === 0)) {
            return [str_replace(URL::to('/'), '', $content->file)];
        } else {
            return [];
        }
    }

}
