<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\Captcha\Securimage;
use CoasterCms\Helpers\Cms\Email;
use CoasterCms\Helpers\Cms\View\FormWrap;
use CoasterCms\Libraries\Builder\FormMessage;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\BlockFormRule;
use CoasterCms\Models\FormSubmission;
use CoasterCms\Models\Page;
use CoasterCms\Models\Theme;
use Request;
use Session;
use Validator;

class Form extends String_
{
    /**
     * @var array
     */
    public static $blockSettings = ['Manage form input validation rules' => 'themes/forms'];

    /**
     * Display form view
     * @param string $content
     * @param array $options
     * @return \Illuminate\Contracts\View\View|string
     */
    public function display($content, $options = [])
    {
        $formData = $this->_defaultData($content);
        $template = $formData->template ?: (!empty($options['view']) ? $options['view'] : $this->_block->name);
        $templatePath = 'themes.' . PageBuilder::getData('theme') . '.blocks.forms.' . $template;
        return FormWrap::view($this->_block, $options, $templatePath, ['form_data' => $formData]);
    }

    /**
     * Save form data and send email
     * @param array $formData
     * @return bool|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function submission($formData)
    {
        if ($form_settings = $this->_block->getContent()) {
            $form_settings = unserialize($form_settings);
            $form_rules = BlockFormRule::get_rules($form_settings->template);
            $v = Validator::make($formData, $form_rules);
            $captcha = Securimage::captchaCheck();

            // check form rules
            if ($v->passes() && !($form_settings->captcha == true && !$captcha)) {
                // delete blank and system fields
                unset($formData['captcha_code']);

                $files = array();
                foreach ($formData as $field => $value) {
                    if (empty($value)) {
                        unset($formData[$field]);
                    }
                    if (Request::hasFile($field)) {
                        $files[$field] = $value;
                        unset($formData[$field]);
                    }
                }

                // save form submission
                $form_submission = new FormSubmission;
                $form_submission->form_block_id = $this->_block->id;
                $form_submission->content = serialize($formData);
                $form_submission->sent = 0;
                $form_submission->from_page_id = PageBuilder::pageId();
                $form_submission->save();

                foreach ($files as $field => $value) {
                    if (Request::hasFile($field)) {
                        $upload_folder = '/uploads/system/forms/' . $this->_block->id;
                        $full_upload_path = public_path() . $upload_folder;
                        if (!file_exists($full_upload_path)) {
                            mkdir($full_upload_path, 0755, true);
                        }
                        $unique_filename = $field . ' ' . $form_submission->id . ' ' . Request::file($field)->getClientOriginalName();
                        Request::file($field)->move($full_upload_path, $unique_filename);
                        $formData[$field] = \HTML::link($upload_folder . '/' . $unique_filename, $unique_filename);
                    }
                }

                $form_submission->content = serialize($formData);
                $form_submission->save();

                $subject = config('coaster::site.name') . ': New Form Submission - ' . $this->_block->label;
                $template = $form_settings->template?:$this->_block->name;
                $sentEmail = Email::sendFromFormData([$template], $formData, $subject, $form_settings->email_to, $form_settings->email_from);

                if ($sentEmail) {
                    $form_submission->sent = 1;
                    $form_submission->save();
                }

                Session::set('form_data', $form_submission);
                return \redirect(PageBuilder::pageUrl($form_settings->page_to));
            } else {
                FormMessage::set($v->messages());
                if (!$captcha) {
                    FormMessage::add('captcha_code', 'Invalid Captcha Code!');
                }
            }

        }

        return false;
    }

    /**
     * Display form settings
     * Template selector should only should if custom template selected (otherwise deprecated)
     * @param string $postContent
     * @return string
     */
    public function edit($postContent)
    {
        $formData = $this->_defaultData($postContent);
        $formData->template = $formData->template == $this->_block->name ? 0 : $formData->template;

        $this->_editViewData['pageList'] = Page::get_page_list();
        $this->_editViewData['formTemplates'] = [0 => '-- Use view from template --'];
        $theme = Theme::find(config('coaster::frontend.theme'));
        if (!empty($theme)) {
            $forms = base_path('/resources/views/themes/' . $theme->theme . '/blocks/forms');
            if (is_dir($forms)) {
                foreach (scandir($forms) as $form) {
                    if (!is_dir($forms . DIRECTORY_SEPARATOR . $form)) {
                        $form_file = explode('.', $form);
                        if (!empty($form_file[0])) {
                            $this->_editViewData['formTemplates'][$form_file[0]] = $form_file[0] . (strpos(file_get_contents($forms . DIRECTORY_SEPARATOR . $form), 'captcha') ? ' (supports captcha)' : ' (does not support captcha)');
                        }
                    }
                }
            }
        }

        return parent::edit($formData);
    }

    /**
     * Save form settings
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        $formData = $this->_defaultData('');
        $formData->captcha = !empty($postContent['captcha']) ? true : false;
        $formData->email_from = $postContent['from'];
        $formData->email_to = $postContent['to'];
        $formData->template = !empty($postContent['template'])? $postContent['template'] : 0;
        $formData->page_to = $postContent['page'];
        return $this->save($formData ? serialize($formData) : '');
    }

    /**
     * Form blocks data should be ignored in page search
     * @param null|string $content
     * @return null
     */
    public function generateSearchText($content)
    {
        return null;
    }

    /**
     * Return valid form data
     * @param $content
     * @return \stdClass
     */
    protected function _defaultData($content)
    {
        $content = @unserialize($content);
        if (empty($content) || !is_a($content, \stdClass::class)) {
            $content = new \stdClass;
        }
        $content->captcha = !empty($content->captcha) ? $content->captcha : false;
        $content->email_from = !empty($content->email_from) ? $content->email_from : '';
        $content->email_to = !empty($content->email_to) ? $content->email_to : '';
        $content->template = !empty($content->template) ? $content->template : '';
        $content->page_to = !empty($content->page_to) ? $content->page_to : '';
        return $content;
    }

}
