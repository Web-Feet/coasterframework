<?php namespace CoasterCms\Helpers\Cms\View;

use CoasterCms\Models\Block;
use PageBuilder;
use Request;
use View;

class FormWrap
{

    /**
     * @param Block $block
     * @param array $formOptions
     * @param string $template
     * @param array $templateData
     * @return string
     */
    public static function view($block, $formOptions, $template, $templateData = [])
    {
        if (View::exists($template)) {
            $formOptions += [
                'real_page_id' => false,
                'page_id' => null,
                'honeyPot' => true,
                'url' => (Request::input('forwarded_url') ?: Request::fullUrl()),
                'files' => true,
                'id' => 'form' . $block->id
            ];
            $formOptions['url'] .= '#' . $formOptions['id'];

            $pageId = $formOptions['page_id'] ?: PageBuilder::pageId($formOptions['real_page_id']);
            $honeyPot = $formOptions['honeyPot'];

            $formTemplate = $formOptions['view'] ?? '';

            unset($formOptions['real_page_id']);
            unset($formOptions['page_id']);
            unset($formOptions['view']);
            unset($formOptions['version']);
            unset($formOptions['honeyPot']);

            $formView = View::make($template, $templateData)->render();
            return View::make('coasterCms::form.wrap', ['blockId' => $block->id, 'pageId' => $pageId, 'honeyPot' => $honeyPot, 'formAttributes' => $formOptions, 'formView' => $formView, 'formTemplate' => $formTemplate])->render();
        } else {
            return 'Form template '.$template.' not found';
        }
    }

}