<?php

return [
    'folder_message' => "Note: system files can't be directly removed"
] + \CoasterCms\Helpers\Admin\FileManager::getPermissions(false);