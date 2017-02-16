<?php

namespace CoasterCms\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class UploadChecks {

    public function handle(Request $request, Closure $next)
    {
        if ($request->getMethod() == 'POST') {
            $uploads = $request->allFiles();
            $this->_uploadErrorCheck($uploads);
        }

        return $next($request);
    }

    protected function _uploadErrorCheck($uploads)
    {
        if (!empty($uploads)) {
            if (is_array($uploads)) {
                foreach ($uploads as $upload) {
                    $this->_uploadErrorCheck($upload);
                }
            } elseif ($uploads->getError()) {
                throw new \Exception($uploads->getErrorMessage());
            }
        }
    }

}
