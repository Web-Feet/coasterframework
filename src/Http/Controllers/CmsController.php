<?php namespace CoasterCms\Http\Controllers;

use CoasterCms\Exceptions\PageLoadException;
use CoasterCms\Helpers\Core\Page\Feed;
use CoasterCms\Helpers\Core\Html\DOMDocument;
use CoasterCms\Helpers\Core\Page\PageLoader;
use CoasterCms\Helpers\Core\View\FormMessage;
use CoasterCms\Libraries\Blocks\Form;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\PageRedirect;
use Illuminate\Routing\Controller;
use Redirect;
use Request;
use Response;
use View;

class CmsController extends Controller
{

    public $headers;
    public $responseCode;

    public $pageContent;
    public $isHtmlPage;

    public function __construct()
    {
        $this->headers = [];
        $this->responseCode = 200;
        $this->isHtmlPage = true;
    }

    public function generatePage()
    {
        $current_uri = trim(Request::getRequestUri(), '/');
        $redirect = PageRedirect::uriHasRedirect($current_uri);

        try {

            // check for redirects
            if (!empty($redirect) && $redirect->force == 1) {
                return redirect($redirect->to, $redirect->type);
            }

            FormMessage::set_class('error', config('coaster::frontend.form_error_class'));

            PageBuilder::setTheme(config('coaster::frontend.theme'));

            PageBuilder::setPageFromLoader(new PageLoader);

            // check if live when not previewing
            if (!PageBuilder::$isPreview && !PageBuilder::$isLive) {
                throw new PageLoadException('page not live');
            }

            $templatePath = 'themes.' . PageBuilder::$theme . '.';
            if (PageBuilder::$externalTemplate) {
                $templatePath .= 'externals.' . PageBuilder::$externalTemplate;
            } elseif (PageBuilder::$feedExtension) {
                $templatePath .= 'feed.' . PageBuilder::$feedExtension . '.' . PageBuilder::$template;
                $this->headers['Content-Type'] = Feed::content_type();
                $this->isHtmlPage = false;
            } else {
                $templatePath .= 'templates.' . PageBuilder::$template;
            }

            // if template not found 404
            if (!View::exists($templatePath)) {
                throw new PageLoadException('template not found');
            }

            // check for form submissions
            if (!empty($_POST)) {
                $form_submit = Request::all();
                $success = Form::submission($form_submit);
                if (!empty($success)) {
                    return $success;
                }
            }

            $this->pageContent = View::make($templatePath)->render();

            // check if search block loaded (if search page)
            if (PageBuilder::$searchQuery !== false && !PageBuilder::$hasSearch) {
                throw new PageLoadException('no search function found');
            }

        } catch (PageLoadException $e) {

            if (!empty($redirect)) {
                return Redirect::to($redirect->to, $redirect->type);
            }

            $this->responseCode = 404;
            if (!View::exists('themes.' . PageBuilder::$theme . '.errors.404')) {
                $this->pageContent = $e->getMessage();
            } else {
                $this->pageContent = View::make('themes.' . PageBuilder::$theme . '.errors.404', array('error' => $e->getMessage()));
            }

        }

        // load output into PHP DOMDocument
        if ($this->isHtmlPage) {
            $domDocument = new DOMDocument;
            $domDocument->loadHTML($this->pageContent);

            $domDocument->addMetaTag('generator', 'Coaster CMS ' . config('coaster::site.version'));

            if (config('coaster::frontend.strong_tags') == 1) {
                $keywords = explode(", ", str_replace(" and ", ", ", PageBuilder::block('meta_keywords')));
                $domDocument->addStrongTags($keywords);
            }

            // save page content
            if (PageBuilder::$externalTemplate) {
                $domDocument->appendInputFieldNames(config('coaster::frontend.external_form_input'));
                $this->pageContent = $domDocument->saveBodyHMTL();
            } else {
                $this->pageContent = $domDocument->saveHTML($domDocument);
            }
        }

        return $this->_createResponse();
    }

    protected function _createResponse()
    {
        $response = Response::make($this->pageContent, $this->responseCode);

        foreach ($this->headers as $header => $headerValue) {
            $response->header($header, $headerValue);
        }

        return $response;
    }

}