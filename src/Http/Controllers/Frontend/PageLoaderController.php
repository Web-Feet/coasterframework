<?php namespace CoasterCms\Http\Controllers\Frontend;

use CoasterCms\Exceptions\PageLoadException;
use CoasterCms\Helpers\Feed;
use CoasterCms\Helpers\View\FormMessage;
use CoasterCms\Libraries\Blocks\Form;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\PageRedirect;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;

class PageLoaderController extends Controller
{

    public $dom;
    public $bodyNodes = array('text' => array(), 'input' => array());
    public $js;

    public function index()
    {
        try {
            // check for redirects
            $current_uri = trim(Request::getRequestUri(), '/');
            $redirect = PageRedirect::get($current_uri);
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
                $form_submit = Input::all();
                $success = Form::submission($form_submit);
                if (!empty($success))
                    return $success;
            }

            // if external template set use it
            if (PageBuilder::$external_template) {
                $output = PageBuilder::external(PageBuilder::$external_template);
            } // else run as normal - load current page and content
            else {
                $output = View::make(PageBuilder::get_template_path())->render();
            }

            // check if search block loaded (if search page)
            if (PageBuilder::$search_not_found) {
                throw new PageLoadException('no search function found');
            }

            // load html into PHP DOMDocument
            $this->dom = new \DOMDocument;
            libxml_use_internal_errors(true);
            $this->dom->loadHTML(mb_convert_encoding($this->removeJs($output), 'HTML-ENTITIES', 'UTF-8'));
            $this->loadBodyNodes($this->dom->getElementsByTagName('body')->item(0));

            // add meta generator
            $headNode = $this->dom->getElementsByTagName('head')->item(0);
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
                return $this->reInsertJs($html);
            } else {
                foreach ($this->bodyNodes['input'] as $node) {
                    if (strpos($node->getAttribute('name'), '[') !== false) {
                        $node->setAttribute('name', URL::to(config('coaster::frontend.external_form')) . '[' . preg_replace('/[/', '][', $node->getAttribute('name'), 1));
                    } else {
                        $node->setAttribute('name', URL::to(config('coaster::frontend.external_form')) . '[' . $node->getAttribute('name') . ']');
                    }
                }
                $html = substr($this->dom->saveHTML($this->dom->getElementsByTagName('body')->item(0)), 6, -7);
                return $this->reInsertJs($html);
            }

        } catch (PageLoadException $e) {
            if (!empty($redirect)) {
                return Redirect::to($redirect->to, $redirect->type);
            }
            if (!View::exists('themes.' . PageBuilder::$theme . '.errors.404')) {
                App::abort(404, $e->getMessage());
            } else {
                return Response::view('themes.' . PageBuilder::$theme . '.errors.404', array('error' => $e->getMessage()), 404);
            }
        }

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
            function ($matches) use (&$index) {
                return $this->js[$index++];
            },
            $html
        );
    }

    public function loadBodyNodes($node)
    {
        foreach ($node->childNodes as $childNode) {
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