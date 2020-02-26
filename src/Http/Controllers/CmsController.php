<?php

namespace CoasterCms\Http\Controllers;

use CoasterCms\Events\Cms\GeneratePage\LoadedPageResponse;
use CoasterCms\Events\Cms\GeneratePage\LoadErrorTemplate;
use CoasterCms\Events\Cms\GeneratePage\LoadPageTemplate;
use CoasterCms\Events\Cms\SubmitFormData;
use CoasterCms\Exceptions\CmsPageException;
use CoasterCms\Helpers\Cms\File\SecureUpload;
use CoasterCms\Helpers\Cms\Html\DOMDocument;
use CoasterCms\Helpers\Cms\Page\PageCache;
use CoasterCms\Models\Block;
use CoasterCms\Models\Language;
use CoasterCms\Models\PageRedirect;
use CoasterCms\Models\PageVersionSchedule;
use Exception;
use Illuminate\Routing\Controller;
use PageBuilder;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class CmsController extends Controller
{

    /**
     * @var array
     */
    protected $headers;

    /**
     * @var int
     */
    protected $responseCode;

    /**
     * @var string|\Symfony\Component\HttpFoundation\Response
     */
    protected $responseContent;

    /**
     * CmsController constructor.
     */
    public function __construct()
    {
        $this->headers = [];
        $this->responseCode = 200;
        $this->responseContent = '';
    }

    /**
     * @param string $file
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getSecureUpload($file)
    {
        $secureFilePath = SecureUpload::getBasePath() . '/' . $file;

        if (file_exists($secureFilePath)) {
            $size = filesize($secureFilePath);
            $type = \GuzzleHttp\Psr7\mimetype_from_filename($secureFilePath);
            return response()->download($secureFilePath, null, ['size' => $size, 'Content-Type' => $type], null);
        } else {
            return $this->generatePage();
        }
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function generatePage()
    {
        // update scheduled versions
        PageVersionSchedule::checkPageVersionIds();
        $isExternal = false;

        try {

            // check for redirects
            $redirect = PageRedirect::uriHasRedirect();
            if (!empty($redirect) && ($redirect->force || PageBuilder::getData('is404'))) {
                $this->responseContent = redirect($redirect->to, $redirect->type);
                throw new CmsPageException('redirect' . ($redirect->force ? ' forced' : ''));
            }

            // 404, no cms page for current request
            if (PageBuilder::getData('is404')) {
                throw new CmsPageException('cms page not found', 404);
            }

            // 404, hidden page
            if (!PageBuilder::getData('previewVersion') && !PageBuilder::getData('isLive')) {
                throw new CmsPageException('cms page not live', 404);
            }

            $isExternal = (stripos(PageBuilder::getData('customTemplate'), 'externals.') === 0);

            // check for form submissions
            if (!empty($_POST)) {
                $formData = $isExternal ? Request::input(config('coaster::frontend.external_form_input')) : Request::all();
                if (!empty($formData['block_id']) && empty($formData['coaster_check'])) { // honeypot option
                    if (!($block = Block::find($formData['block_id']))) {
                        throw new Exception('no block handler for this form data', 500);
                    } else {
                        event(new SubmitFormData($block, $formData));
                        if ($formData) {
                            $pageId = !empty($formData['page_id']) ? $formData['page_id'] : 0;
                            unset($formData['_token']);
                            unset($formData['block_id']);
                            unset($formData['page_id']);
                            unset($formData['coaster_check']);
                            if ($formSubmitResponse = $block->setPageId($pageId)->setVersionId(PageBuilder::pageLiveVersionId())->getTypeObject()->submission($formData)) {
                                $this->responseContent = $formSubmitResponse;
                                throw new CmsPageException('form submission response', 0, null);
                            }
                        }
                    }
                }
            }

            // load page with template
            $templatePath = PageBuilder::templatePath();
            event(new LoadPageTemplate($templatePath));
            if (View::exists($templatePath)) {
                $this->_setHeader('Content-Type', PageBuilder::getData('contentType'));
                $this->responseContent = $this->_getRenderedTemplate($templatePath);
            } else {
                throw new CmsPageException('cms page found with non existent template - ' . $templatePath, 500);
            }

            // if declared as a search page, must have search block
            if (PageBuilder::getData('searchRequired') && !PageBuilder::logs('method')->has('search')) {
                throw new CmsPageException('No search function implemented on this page', 404);
            }
        } catch (Exception $e) {

            if (!$this->responseContent) {
                $this->_setErrorContent($e);
            }
        }

        $response = $this->_createResponse();
        event(new LoadedPageResponse($response));

        // if response content is html, run modifications
        if (!empty($response->headers->get('content-type')) && stripos($response->headers->get('content-type'), 'html') !== false) {
            $domDocument = new DOMDocument;
            $domDocument->loadHTML($response->getContent());

            $domDocument->addMetaTag('generator', 'Coaster CMS ' . config('coaster::site.version'));
            $domDocument->updateTokens(); // fpc fix for tokens

            if (config('coaster::frontend.strong_tags') == 1) {
                $keywords = explode(", ", str_replace(" and ", ", ", PageBuilder::block('meta_keywords')));
                $domDocument->addStrongTags($keywords);
            }

            // save page content
            if ($isExternal) {
                $domDocument->appendInputFieldNames(config('coaster::frontend.external_form_input'));
                $response->setContent($domDocument->saveBodyHTML());
            } else {
                $response->setContent($domDocument->saveHTML($domDocument));
            }
        }

        return $response;
    }

    /**
     * @param string $templatePath
     * @return string
     */
    protected function _getRenderedTemplate($templatePath)
    {
        $pageId = PageBuilder::pageId() . '.' . Language::current();
        $hash = hash('haval256,3', serialize(Request::input()));
        $renderedTemplate = PageCache::remember($pageId, $hash, function () use ($templatePath) {
            return View::make($templatePath)->render();
        });
        if (!PageBuilder::canCache()) {
            PageCache::forget($pageId, $hash);
        }
        return $renderedTemplate;
    }

    /**
     * @param Exception $e
     */
    protected function _setErrorContent(Exception $e)
    {
        $this->responseCode = !empty(\Symfony\Component\HttpFoundation\Response::$statusTexts[$e->getCode()]) ? $e->getCode() : 500;
        $templatePath = PageBuilder::themePath() . 'errors.' . $this->responseCode;

        // display error loading page
        event(new LoadErrorTemplate($templatePath));
        if (View::exists($templatePath)) {
            $this->_setHtmlContentType();
            $this->responseContent = View::make($templatePath, ['e' => $e, 'error' => $e->getMessage()])->render();
        } else {
            $this->responseContent = $e->getMessage();
        }
    }

    /**
     * @param string $name
     * @param string $value
     */
    protected function _setHeader($name, $value)
    {
        $this->headers[$name] = $value;
    }

    /**
     * Set response content type to html
     */
    protected function _setHtmlContentType()
    {
        $this->headers['Content-Type'] = 'text/html; charset=UTF-8';
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    protected function _createResponse()
    {
        if (is_a($this->responseContent, \Symfony\Component\HttpFoundation\Response::class)) {
            $response = $this->responseContent;
        } else {
            $response = Response::make($this->responseContent, $this->responseCode);
        }

        foreach ($this->headers as $header => $headerValue) {
            $response->header($header, $headerValue);
        }

        return $response;
    }
}
