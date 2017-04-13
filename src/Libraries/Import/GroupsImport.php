<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Models\PageGroup;
use CoasterCms\Models\PageGroupAttribute;
use CoasterCms\Models\Template;
use Illuminate\Support\Facades\DB;

class GroupsImport extends AbstractImport
{
    /**
     * @var PageGroup
     */
    protected $_currentGroup;

    /**
     *
     */
    const IMPORT_FILE_DEFAULT = 'pages/groups.csv';

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Group Id' => [
                'mapTo' => 'id',
                'validate' => 'required'
            ],
            'Group Name' => [
                'mapTo' => 'name',
                'validate' => 'required'
            ],
            'Group Item Name' => [
                'mapTo' => 'item_name',
                'validate' => 'required'
            ],
            'Url Priority' => [
                'mapTo' => 'url_priority',
                'default' => 50,
                'aliases' => ['Url Priority (Default: 50)']
            ],
            'Default Template' => [
                'mapTo' => 'default_template',
                'mapFn' => '_convertToTemplateId',
                'default' => 0
            ]
        ];
    }

    /**
     *
     */
    protected function _beforeRun()
    {
        // wipe data
        DB::table((new PageGroup)->getTable())->truncate();
        DB::table((new PageGroupAttribute)->getTable())->truncate();
    }

    /**
     *
     */
    protected function _beforeRowMap()
    {
        $this->_currentGroup = new PageGroup;
    }

    /**
     * @param array $importInfo
     * @param string $importFieldData
     */
    protected function _mapTo($importInfo, $importFieldData)
    {
        $this->_currentGroup->{$importInfo['mapTo']} = $importFieldData;
    }

    /**
     *
     */
    protected function _afterRowMap()
    {
        $this->_currentGroup->save();
    }

    /**
     *
     */
    protected function _afterRun()
    {
        $groupAttributes = new Groups\GroupAttributesImport($this->_importPath, $this->_importFileRequired);
        $groupAttributes->run();
        $this->_importErrors = array_merge($this->_importErrors, $groupAttributes->getErrors());
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _convertToTemplateId($importFieldData)
    {
        if ($importFieldData !== '') {
            $template = Template::preload(trim($importFieldData));
            if ($template->exists) {
                return $template->id;
            }
        }
        return 0;
    }

}