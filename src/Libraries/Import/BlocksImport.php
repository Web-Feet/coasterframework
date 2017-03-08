<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Helpers\Cms\Page\PageLoaderDummy;
use CoasterCms\Libraries\Builder\PageBuilder;
use View;

class BlocksImport extends AbstractImport
{

    /**
     * @var array
     */
    protected $_blockSettings;

    /**
     * @var string
     */
    protected $_currentBlockName;

    /**
     * @var string
     */
    protected $_categoryImport;

    /**
     * BlocksImport constructor.
     * @param $importFile
     * @param bool $requiredFile
     */
    public function __construct($importFile, $requiredFile = true)
    {
        parent::__construct($importFile, $requiredFile);
        $this->_blockSettings = [];
    }

    /**
     * @return array
     */
    public function validateRules()
    {
        return [
            'Block Name' => 'required',
            'Block Label' => '',
            'Block Note' => '',
            'Block Category' => '',
            'Block Type' => '',
            'Global (show in site-wide)' => '',
            'Global (show in pages)' => '',
            'Templates' => '',
            'Block Order' => ''
        ];
    }

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Block Name' => 'name',
            'Block Label' => 'label',
            'Block Note' => 'note',
            'Block Category' => 'category_id',
            'Block Type' => 'type',
            'Global (show in site-wide)' => 'global_site',
            'Global (show in pages)' => 'global_pages',
            'Templates' => 'templates',
            'Block Order' => 'order'
        ];
    }

    /**
     * @param string $importFieldName
     * @param string $importFieldData
     */
    protected function _importField($importFieldName, $importFieldData)
    {
        $importFieldData = trim($importFieldData);
        $mappedName = $this->_fieldMap[$importFieldName];
        if ($importFieldData !== '') {
            if (in_array($mappedName, ['global_site', 'global_pages'])) {
                $importFieldData = (empty($importFieldData) || strtolower($importFieldData) == 'false' || strtolower($importFieldData) == 'no' || strtolower($importFieldData) == 'n');
            }
            if ($mappedName == 'name') {
                $importFieldData = strtolower($importFieldData);
            }
            if (!empty($blockSettings['category_id'])) { // TODO
                $blockSettings['category_id'] = $this->_getBlockCategoryIdFromName($blockSettings['category_id']);
            }

            $this->_blockSettings[$this->_currentBlockName][$mappedName] = $importFieldData;
        }
    }

    /**
     *
     */
    protected function _startRowImport()
    {
        $this->_currentBlockName = $this->_importCurrentRow['Block Name'];
    }

    public function run()
    {
        if (array_key_exists('theme', $this->_additionalData) && $this->validate()) {
            $theme = $this->_additionalData['theme'];

            $categoryImport = new \CoasterCms\Libraries\Import\Blocks\CategoryImport(
                base_path('resources/views/themes/' . $theme->theme . '/import/blocks/categories.csv'),
                false
            );
            $categoryImport->run();

            $formRulesImport = new \CoasterCms\Libraries\Import\Blocks\FormRulesImport(
                base_path('resources/views/themes/' . $theme->theme . '/import/blocks/form_rules.csv'),
                false
            );
            $formRulesImport->run();

            $this->_processFiles(); // TODO move block import funcs from block updater and eventually replace it with this file

            $selectOptionsImport = new \CoasterCms\Libraries\Import\Blocks\SelectOptionImport(
                base_path('resources/views/themes/' . $theme->theme . '/import/blocks/select_options.csv'),
                false
            );
            $selectOptionsImport->run();
        }
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->_blockSettings;
    }

    protected function _processFiles($overwriteFile = true)
    {
        if (!empty($this->_additionalData['theme'])) {

            $themeModel = $this->_additionalData['theme'];
            $themePath = base_path('resources/views/themes/' . $themeModel->theme . '/templates');

            // TODO change overwrite option
            PageBuilder::setClass(PageBuilder\ThemeBuilderInstance::class, [$overwriteFile], PageLoaderDummy::class, [$themeModel->theme]);

            if (is_dir($themePath)) {

                foreach (scandir($themePath) as $templateFile) {
                    if (($templateName = explode('.', $templateFile)[0]) && !is_dir($themePath.'/'.$templateFile)) {
                        PageBuilder::setData('template', $templateName);
                        View::make('themes.' . $themeModel->theme . '.templates.' . $templateName)->render();
                    }
                }

                if ($errors = PageBuilder::getData('errors')) {
                    echo 'Could not complete block import, errors found in theme:'; dd($errors);
                }


                // get data

            }
        }
    }



}