<?php namespace CoasterCms\Helpers\Cms\Html;

use DOMElement;
use DOMNode;

class DOMDocument extends \DOMDocument
{
    public $_jsScripts;
    public $bodyNodes;

    public function __construct($version = '', $encoding = '')
    {
        parent::__construct($version, $encoding);
        $this->_jsScripts = [];
        $this->bodyNodes = [
            'text' => [],
            'input' => []
        ];
    }

    public function loadHTML($source, $options = 0)
    {
        libxml_use_internal_errors(true);
        $sourceNoJs = $this->_removeJs($source);
        $htmlEntitiesSource = \mb_convert_encoding($sourceNoJs, 'HTML-ENTITIES', 'UTF-8');
        parent::loadHTML($htmlEntitiesSource, $options);
        $bodyNode = $this->getElementsByTagName('body')->item(0);
        $this->loadNodes($bodyNode);
    }

    public function loadNodes($node)
    {
        if ($node) {
            foreach ($node->childNodes as $childNode) {
                /** @var $childNode DOMElement */
                if ($childNode->hasChildNodes()) {
                    $this->loadNodes($childNode);
                } elseif (strlen(trim($childNode->nodeValue)) > 0 && $childNode->nodeType != XML_COMMENT_NODE) {
                    $this->bodyNodes['text'][] = $childNode;
                } elseif ($childNode->nodeName == 'input') {
                    $this->bodyNodes['input'][] = $childNode;
                }
            }
        }
    }

    public function saveHTML(DOMNode $node = null)
    {
        $html = parent::saveHTML($node);
        return $this->_reInsertJs($html);
    }

    public function saveInnerHTML(DOMNode $node = null)
    {
        $innerHTML = '';
        foreach ($node->childNodes as $childNode) {
            $innerHTML .= parent::saveHTML($childNode);
        }
        return $this->_reInsertJs($innerHTML);
    }

    public function saveBodyHTML($withTags = false)
    {
        if ($bodyNode = $this->getElementsByTagName('body')->item(0)) {
            return $withTags ? $this->saveHTML($bodyNode) : $this->saveInnerHTML($bodyNode);
        } else {
            return '';
        }
    }

    public function addMetaTag($name, $value)
    {
        if ($headNode = $this->getElementsByTagName('head')->item(0)) {
            $generator = $this->createElement('meta');
            $generator->setAttribute('name', $name);
            $generator->setAttribute('content', $value);
            if ($headNode->hasChildNodes()) {
                if ($titleNode = $this->getElementsByTagName('title')->item(0)) {
                    $headNode->insertBefore($generator, $titleNode);
                } else {
                    $headNode->insertBefore($generator, $headNode->lastChild);
                }
            } else {
                $headNode->appendChild($generator);
            }
        }
    }

    public function addStrongTags($keywords)
    {
        $keywords = "/(" . implode("|", $keywords) . ")/i";
        foreach ($this->bodyNodes['text'] as $node) {
            $node->nodeValue = preg_replace($keywords, '<strong>$0</strong>', $node->nodeValue, -1, $count);
            if ($count > 0) {
                $dom = new \DOMDocument;
                @$dom->loadHTML('<p>' . mb_convert_encoding($node->nodeValue, 'HTML-ENTITIES', 'UTF-8') . '</p>');
                $newNode = $dom->documentElement->getElementsByTagName('p')->item(0);
                foreach ($newNode->childNodes as $newChildNode) {
                    $newChildNode = $this->importNode($newChildNode, true);
                    $node->parentNode->insertBefore($newChildNode, $node);
                }
                $node->parentNode->removeChild($node);
            }
        }
    }

    public function appendInputFieldNames($prefix)
    {
        foreach ($this->bodyNodes['input'] as $node) {
            /** @var $node DOMElement */
            if (strpos($node->getAttribute('name'), '[') !== false) {
                $node->setAttribute('name', $prefix . '[' . preg_replace('/[/', '][', $node->getAttribute('name'), 1));
            } else {
                $node->setAttribute('name', $prefix . '[' . $node->getAttribute('name') . ']');
            }
        }
    }

    public function updateTokens()
    {
        foreach ($this->bodyNodes['input'] as $node) {
            if ($node->getAttribute('name') == '_token') {
                $node->setAttribute('value', csrf_token());
            }
        }
    }

    private function _removeJs($html)
    {
        return preg_replace_callback(
            '#<script(.*?)>(.*?)</script>#is',
            function ($matches) {
                $this->_jsScripts[] = $matches[0];
                return '<script type="text/coaster_js_replace"></script>';
            },
            $html
        );
    }

    private function _reInsertJs($html)
    {
        $index = 0;
        return preg_replace_callback(
            '/<script type="text\/coaster_js_replace"><\/script>/is',
            function () use (&$index) {
                return $this->_jsScripts[$index++];
            },
            $html
        );
    }

}
