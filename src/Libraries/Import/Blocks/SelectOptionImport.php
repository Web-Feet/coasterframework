<?php namespace CoasterCms\Libraries\Import\Blocks;

use CoasterCms\Libraries\Import\AbstractImport;
use CoasterCms\Models\BlockSelectOption;

class SelectOptionImport extends AbstractImport
{

    /**
     * @var array
     */
    protected $_blockSelectOptions;

    /**
     * @var BlockSelectOption
     */
    protected $_currentSelectOption;

    /**
     * @return array
     */
    public function validateRules()
    {
        return [
            'Block Name' => 'required',
            'Option' => 'required',
            'Value' => 'required'
        ];
    }

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Block Name' => 'name',
            'Options' => 'option',
            'Value' => 'value'
        ];
    }

    // TODO

}