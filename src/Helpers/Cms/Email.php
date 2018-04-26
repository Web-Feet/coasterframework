<?php namespace CoasterCms\Helpers\Cms;

use CoasterCms\Exceptions\CmsPageException;
use Illuminate\Mail\Message;
use PageBuilder;
use Mail;
use Validator;
use View;

class Email
{

    public static function sendFromFormData($templates, $formData, $subject, $to = null, $from = null)
    {
        // get email details to send to
        $emailDetails = [
            'subject' => $subject,
            'to' => $to ?: config('coaster::site.email'),
            'from' => $from ?: config('coaster::site.email'),
            'userEmail' => null
        ];

        // split to addresses
        $emailDetails['to'] = explode(',', $emailDetails['to']);
        $emailCheck = Validator::make($emailDetails, ['from' => 'email|required', 'to' => 'required', 'to.*' => 'required|email']);

        if ($emailCheck->passes()) {

            // get templates
            $emailsViews = [];
            $emailViewRoot = 'themes.' . PageBuilder::getData('theme') . '.emails.';
            foreach (array_filter($templates) as $template) {
                $emailsViews[] = $emailViewRoot . $template . '.';
            }
            $emailsViews = array_merge($emailsViews, [$emailViewRoot]);

            $sendTemplate = null;
            $replyTemplate = null;
            foreach ($emailsViews as $emailsView) {
                if (!$sendTemplate && View::exists($emailsView . 'default')) {
                    $sendTemplate = $emailsView . 'default';
                }
                if (!$replyTemplate && View::exists($emailsView . 'reply')) {
                    $replyTemplate = $emailsView . 'reply';
                }
            }
            if (!$sendTemplate) {
                throw new CmsPageException('No default email template', 500);
            }
            $replyTemplate = $replyTemplate ?: $sendTemplate;

            // generate body
            $body = '';
            foreach ($formData as $field => $value) {
                if (is_array($value)) {
                    $value = implode(", ", $value);
                }
                if (strpos($value, "\r\n") !== false) {
                    $value = "<br />" . str_replace("\r\n", "<br />", $value);
                }
                $body .= ucwords(str_replace('_', ' ', $field)) . ": $value <br />";
                if (stristr($field, 'email') !== false) {
                    $emailDetails['userEmail'] = $value;
                }
            }

            Mail::send($sendTemplate, ['body' => $body, 'formData' => $formData, 'form_data' => $formData], function (Message $message) use ($emailDetails) {
                if ($emailDetails['userEmail']) {
                    $message->replyTo($emailDetails['userEmail']);
                }
                $message->to($emailDetails['to']);
                $message->from($emailDetails['from']);
                $message->subject($emailDetails['subject']);
            });

            if ($emailDetails['userEmail']) {
                Mail::send($replyTemplate, ['body' => $body, 'formData' => $formData, 'form_data' => $formData], function (Message $message) use ($emailDetails) {
                    $message->to($emailDetails['userEmail']);
                    $message->from($emailDetails['from']);
                    $message->subject($emailDetails['subject']);
                });
            }

            return !Mail::failures();

        } else {
            return false;
        }

    }

}