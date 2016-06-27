<?php namespace CoasterCms\Http\Controllers;

use CoasterCms\Exceptions\CmsPageException;
use CoasterCms\Helpers\Core\Html\DOMDocument;
use CoasterCms\Helpers\Core\Page\Feed;
use CoasterCms\Helpers\Core\Page\PageLoader;
use CoasterCms\Helpers\Core\Page\Search;
use CoasterCms\Helpers\Core\View\FormMessage;
use CoasterCms\Libraries\Blocks\Form;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\PageRedirect;
use Illuminate\Routing\Controller;
use Request;
use Response;
use View;

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
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function generatePage()
    {
        FormMessage::set_class('error', config('coaster::frontend.form_error_class'));

        PageBuilder::setTheme(config('coaster::frontend.theme'));
        $templatePathRoot = 'themes.' . PageBuilder::$theme . '.';

        $currentUri = trim(Request::getRequestUri(), '/');

        try {

            // check for forced redirects
            $redirect = PageRedirect::uriHasRedirect($currentUri);
            if (!empty($redirect) && $redirect->force == 1) {
                throw new CmsPageException('forced redirect', 0, null, redirect($redirect->to, $redirect->type));
            }

            // try to load cms page for current request
            PageBuilder::setPageFromLoader(new PageLoader);

            // check for unforced redirects
            if (PageBuilder::$is404 && !empty($redirect)) {
                throw new CmsPageException('redirect', 0, null, redirect($redirect->to, $redirect->type));
            }

            // 404, no cms page for current request
            if (PageBuilder::$is404) {
                throw new CmsPageException('cms page not found', 404);
            }

            // 404, hidden page
            if (!PageBuilder::$isPreview && !PageBuilder::$isLive) {
                throw new CmsPageException('cms page not live', 404);
            }

            // check for form submissions
            if (!empty($_POST) && ($formSubmitResponse = Form::submission(Request::all())) !== false) {
                throw new CmsPageException('form submission response', 0, null, $formSubmitResponse);
            }

            // set template
            if (PageBuilder::$externalTemplate) {
                $this->_setHtmlContentType();
                $templatePath = $templatePathRoot . 'externals.' . PageBuilder::$externalTemplate;
            } elseif (PageBuilder::$feedExtension) {
                $this->_setHeader('Content-Type', Feed::content_type());
                $templatePath = $templatePathRoot . 'feed.' . PageBuilder::$feedExtension . '.' . PageBuilder::$template;
            } else {
                $this->_setHtmlContentType();
                $templatePath = $templatePathRoot . 'templates.' . PageBuilder::$template;
            }

            // load page with template
            if (View::exists($templatePath)) {
                $this->responseContent = View::make($templatePath)->render();
            } else {
                throw new CmsPageException('cms page found with non existent template', 500);
            }

            // if declared as a search page, must have search block
            if (Search::searchBlockRequired() && !Search::searchBlockExists()) {
                throw new CmsPageException('cms page found without search function', 404);
            }

        } catch (CmsPageException $e) {

            if (!($this->responseContent = $e->getAlternateResponse())) {

                $this->responseCode = $e->getCode();
                $templatePath = $templatePathRoot . 'errors.' . $this->responseCode;

                // display error loading page
                if (View::exists($templatePath)) {
                    $this->_setHtmlContentType();
                    $this->responseContent = View::make($templatePath, ['error' => $e->getMessage()])->render();
                } else {
                    $this->responseContent = $e->getMessage();
                }

            }

        }

        // if response is html, run modifications
        if (!empty($this->headers['Content-Type']) && stripos($this->headers['Content-Type'], 'html') !== false) {
            $domDocument = new DOMDocument;
            $domDocument->loadHTML($this->responseContent);

            $domDocument->addMetaTag('generator', 'Coaster CMS ' . config('coaster::site.version'));

            if (config('coaster::frontend.strong_tags') == 1) {
                $keywords = explode(", ", str_replace(" and ", ", ", PageBuilder::block('meta_keywords')));
                $domDocument->addStrongTags($keywords);
            }

            // save page content
            if (PageBuilder::$externalTemplate) {
                $domDocument->appendInputFieldNames(config('coaster::frontend.external_form_input'));
                $this->responseContent = $domDocument->saveBodyHMTL();
            } else {
                $this->responseContent = $domDocument->saveHTML($domDocument);
            }
        }

        return $this->_createResponse();
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