<?php namespace CoasterCms\Http\Controllers\Backend;

use CoasterCms\Libraries\Builder\_PageBuilder;
use CoasterCms\Models\BlockBeacon;
use CoasterCms\Models\BlockCategory;
use CoasterCms\Models\Theme;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\View;

class ThemesController extends _Base
{

    public function getIndex()
    {
        $themes = [];

        $themesPath = base_path('resources/views/themes');
        if (is_dir($themesPath)) {
            foreach (Theme::all() as $databaseTheme) {
                $databaseThemes[$databaseTheme->theme] = $databaseTheme;
            }

            foreach (scandir($themesPath) as $themeFolder) {
                if (is_dir($themesPath . '/' . $themeFolder) && strpos($themeFolder, '.') !== 0) {
                    if (!isset($databaseThemes[$themeFolder])) {
                        $databaseThemes[$themeFolder] = new Theme;
                        $databaseThemes[$themeFolder]->theme = $themeFolder;
                        $databaseThemes[$themeFolder]->save();
                    }
                    $themes[$databaseThemes[$themeFolder]->id] = $databaseThemes[$themeFolder]->theme;
                }
            }
        }

        $this->layout->content = View::make('coaster::pages.themes', ['themes' => $themes]);
    }

    public function getBeacons()
    {
        $this->layout->content = View::make('coaster::pages.themes.beacons', ['rows' => BlockBeacon::getTableRows()]);
    }

    public function postBeacons()
    {
        if ($id = Request::input('add')) {
            BlockBeacon::addId();
            return BlockBeacon::getTableRows();
        }
        if ($id = Request::input('delete_id')) {
            BlockBeacon::removeId($id);
            return 1;
        }
    }

    public function getUpdate($themeId)
    {
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('PageBuilder', 'CoasterCms\Libraries\Builder\_PageBuilder');

        _PageBuilder::processFiles($themeId);

        $this->layout->content = View::make('coaster::pages.themes.update',
            [
                'theme' => _PageBuilder::getThemeName(),
                'blocksData' => _PageBuilder::getMainTableData(),
                'typeList' => $this->_typeList(),
                'categoryList' => $this->_categoryList()
            ]
        );

        $loader->alias('PageBuilder', 'CoasterCms\Libraries\Builder\PageBuilder');
    }

    public function postUpdate($themeId)
    {
        $loader = \Illuminate\Foundation\AliasLoader::getInstance();
        $loader->alias('PageBuilder', 'CoasterCms\Libraries\Builder\_PageBuilder');

        _PageBuilder::processFiles($themeId);

        // add any new templates
        _PageBuilder::saveTemplates();

        $blocks = Request::input('block');

        // Update Blocks Table
        foreach ($blocks as $block => $details) {
            // Update Blocks
            _PageBuilder::saveBlock($block, $details);

            // Update ThemeBlocks/TemplateBlocks
            if (!empty($details['run_template_update'])) {
                _PageBuilder::updateBlockTemplates($block, $details);
            }
        }

        _PageBuilder::updateBlockRepeaters();

        $this->layout->content = View::make('coaster::pages.themes.update',
            [
                'theme' => _PageBuilder::getThemeName(),
                'saved' => true
            ]
        );

        $loader->alias('PageBuilder', 'CoasterCms\Libraries\Builder\PageBuilder');
    }

    private function _typeList()
    {
        $classNames = [];
        foreach (scandir(__DIR__ . '/../../../Libraries/Blocks') as $name) {
            $className = explode('.', $name)[0];
            if (!empty($className)) {
                $classNames[trim(strtolower($className), '_')] = trim(strtolower($className), '_');
            }
        }
        return $classNames;
    }

    private function _categoryList()
    {
        $blockCategoryNames = [];
        $blockCategories = BlockCategory::orderBy('order')->get();
        foreach ($blockCategories as $blockCategory) {
            $blockCategoryNames[$blockCategory->id] = $blockCategory->name;
        }
        return $blockCategoryNames;
    }

}