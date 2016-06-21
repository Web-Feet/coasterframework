<?php namespace CoasterCms\Http\Controllers\Frontend;

use CoasterCms\Exceptions\PageLoadException;
use CoasterCms\Helpers\Core\Feed;
use CoasterCms\Helpers\Core\View\FormMessage;
use CoasterCms\Libraries\Blocks\Form;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\PageRedirect;
use Illuminate\Routing\Controller;
use Redirect;
use Request;
use Response;
use URL;
use View;

class PageLoaderController extends Controller
{

    public $dom;
    public $bodyNodes = ['text' => [], 'input' => []];
    public $js;

    public function index()
    {
        $response = 200;

        try {

            // check for redirects
            $current_uri = trim(Request::getRequestUri(), '/');
            $redirect = PageRedirect::uriHasRedirect($current_uri);
            if (!empty($redirect) && $redirect->force == 1) {
                return Redirect::to($redirect->to, $redirect->type);
            }

            // set page info & check for returned errors
            PageBuilder::set_page(config('coaster::frontend.theme'));
            FormMessage::set_class('error', config('coaster::frontend.form_error_class'));

            // check for xml template
            if (Feed::$extension) {
                $output = View::make(PageBuilder::get_template_path())->render();
                return Response::make($output, '200')->header('Content-Type', Feed::content_type());
            }

            $external_template = Request::input('external');
            if (!empty($external_template)) {
                PageBuilder::$external_template = $external_template;
            }

            // check for form submissions
            if (!empty($_POST)) {
                $form_submit = Request::all();
                $success = Form::submission($form_submit);
                if (!empty($success)) {
                    return $success;
                }
            }

            if (PageBuilder::$external_template) {
                // if external template set use it
                $output = PageBuilder::external(PageBuilder::$external_template);
            }
            else {
                // else run as normal - load current page and content
                $output = View::make(PageBuilder::get_template_path())->render();
            }

            // check if search block loaded (if search page)
            if (PageBuilder::$search_not_found) {
                throw new PageLoadException('no search function found');
            }

        } catch (PageLoadException $e) {

            if (!empty($redirect)) {
                return Redirect::to($redirect->to, $redirect->type);
            }

            $response = 404;
            if (!View::exists('themes.' . PageBuilder::$theme . '.errors.404')) {
                $output = $e->getMessage();
            } else {
                $output = View::make('themes.' . PageBuilder::$theme . '.errors.404', array('error' => $e->getMessage()));
            }

        }

        // load output into PHP DOMDocument
        $this->dom = new \DOMDocument;
        libxml_use_internal_errors(true);
        $this->dom->loadHTML(mb_convert_encoding($this->removeJs($output), 'HTML-ENTITIES', 'UTF-8'));
        $this->loadBodyNodes($this->dom->getElementsByTagName('body')->item(0));

        // add meta generator
        if ($headNode = $this->dom->getElementsByTagName('head')->item(0)) {
            $generator = $this->dom->createElement('meta');
            $generator->setAttribute('name', 'generator');
            $generator->setAttribute('content', 'Coaster CMS ' . config('coaster::site.version'));
            if ($headNode->hasChildNodes()) {
                $titleNode = $this->dom->getElementsByTagName('title')->item(0);
                if (!empty($titleNode)) {
                    $headNode->insertBefore($generator, $titleNode);
                } else {
                    $headNode->insertBefore($generator, $headNode->firstChild);
                }
            } else {
                $headNode->appendChild($generator);
            }
        }

        // add <strong></strong> around any keywords
        if (config('coaster::frontend.strong_tags') == 1) {
            $keywords = explode(", ", str_replace(" and ", ", ", PageBuilder::block('meta_keywords')));
            $keywords = "/(" . implode("|", $keywords) . ")/i";
            foreach ($this->bodyNodes['text'] as $node) {
                $node->nodeValue = preg_replace($keywords, '<strong>$0</strong>', $node->nodeValue, -1, $count);
                if ($count > 0) {
                    $dom = new \DOMDocument;
                    @$dom->loadHTML('<p>' . mb_convert_encoding($node->nodeValue, 'HTML-ENTITIES', 'UTF-8') . '</p>');
                    $newNode = $dom->documentElement->getElementsByTagName('p')->item(0);
                    foreach ($newNode->childNodes as $newChildNode) {
                        $newChildNode = $this->dom->importNode($newChildNode, true);
                        $node->parentNode->insertBefore($newChildNode, $node);
                    }
                    $node->parentNode->removeChild($node);
                }
            }
        }

        // return output
        if (!PageBuilder::$external_template) {
            $html = $this->dom->saveHTML($this->dom);
            $finalHtml = $this->reInsertJs($html);
        } else {
            foreach ($this->bodyNodes['input'] as $node) {
                /** @var $node \DOMElement */
                if (strpos($node->getAttribute('name'), '[') !== false) {
                    $node->setAttribute('name', URL::to(config('coaster::frontend.external_form')) . '[' . preg_replace('/[/', '][', $node->getAttribute('name'), 1));
                } else {
                    $node->setAttribute('name', URL::to(config('coaster::frontend.external_form')) . '[' . $node->getAttribute('name') . ']');
                }
            }
            $html = substr($this->dom->saveHTML($this->dom->getElementsByTagName('body')->item(0)), 6, -7);
            $finalHtml = $this->reInsertJs($html);
        }

        return Response::make($finalHtml, $response);

    }

    public function removeJs($html)
    {
        $this->js = array();
        return preg_replace_callback(
            '#<script(.*?)>(.*?)</script>#is',
            function ($matches) {
                $this->js[] = $matches[0];
                return '<div class="coaster_js_replace"></div>';
            },
            $html
        );
    }

    public function reInsertJs($html)
    {
        $index = 0;
        return preg_replace_callback(
            '/<div class="coaster_js_replace"><\/div>/is',
            function () use (&$index) {
                return $this->js[$index++];
            },
            $html
        );
    }

    public function loadBodyNodes($node)
    {
        foreach ($node->childNodes as $childNode) {
            /** @var $childNode \DOMElement */
            if ($childNode->hasChildNodes()) {
                $this->loadBodyNodes($childNode);
            } elseif (strlen(trim($childNode->nodeValue)) > 0 && $childNode->nodeType != XML_COMMENT_NODE) {
                $this->bodyNodes['text'][] = $childNode;
            } elseif ($childNode->nodeName == 'input') {
                $this->bodyNodes['input'][] = $childNode;
            }
        }
    }

}