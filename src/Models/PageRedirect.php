<?php namespace CoasterCms\Models;

class PageRedirect extends _BaseEloquent
{

    protected $table = 'page_redirects';

    public static function get($redirect_url_encoded)
    {
        $redirect_url = urldecode($redirect_url_encoded); // decode foreign chars
        $redirect = self::where('redirect', '=', $redirect_url)
            ->orWhere('redirect', '=', $redirect_url_encoded)
            ->orWhere('redirect', '=', '/' . $redirect_url)
            ->orWhere('redirect', '=', '/' . $redirect_url_encoded)
            ->orWhere('redirect', '=', '/' . $redirect_url . '/')
            ->orWhere('redirect', '=', '/' . $redirect_url_encoded . '/')
            ->orWhere('redirect', '=', $redirect_url . '/')
            ->orWhere('redirect', '=', $redirect_url_encoded . '/')
            ->first();
        if (!empty($redirect)) {
            return $redirect;
        } else {
            $redirects = self::where('redirect', 'LIKE', '%\%')->get();
            foreach ($redirects as $redirect) {
                if (strpos($redirect_url, substr(trim($redirect->redirect, '/'), 0, -1)) === 0) {
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