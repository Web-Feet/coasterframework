<?php


$filePermissions['product']['prevent_delete'] = true;
$filePermissions['product']['prevent_rename'] = true;
$filePermissions['category']['prevent_delete'] = true;
$filePermissions['category']['prevent_rename'] = true;

return [
    'folder_message' => "Note: some folders can't be directly removed as they may be used for ecomm features"
];