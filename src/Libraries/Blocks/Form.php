<?php namespace CoasterCms\Libraries\Blocks;

use CoasterCms\Helpers\Cms\Captcha\Securimage;
use CoasterCms\Helpers\Cms\Email;
use CoasterCms\Helpers\Cms\View\FormWrap;
use CoasterCms\Facades\FormMessage;
use Illuminate\Support\Facades\Cache;
use PageBuilder;
use CoasterCms\Models\Block;
use CoasterCms\Models\BlockFormRule;
use CoasterCms\Models\FormSubmission;
use CoasterCms\Models\Page;
use CoasterCms\Models\Theme;
use Request;
use Response;
use Session;
use Validator;

class Form extends AbstractBlock
{
    /**
     * @var array
     */
    public static $blockSettings = ['Manage form input validation rules' => 'themes/forms'];

    /**
     * @var string
     */
    protected $_displayedTemplatesKey;

    /**
     * Repeater constructor.
     * @param Block $block
     */
    public function __construct(Block $block)
    {
        parent::__construct($block);
        $this->_displayViewDirs[] = 'forms';
        $this->_displayedTemplatesKey = 'displayed_form_' . $this->_block->id . '_templates';
    }

    /**
     * Display form view
     * @param string $content
     * @param array $options
     * @return string
     */
    public function display($content, $options = [])
    {
        $formData = $this->_defaultData($content);
        $view = !empty($options['view']) ? $options['view'] : $formData->template;
        if ($view) {
            $formTemplates = Cache::get($this->_displayedTemplatesKey, []);
            $formTemplates[] = $view;
            $formTemplates = array_unique($formTemplates);
            Cache::put($this->_displayedTemplatesKey, $formTemplates, 60 + abs((int)config('coaster::frontend.cache')));
        }
        return FormWrap::view($this->_block, $options, $this->displayView($view), ['form_data' => $formData]);
    }

    /**
     * Save form data
     * @param array $formData
     * @return FormSubmission
     */
    public function submissionSaveData(array $formData)
    {
        // remove empty values
        $formData = array_filter($formData);

        // get array of files to upload
        $files = [];
        foreach ($formData as $field => $value) {
            if (Request::hasFile($field)) {
                $files[$field] = Request::file($field);
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
        $form_submission->uploadFiles($files);
        return $form_submission;
    }

    /**
     * Send the form data by email
     * @param array $formData
     * @param \stdClass $form_settings
     * @return boolean
     */
    public function submissionSendEmail(array $formData, \stdClass $form_settings)
    {
        $subject = config('coaster::site.name') . ': New Form Submission - ' . $this->_block->label;
        return Email::sendFromFormData([$form_settings->template, $this->_block->name], $formData, $subject, $form_settings->email_to, $form_settings->email_from);
    }

    /**
     * Save form data and send email
     * @param array $formData
     * @return bool|\Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     */
    public function submission($formData)
    {
        if ($form_settings = $this->_block->getContent(true)) {
            $form_settings = $this->_defaultData($form_settings);

            if (!empty($formData['form_template'])) {
                $formTemplates = Cache::get($this->_displayedTemplatesKey, []);
                if (in_array($formData['form_template'], $formTemplates)) {
                    $form_settings->template = $formData['form_template'];
                }
            }

            unset($formData['form_template']);

            $form_rules = BlockFormRule::get_rules($form_settings->template ?: $this->_block->name);
            $v = Validator::make($formData, $form_rules);
            $captcha = Securimage::captchaCheck();

            // check form rules
            if ($v->passes() && !($form_settings->captcha && !$captcha)) {
                // delete blank and system fields
                unset($formData['captcha_code']);

                // Save data function (override this function to save data differently)
                $form_submission = $this->submissionSaveData($formData);
                if (!$form_submission->id) {
                    FormMessage::add('submission_save_error', 'Unable to save the form.');
                }

                // Send email
                if ($this->submissionSendEmail(unserialize($form_submission->content), $form_settings)) {
                    $form_submission->sent = 1;
                    $form_submission->save();
                }

                Session::put('form_data', $form_submission);
                return \redirect(PageBuilder::pageUrl($form_settings->page_to));
            } else {
                FormMessage::set($v->messages());
                if (!$captcha && $form_settings->captcha) {
                    FormMessage::add('captcha_code', 'Invalid Captcha Code, try again.');
                }
            }

        } else {
            return Response::make('No form settings found, try saving the form block in the admin.', 500);
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
     * Save form settings (Admin)
     * @param array $postContent
     * @return static
     */
    public function submit($postContent)
    {
        $formData = $this->_defaultData('');
        $formData->captcha = !empty($postContent['captcha']) ? true : false;
        $formData->email_from = $postContent['from'];
        $formData->email_to = $postContent['to'];
        $formData->template = !empty($postContent['template'])? $postContent['template'] : '';
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
