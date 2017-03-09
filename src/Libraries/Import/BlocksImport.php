<?php namespace CoasterCms\Libraries\Import;

use CoasterCms\Helpers\Cms\Page\PageLoaderDummy;
use CoasterCms\Libraries\Builder\PageBuilder;
use CoasterCms\Models\BlockCategory;
use CoasterCms\Models\Theme;
use View;

class BlocksImport extends AbstractImport
{

    /**
     * @var string
     */
    protected $_currentBlockName;

    /**
     * @var BlockCategory[]
     */
    protected $_blockCategoriesByName;

    /**
     * @var array
     */
    protected $_templateList;

    /**
     * @var array
     */
    protected $_blockData;

    /**
     * @var array
     */
    protected $_blockGlobals;

    /**
     * @var array
     */
    protected $_blockTemplates;

    /**
     * @var array
     */
    protected $_blockOtherViews;

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
                'mapFn' => '_mapGlobalSiteWide'
            ],
            'Global (show in pages)' => [
                'mapFn' => '_mapGlobalPages'
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
    protected function _loadTemplateList()
    {
        $this->_templateList = [];
        $themePath = base_path('resources/views/themes/' .  $this->_additionalData['theme']->theme . '/templates');
        if (is_dir($themePath)) {
            foreach (scandir($themePath) as $templateFile) {
                $templateName = current(explode('.', $templateFile));
                if ($templateName && !is_dir($themePath . '/' . $templateFile)) {
                    $this->_templateList[] = $templateName;
                }
            }
        }
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
        $this->_loadTemplateList();
        $this->_blockData = [];
        $this->_blockGlobals = [];
        $this->_blockTemplates = [];
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
        $importFieldData = strtolower($importFieldData);
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
     */
    protected function _mapTemplates($importFieldData)
    {
        if (!array_key_exists($this->_currentBlockName, $this->_blockTemplates)) {
            $this->_blockTemplates[$this->_currentBlockName] = [];
        }
        if ($importFieldData == '*') {
            $this->_blockTemplates[$this->_currentBlockName] = array_unique(array_merge($this->_blockTemplates[$this->_currentBlockName], $this->_templateList));
        } elseif ($templates = explode(',', $importFieldData)) {
            $this->_blockTemplates[$this->_currentBlockName] = array_unique(array_merge($this->_blockTemplates[$this->_currentBlockName], $templates));
        }
    }

    /**
     * @param string $importFieldData
     * @param string $globalSetting
     */
    protected function _mapGlobal($importFieldData, $globalSetting = '')
    {
        if ($importFieldData !== '') {
            if (!array_key_exists($this->_currentBlockName, $this->_blockGlobals)) {
                $this->_blockGlobals[$this->_currentBlockName] = [];
            }
            $this->_blockGlobals[$this->_currentBlockName][$globalSetting] = $this->_toBool($importFieldData) ? 1 : 0;
        }
    }

    /**
     * @param string $importFieldData
     */
    protected function _mapGlobalPages($importFieldData)
    {
        $this->_mapGlobal($importFieldData, 'show_in_pages');
    }

    /**
     * @param string $importFieldData
     */
    protected function _mapGlobalSiteWide($importFieldData)
    {
        $this->_mapGlobal($importFieldData, 'show_in_global');
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
        if ($this->_templateList) {

            // load themebuilder instance and render all templates with page dummy data
            PageBuilder::setClass(PageBuilder\ThemeBuilderInstance::class, [$this->_blockData], PageLoaderDummy::class, [$this->_additionalData['theme']->theme]);
            foreach ($this->_templateList as $templateName) {
                PageBuilder::setData('template', $templateName);
                View::make('themes.' . $this->_additionalData['theme']->theme . '.templates.' . $templateName)->render();
            }

            if ($errors = PageBuilder::getData('errors')) {
                echo 'Could not complete block import, errors found in theme:'; dd($errors);
            }

            $this->_blockData = PageBuilder::getData('blockData');
            $this->_blockTemplates = PageBuilder::getData('blockTemplates');
            $this->_blockOtherViews = PageBuilder::getData('blockOtherViews'); // blocks not directly linked to a template

            $this->_processFileBlocks();

            dd(1, $this);
        }
    }

    protected function _processFileBlocks()
    {
        // set type guesses

        // set order guesses

        // set global guesses
        $totalTemplates = count($this->_templateList);
        foreach ($this->_blockTemplates as $block => $templates) {
            if (!array_key_exists($block, $this->_blockGlobals)) {
                $this->_blockGlobals[$block] = [];
            }
            if (!array_key_exists('show_in_global', $this->_blockGlobals[$block])) {
                $this->_blockGlobals[$block]['show_in_global'] = ((count($templates) / $totalTemplates) >= 0.7) ? 1 : 0;
            }
            if (!array_key_exists('show_in_pages', $this->_blockGlobals[$block])) {
                $this->_blockGlobals[$block]['show_in_pages'] = 0;
            }
        }
    }

}