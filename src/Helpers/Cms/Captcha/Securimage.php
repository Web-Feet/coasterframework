<?php namespace CoasterCms\Helpers\Cms\Captcha;

class Securimage
{

    public static function captchaCheck()
    {
        $_POST['captcha_code'] = empty($_POST['captcha_code']) ? '' : $_POST['captcha_code'];
        if (include(public_path(config('coaster::admin.public').'/securimage/securimage.php'))) {
            $secure_image = new \Securimage();
        }
        return isset($secure_image) ? $secure_image->check($_POST['captcha_code']) : false;
    }

}