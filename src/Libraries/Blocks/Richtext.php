<?php namespace CoasterCms\Libraries\Blocks;

use DOMDocument;
use URL;

class Richtext extends Text
{

    /**
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        $options['nl2br'] = 0; // remove <br />
        return parent::display($content, $options);
    }

    /**
     * Return files for exporting with theme data
     * @param string $content
     * @return array
     */
    public function exportFiles($content)
    {
        $uploadFiles = [];
        if ($content) {
            $dom = new DOMDocument;
            libxml_use_internal_errors(true);
            $dom->loadHTML($content);
            $tags = ['a' => 'href', 'img' => 'src'];
            foreach ($tags as $tag => $attribute) {
                foreach ($dom->getElementsByTagName($tag) as $node) {
                    $uploadFiles[] = $node->getAttribute($attribute);
                }
            }
            foreach ($uploadFiles as $k => $uploadFile) {
                if (strpos($uploadFile, '/uploads/') === 0 || strpos($uploadFile, URL::to('/')) === 0) {
                    $uploadFiles[$k] = str_replace(URL::to('/'), '', parse_url($uploadFile, PHP_URL_PATH));
                } else {
                    unset($uploadFiles[$k]);
                }
            }
        }
        return $uploadFiles;
    }

}