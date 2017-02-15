<?php namespace CoasterCms\Helpers\Cms\View;

use CoasterCms\Models\Block;
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
            $useReal = !empty($formOptions['real_page_id']) ? $formOptions['real_page_id'] : false;
            $honeyPot = !empty($formOptions['honeyPot']) ? $formOptions['honeyPot'] : true;

            $formOptions['url'] = (Request::input('forwarded_url') ?: Request::fullUrl()) . '#form' . $block->id;
            $formOptions['files'] = !empty($formOptions['files']) ? $formOptions['files'] : true;
            $formOptions['id'] = !empty($formOptions['id']) ? $formOptions['id']:'form' . $block->id;

            unset($formOptions['view']);
            unset($formOptions['version']);
            unset($formOptions['useReal']);
            unset($formOptions['honeyPot']);

            $formView = View::make($template, $templateData)->render();
            return View::make('coasterCms::form.wrap', ['blockId' => $block->id, 'honeyPot' => $honeyPot, 'useReal' => $useReal, 'formAttributes' => $formOptions, 'formView' => $formView])->render();
        } else {
            return 'Form template '.$template.' not found';
        }
    }

}