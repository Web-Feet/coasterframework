<?php namespace CoasterCms\Http\Controllers;

use CoasterCms\Events\Cms\GeneratePage\LoadedPageResponse;
use CoasterCms\Events\Cms\GeneratePage\LoadErrorTemplate;
use CoasterCms\Events\Cms\GeneratePage\LoadPageTemplate;
use CoasterCms\Exceptions\CmsPageException;
use CoasterCms\Helpers\Cms\Html\DOMDocument;
use CoasterCms\Helpers\Cms\Page\Search;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\Block;
use CoasterCms\Models\PageRedirect;
use CoasterCms\Models\PageVersionSchedule;
use Exception;
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
        // update scheduled versions
        PageVersionSchedule::checkPageVersionIds();

        try {

            // check for forced redirects
            $redirect = PageRedirect::uriHasRedirect();
            if (!empty($redirect) && $redirect->force == 1) {
                throw new CmsPageException('forced redirect', 0, null, redirect($redirect->to, $redirect->type));
            }

            // check for unforced redirects
            if (PageBuilder::getData('is404') && !empty($redirect)) {
                throw new CmsPageException('redirect', 0, null, redirect($redirect->to, $redirect->type));
            }

            // 404, no cms page for current request
            if (PageBuilder::getData('is404')) {
                throw new Exception('cms page not found', 404);
            }

            // 404, hidden page
            if (!PageBuilder::getData('previewVersion') && !PageBuilder::getData('isLive')) {
                throw new Exception('cms page not live', 404);
            }

            // check for form submissions
            if (!empty($_POST)) {
                $formData = PageBuilder::getData('externalTemplate') ? Request::input(config('coaster::frontend.external_form_input')) : Request::all();
                if (!empty($formData['block_id']) && empty($formData['coaster_check'])) { // honeypot option
                    if (!($block = Block::find($formData['block_id']))) {
                        throw new Exception('no block handler for this form data', 500);
                    } else {
                        $pageId = !empty($formData['page_id']) ? $formData['page_id'] : 0;
                        unset($formData['_token']);
                        unset($formData['block_id']);
                        unset($formData['page_id']);
                        unset($formData['coaster_check']);
                        if ($formSubmitResponse = $block->setPageId($pageId)->setVersionId(PageBuilder::pageLiveVersionId())->getTypeObject()->submission($formData)) {
                            throw new CmsPageException('form submission response', 0, null, $formSubmitResponse);
                        }
                    }
                }
            }

            // load page with template
            $templatePath = PageBuilder::templatePath();
            event(new LoadPageTemplate($templatePath));
            if (View::exists($templatePath)) {
                $this->_setHeader('Content-Type', PageBuilder::getData('contentType'));
                $this->responseContent = View::make($templatePath)->render();
            } else {
                throw new Exception('cms page found with non existent template - '.$templatePath, 500);
            }

            // if declared as a search page, must have search block
            if (Search::searchBlockRequired() && !Search::searchBlockExists()) {
                throw new Exception('cms page found without search function', 404);
            }

        } catch (CmsPageException $e) {

            $this->responseContent = $e->getAlternateResponse();

        } catch (Exception $e) {

            $this->_setErrorContent($e);

        }

        $response = $this->_createResponse();
        event(new LoadedPageResponse($response));

        // if response content is html, run modifications
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
                $response->setContent($this->responseContent = $domDocument->saveBodyHTML());
            } else {
                $response->setContent($domDocument->saveHTML($domDocument));
            }
        }

        return $response;
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