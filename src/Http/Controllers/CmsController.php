<?php namespace CoasterCms\Http\Controllers;

use CoasterCms\Events\Cms\InitializePageBuilder;
use CoasterCms\Events\Cms\LoadedPageResponse;
use CoasterCms\Events\Cms\LoadErrorTemplate;
use CoasterCms\Events\Cms\LoadPageTemplate;
use CoasterCms\Events\Cms\ReturnPageResponse;
use CoasterCms\Exceptions\CmsPageException;
use CoasterCms\Helpers\Core\Html\DOMDocument;
use CoasterCms\Helpers\Core\Page\Feed;
use CoasterCms\Helpers\Core\Page\PageLoader;
use CoasterCms\Helpers\Core\Page\Search;
use CoasterCms\Libraries\Blocks\Form;
use CoasterCms\Libraries\Builder\PageBuilder\PageBuilderInstance;
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
        $pageLoader = PageLoader::class;
        $pageBuilder = [
            'class' => PageBuilderInstance::class,
            'args' => []
        ];

        // try to load cms page for current request
        event(new InitializePageBuilder($pageLoader, $pageBuilder));
        PageBuilder::setClass($pageBuilder['class'], $pageLoader, $pageBuilder['args']);
        PageBuilder::setTheme(config('coaster::frontend.theme'));
        
        $templatePathRoot = 'themes.' . PageBuilder::getData('theme') . '.';
        $currentUri = trim(Request::getRequestUri(), '/');

        try {

            // check for forced redirects
            $redirect = PageRedirect::uriHasRedirect($currentUri);
            if (!empty($redirect) && $redirect->force == 1) {
                throw new CmsPageException('forced redirect', 0, null, redirect($redirect->to, $redirect->type));
            }

            // check for unforced redirects
            if (PageBuilder::getData('is404') && !empty($redirect)) {
                throw new CmsPageException('redirect', 0, null, redirect($redirect->to, $redirect->type));
            }

            // 404, no cms page for current request
            if (PageBuilder::getData('is404')) {
                throw new CmsPageException('cms page not found', 404);
            }

            // 404, hidden page
            if (!PageBuilder::getData('isPreview') && !PageBuilder::getData('isLive')) {
                throw new CmsPageException('cms page not live', 404);
            }

            // check for form submissions
            if (!empty($_POST) && ($formSubmitResponse = Form::submission(Request::all())) !== false) {
                throw new CmsPageException('form submission response', 0, null, $formSubmitResponse);
            }

            // set template
            if (PageBuilder::getData('externalTemplate')) {
                $this->_setHtmlContentType();
                $templatePath = $templatePathRoot . 'externals.' . PageBuilder::getData('externalTemplate');
            } elseif ($extension = PageBuilder::getData('feedExtension')) {
                $this->_setHeader('Content-Type', Feed::getMimeType($extension));
                $templatePath = $templatePathRoot . 'feed.' . PageBuilder::getData('feedExtension') . '.' . PageBuilder::getData('template');
            } else {
                $this->_setHtmlContentType();
                $templatePath = $templatePathRoot . 'templates.' . PageBuilder::getData('template');
            }

            // load page with template
            event(new LoadPageTemplate($templatePath));
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
                event(new LoadErrorTemplate($templatePath));
                if (View::exists($templatePath)) {
                    $this->_setHtmlContentType();
                    $this->responseContent = View::make($templatePath, ['error' => $e->getMessage()])->render();
                } else {
                    $this->responseContent = $e->getMessage();
                }

            }

        }

        $response = $this->_createResponse();
        event(new LoadedPageResponse($response));

        // if response content is html string, run modifications
        if (!empty($response->headers->get('content-type')) && stripos($response->headers->get('content-type'), 'html') !== false) {
            $domDocument = new DOMDocument;
            $domDocument->loadHTML($response->getContent());

            $domDocument->addMetaTag('generator', 'Coaster CMS ' . config('coaster::site.version'));

            if (config('coaster::frontend.strong_tags') == 1) {
                $keywords = explode(", ", str_replace(" and ", ", ", PageBuilder::block('meta_keywords')));
                $domDocument->addStrongTags($keywords);
            }

            // save page content
            if (PageBuilder::getData('externalTemplate')) {
                $domDocument->appendInputFieldNames(config('coaster::frontend.external_form_input'));
                $response->setContent($this->responseContent = $domDocument->saveBodyHMTL());
            } else {
                $response->setContent($domDocument->saveHTML($domDocument));
            }
        }

        return $response;
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