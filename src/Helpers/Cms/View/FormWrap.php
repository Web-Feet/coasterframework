<?php namespace CoasterCms\Helpers\Cms\View;

use Request;
use View;

class FormWrap
{

    public static function view($block, $formOptions, $template, $templateData = [])
    {
        if (View::exists($template)) {
            $useReal = !empty($formOptions['real_page_id']) ? $formOptions['real_page_id'] : false;

            $formOptions['url'] = (Request::input('forwarded_url') ?: Request::fullUrl()) . '#form' . $block->id;
            $formOptions['files'] = !empty($formOptions['files']) ? $formOptions['files'] : true;
            $formOptions['id'] = !empty($formOptions['id']) ? $formOptions['id']:'form' . $block->id;

            unset($formOptions['view']);
            unset($formOptions['version']);
            unset($formOptions['useReal']);

            $formView = View::make($template, $templateData)->render();
            return View::make('coasterCms::form.wrap', ['blockId' => $block->id, 'useReal' => $useReal, 'formAttributes' => $formOptions, 'formView' => $formView]);
        } else {
            return 'Form template '.$template.' not found';
        }
    }

}