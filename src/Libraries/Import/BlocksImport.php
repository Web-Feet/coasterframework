<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Helpers\Cms\Page\PageLoaderDummy;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\BlockCategory;
use CoasterCms\Models\Theme;
use View;

class BlocksImport extends AbstractImport
{

    /**
     * @var array
     */
    protected $_blockData;

    /**
     * @var string
     */
    protected $_currentBlockName;

    /**
     * @var string
     */
    protected $_categoryImport;

    /**
     * @var BlockCategory[]
     */
    protected $_blockCategoriesByName;

    /**
     * @return array
     */
    public function fieldMap()
    {
        return [
            'Block Name' => [
                'mapTo' => 'name',
                'mapFn' => '_toLowerTrim',
                'validate' => 'required'
            ],
            'Block Label' => [
                'mapTo' => 'label'
            ],
            'Block Note' => [
                'mapTo' => 'note'
            ],
            'Block Category' => [
                'mapTo' => 'category_id',
                'mapFn' => '_toCategoryId'
            ],
            'Block Type' => [
                'mapTo' => 'type'
            ],
            'Global (show in site-wide)' => [
                'mapTo' => 'global_site',
                'mapFn' => '_toBool'
            ],
            'Global (show in pages)' => [
                'mapTo' => 'global_pages',
                'mapFn' => '_toBool'
            ],
            'Templates' => [
                'mapFn' => '_mapTemplates'
            ],
            'Block Order' => [
                'mapTo' => 'order'
            ],
            'Block Active' => [
                'mapTo' => 'active'
            ]
        ];
    }

    /**
     * @return bool
     */
    public function validate()
    {
        if (!array_key_exists('theme', $this->_additionalData) || !is_a($this->_additionalData['theme'], Theme::class)) {
            $this->_validationErrors[] = 'Theme must be specified before blocks can be imported';
        }
        return parent::validate();
    }

    /**
     *
     */
    protected function _beforeRun()
    {
        $categoryImport = new \CoasterCms\Libraries\Import\Blocks\CategoryImport(
            base_path('resources/views/themes/' . $this->_additionalData['theme']->theme . '/import/blocks/categories.csv'),
            false
        );
        $categoryImport->run();
        $this->_blockCategoriesByName = $categoryImport->getBlockCategoriesByName();
        $formRulesImport = new \CoasterCms\Libraries\Import\Blocks\FormRulesImport(
            base_path('resources/views/themes/' . $this->_additionalData['theme']->theme . '/import/blocks/form_rules.csv'),
            false
        );
        $formRulesImport->run();
        $this->_blockSettings = [];
    }

    /**
     *
     */
    protected function _beforeRowMap()
    {
        $this->_currentBlockName = $this->_toLowerTrim($this->_importCurrentRow['Block Name']);
        $this->_blockData[$this->_currentBlockName] = [];
    }

    /**
     * @param array $importInfo
     * @param string $importFieldData
     */
    protected function _mapTo($importInfo, $importFieldData)
    {
        if ($importFieldData !== '') {
            $this->_blockData[$this->_currentBlockName][$importInfo['mapTo']] = $importFieldData;
        }
    }

    /**
     * @param string $importFieldData
     * @return string
     */
    protected function _toCategoryId($importFieldData)
    {
        if (!array_key_exists($importFieldData, $this->_blockCategoriesByName)) {
            $newBlockCategory = new BlockCategory;
            $newBlockCategory->name = trim($importFieldData);
            $newBlockCategory->order = 0;
            $newBlockCategory->save();
            $this->_blockCategoriesByName[$newBlockCategory->name] = $newBlockCategory;
        }
        return $this->_blockCategoriesByName[$importFieldData]->id;
    }

    /**
     * @param string $importFieldData
     * @return mixed
     */
    protected function _mapTemplates($importFieldData)
    {
        return $importFieldData;
    }

    /**
     *
     */
    protected function _afterRun()
    {
        $this->_processFiles(); // TODO move block import funcs from block updater and eventually replace it with this file

        $selectOptionsImport = new \CoasterCms\Libraries\Import\Blocks\SelectOptionImport(
            base_path('resources/views/themes/' . $this->_additionalData['theme']->theme . '/import/blocks/select_options.csv'),
            false
        );
        $selectOptionsImport->run();
    }

    protected function _processFiles()
    {
        $themeName = $this->_additionalData['theme']->theme;
        $themePath = base_path('resources/views/themes/' . $themeName . '/templates');

        PageBuilder::setClass(PageBuilder\ThemeBuilderInstance::class, [$this->_blockData], PageLoaderDummy::class, [$themeName]);

        if (is_dir($themePath)) {

            foreach (scandir($themePath) as $templateFile) {
                $templateName = current(explode('.', $templateFile));
                if ($templateName && !is_dir($themePath . '/' . $templateFile)) {
                    PageBuilder::setData('template', $templateName);
                    View::make('themes.' . $themeName . '.templates.' . $templateName)->render();
                }
            }

            if ($errors = PageBuilder::getData('errors')) {
                echo 'Could not complete block import, errors found in theme:'; dd($errors);
            }

            $this->_blockData = PageBuilder::getData('blockData');
            $this->_templateBlocks = PageBuilder::getData('templateBlocks');
            $this->_otherBlocks = PageBuilder::getData('otherBlocks'); // blocks not directly linked to a template

            // get data

            dd(1, $this);

        }

    }

}