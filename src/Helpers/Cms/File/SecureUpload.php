<?php namespace CoasterCms\Helpers\Cms\File;

class SecureUpload
{

    /**
     * @return array
     */
    public static function secureFolders()
    {
        return static::_processFolders(config('coaster::site.secure_folders'));
    }

    /**
     * @param string $checkPath
     * @return bool
     */
    public static function isSecurePath($checkPath)
    {
        $secureStorage = static::getBasePath(true) . '/';
        if (strpos($checkPath, $secureStorage) === 0) {
            $checkPath = substr($checkPath, strlen($secureStorage));
        }
        $checkPath = '/' . trim($checkPath, '/');
        foreach (static::secureFolders() as $secureFolder) {
            if (strpos($checkPath, $secureFolder) === 0) {
                $dirName = dirname($secureStorage . $checkPath);
                if (!is_dir($dirName)) {
                    @mkdir($dirName, 0777, true);
                }
                return true;
            }
        }
        return false;
    }

    /**
     * @param string|array $secureFolders
     * @return array
     */
    public static function updateFolders($secureFolders)
    {
        $newSecureFolders = static::_processFolders($secureFolders);
        $oldSecureFolders = static::secureFolders();
        static::_moveFolders($newSecureFolders, $oldSecureFolders);
        static::_moveFolders($oldSecureFolders, $newSecureFolders, false);
        return $newSecureFolders;
    }

    /**
     * @param array $newFolders
     * @param array $oldFolders
     * @param bool $toSecure
     */
    protected static function _moveFolders($newFolders, $oldFolders, $toSecure = true)
    {
        $fromPath = static::getBasePath(!$toSecure);
        $toPath = static::getBasePath($toSecure);
        if ($toSecure) {
            foreach ($newFolders as $folder) {
                @mkdir($fromPath . $folder, 0777, true);
            }
        }
        foreach ($newFolders as $k => $newFolder) {
            foreach ($oldFolders as $oldFolder) {
                if (strpos($newFolder, rtrim($oldFolder, '/') . '/') === 0) {
                    unset($newFolders[$k]);
                }
            }
        }
        $moveFolders = array_diff($newFolders, $oldFolders);
        foreach ($moveFolders as $folder) {
            if (is_dir($fromPath . $folder)) {
                Directory::copy($fromPath . $folder, $toPath . $folder, function($copyFrom) use($fromPath, $oldFolders) {
                    return !in_array(substr($copyFrom, strlen($fromPath)), $oldFolders);
                });
                Directory::remove($fromPath . $folder, $folder == '/' || $toSecure, function($deleteFrom) use($fromPath, $oldFolders) {
                    $deleteFrom = substr($deleteFrom, strlen($fromPath));
                    foreach ($oldFolders as $oldFolder) {
                        if ($deleteFrom == $oldFolder || strpos($oldFolder, rtrim($deleteFrom, '/') . '/') === 0) {
                            return false;
                        }
                    }
                    return true;
                });
            }
        }
    }

    /**
     * Remove subfolders and blank entries then return array
     * @param string|array $secureFolders
     * @return array
     */
    protected static function _processFolders($secureFolders)
    {
        $secureFolders = array_filter(is_array($secureFolders) ? $secureFolders : explode(',', $secureFolders), 'trim');
        foreach ($secureFolders as $k => $folder) {
            $secureFolders[$k] = '/' . trim($folder, '/');
        }
        if (in_array('/', $secureFolders)) {
            return ['/']; // all uploads are secure
        }
        foreach ($secureFolders as $k => $folder) {
            foreach ($secureFolders as $folderCheck) {
                if (strpos($folder, $folderCheck . '/') === 0) {
                    unset($secureFolders[$k]);
                }
            }
        }
        return $secureFolders;
    }

    /**
     * @param bool $secure
     * @param bool $withUploadsFolder
     * @return string
     */
    public static function getBasePath($secure = true, $withUploadsFolder = true)
    {
        return ($secure ? storage_path() : public_path()) . ($withUploadsFolder ? '/uploads' : '');
    }

}