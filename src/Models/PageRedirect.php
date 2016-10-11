<?php namespace CoasterCms\Models;

use Eloquent;
use Request;

class PageRedirect extends Eloquent
{

    protected $table = 'page_redirects';

    public static function uriHasRedirect($redirect_url_encoded = '')
    {
        $redirect_url_encoded = $redirect_url_encoded ?: trim(Request::getRequestUri(), '/');
        $redirect_url_decoded = urldecode($redirect_url_encoded); // decode foreign chars

        $redirectMatches = [];
        foreach ([$redirect_url_encoded, $redirect_url_decoded] as $redirectUrl) {
            $redirectMatches[] = $redirectUrl;
            $redirectMatches[] = '/' . $redirectUrl;
            $redirectMatches[] = '/' . $redirectUrl . '/';
            $redirectMatches[] = $redirectUrl . '/';
        }

        $redirect = self::whereIn('redirect', $redirectMatches)->first();
        if (!empty($redirect)) {
            return $redirect;
        } else {
            $redirects = self::where('redirect', 'LIKE', '%\%')->get();
            foreach ($redirects as $redirect) {
                if (strpos($redirect_url_decoded, substr(trim($redirect->redirect, '/'), 0, -1)) === 0) {
                    return $redirect;
                }
            }
            return null;
        }
    }

    public static function import()
    {
        if (file_exists(public_path() . '/uploads/import/links.csv')) {
            if (($handle = fopen(public_path() . '/uploads/import/links.csv', "r")) !== FALSE) {
                while (($row = fgetcsv($handle, 1000, ',')) !== FALSE) {
                    if (!empty($row[0]) && !empty($row[1])) {
                        $redirect = new PageRedirect;
                        $redirect->redirect = $row[0];
                        $redirect->to = $row[1];
                        $redirect->type = 301;
                        $redirect->force = 0;
                        $redirect->save();
                    }
                }
                fclose($handle);
            }
        }
    }

}