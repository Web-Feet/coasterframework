<?php namespace CoasterCms\Http\Controllers\AdminControllers;

use Auth;
use CoasterCms\Http\Controllers\AdminController as Controller;
use CoasterCms\Libraries\Builder\AssetBuilder;
use CoasterCms\Libraries\Import\BlocksImport;
use CoasterCms\Models\Block;
use CoasterCms\Models\AdminLog;
use CoasterCms\Models\BlockBeacon;
use CoasterCms\Models\BlockCategory;
use CoasterCms\Models\BlockFormRule;
use CoasterCms\Models\BlockSelectOption;
use CoasterCms\Models\Theme;
use GuzzleHttp\Client;
use Request;
use URL;
use View;

class ThemesController extends Controller
{

    private static $_error;

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
                    if (isset($databaseThemes[$themeFolder])) {
                        $themes[$databaseThemes[$themeFolder]->id] = $databaseThemes[$themeFolder]->theme;
                    }
                }
            }
        }

        $blockSettings = [];
        foreach (Block::getBlockClasses() as $blockName => $blockClass) {
            foreach ($blockClass::$blockSettings as $name => $action) {
                if (!empty($action) && Auth::action(str_replace('/', '.', $action))) {
                    $blockSettings[$name] = $action;
                }
            }
        }

        $this->layoutData['content'] = View::make('coaster::pages.themes', ['themes' => $themes, 'themes_count' => Theme::count(), 'blockSettings' => $blockSettings]);
    }

    public function getList()
    {
        $thumbs = [];

        $themesPath = base_path('resources/views/themes');
        if (is_dir($themesPath)) {
            foreach (Theme::all() as $databaseTheme) {
                $databaseThemes[$databaseTheme->theme] = $databaseTheme;
            }
            foreach (scandir($themesPath) as $themeFolder) {
                if (strpos($themeFolder, '.') !== 0) {

                    $theme = new \stdClass;
                    if (isset($databaseThemes[$themeFolder])) {
                        $theme->id = $databaseThemes[$themeFolder]->id;
                        $theme->install = 0;
                        $theme->active = ($databaseThemes[$themeFolder]->id == config('coaster::frontend.theme'));
                    } else {
                        $theme->id = 0;
                        $theme->install = 1;
                        $theme->active = false;
                    }
                    $theme->image = '//placeholdit.imgix.net/~text?txtsize=19&bg=efefef&txtclr=aaaaaa%26text%3Dno%2Bimage&txt=no+image&w=200&h=150';

                    if (is_dir($themesPath . '/' . $themeFolder)) {
                        $theme->name = $themeFolder;
                        $publicLocations = [public_path('themes/' . $themeFolder), $themesPath . '/' . $themeFolder . '/public'];
                        foreach ($publicLocations as $k => $publicLocation) {
                            if (is_dir($publicLocation)) {
                                foreach (scandir($publicLocation) as $file) {
                                    if (!is_dir($publicLocation . '/' . $file) && strpos($file, 'screenshot.') === 0) {
                                        if ($k == 0) {
                                            $theme->image = \Croppa::url(URL::to('themes/' . $themeFolder . '/' . $file), 252, 142);
                                        } else {
                                            $saveInCache = public_path('coaster/tmp/themes/' . $themeFolder);
                                            @mkdir($saveInCache, 0777, true);
                                            copy($publicLocation . '/' . $file, $saveInCache . '/' . $file);
                                            $theme->image = \Croppa::url(URL::to('coaster/tmp/themes/' . $themeFolder . '/' . $file), 252, 142);
                                        }
                                    }
                                }
                            }
                        }
                    } elseif (substr($themeFolder, -4) === '.zip') {
                        $theme->name = substr($themeFolder, 0, -4);
                    } else {
                        continue;
                    }

                    $thumbs[] = $theme;
                }
            }
        }

        $theme_auth = [
            'update' => Auth::action('themes.update'),
            'manage' => Auth::action('themes.manage'),
            'export' => Auth::action('themes.export'),
            'edit' => Auth::action('themes.edit')
        ];

        foreach ($thumbs as $thumb) {
            $buttons = [];
            if ($thumb->install) {
                if ($theme_auth['manage']) {
                    $buttons[] = ['classes' => ['installTheme'], 'glyphicon' => 'cog', 'label' => 'Install'];
                }
            } elseif ($thumb->id) {
                if ($theme_auth['manage']) {
                    $buttons[] = ['href' => '', 'classes' => ['activateTheme', 'activeSwitch', ($thumb->active || $thumb->install) ? 'hidden' : ''], 'glyphicon' => 'ok', 'label' => 'Activate'];
                }
                if ($theme_auth['edit']) {
                    $buttons[] = ['href' => route('coaster.admin.themes.edit', ['themeId' => $thumb->id]), 'glyphicon' => 'pencil', 'label' => 'Edit'];
                }
                if ($theme_auth['export']) {
                    $buttons[] = ['classes' => ['exportTheme'], 'glyphicon' => 'download', 'label' => 'Export'];
                }
                if ($theme_auth['update']) {
                    $buttons[] = ['href' => route('coaster.admin.themes.update', ['themeId' => $thumb->id]), 'glyphicon' => 'flag', 'label' => 'Review Block Changes'];
                }
            }

            $thumb->buttons = [];
            foreach ($buttons as $button) {
                $thumb->buttons[] = View::make('coaster::partials.themes.thumbs.button', $button + ['href' => '', 'classes' => [], 'glyphicon' => '', 'label' => '', 'thumb' => $thumb]);
            }
        }

        $themes_installed = View::make('coaster::partials.themes.thumbs', ['thumbs' => $thumbs, 'auth' => $theme_auth]);

        if (!empty(self::$_error)) {
            $this->addAlert('danger', self::$_error);
        }

        $this->layoutData['content'] = View::make('coaster::pages.themes.list', ['themes_installed' => $themes_installed, 'can_upload' => $theme_auth['manage']]);
        $this->layoutData['modals'] = View::make('coaster::modals.themes.delete')->render().
            View::make('coaster::modals.themes.export')->render().
            View::make('coaster::modals.themes.install')->render().
            View::make('coaster::modals.themes.install_confirm')->render().
            View::make('coaster::modals.themes.install_error')->render();
    }

    public function postManage()
    {
        $request = Request::all();

        if (!empty($request['activate'])) {
            return Theme::activate($request['theme']);
        }

        if (!empty($request['remove'])) {
            return Theme::remove($request['theme']);
        }

        if (!empty($request['newTheme'])) {
            if (!(self::$_error = Theme::upload($request['newTheme']))) {
                return \redirect()->route('coaster.admin.themes.list');
            } else {
                $this->getList();
            }
        }

        if (!empty($request['install'])) {
            return Theme::install($request['theme'], ['check' => !empty($request['check']), 'withPageData' => !empty($request['withPageData'])]);
        }

        return null;
    }

    public function getExport($themeId, $withPageData = false)
    {
        return Theme::export($themeId, (bool) $withPageData);
    }

    public function getBeacons()
    {
        $this->layoutData['content'] = View::make('coaster::pages.themes.beacons', ['rows' => BlockBeacon::getTableRows(), 'bitly' => BlockBeacon::bitlyCheck()]);
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
        return 0;
    }

    public function getUpdate($themeId)
    {
        $theme = Theme::find($themeId) ?: new Theme();
        $themeErrors = [];

        try {
            $blocksImport = new BlocksImport();
            $blocksImport->setTheme($theme)->run();
        } catch (\Exception $e) {
            $themeErrors[] = $e;
        }

        $importBlocks = $blocksImport->getBlocksCollection();

        $this->layoutData['content'] = View::make('coaster::pages.themes.update', [
                'themeErrors' => array_merge($themeErrors, $blocksImport->getErrors()),
                'theme' => $theme,
                'importBlocks' => $importBlocks,
                'importBlocksList' => $importBlocks->getBlockListInfo(),
                'typeList' => $this->_typeList(),
                'categoryList' => $this->_categoryList(),
                'templateList' => []
            ]
        );
    }

    public function postUpdate($themeId)
    {
        $postBlocks = Request::input('block');
        $theme = Theme::find($themeId) ?: new Theme();
        $themeErrors = [];

        try {
            $blocksImport = new BlocksImport();
            $blocksImport->setTheme($theme)->run();
            $importBlocks = $blocksImport->getBlocksCollection();
            if ($blocksImport->getErrorMessages()) {
                throw new \Exception;
            }

            $importBlocks->setScope('form'); // all form data has priority
            $updateTemplates = [];
            foreach ($postBlocks as $blockName => $postData) {
                $updateTemplates[$blockName] = array_key_exists('update_templates', $postData) ? $postData['update_templates'] : 0;
                $postData['globalData'] = array_key_exists('globalData', $postData) ? $postData['globalData'] : [];
                $postData['globalData'] += [
                    'show_in_global' => 0,
                    'show_in_pages' => 0
                ];
                $importBlocks->getBlock($blockName)
                    ->setBlockData($postData['blockData'])
                    ->setGlobalData($postData['globalData']);
            }
            $blocksImport->save($updateTemplates);
            $blocksImport->cleanCsv();
        } catch (\Exception $e) {
            $themeErrors = $e->getMessage() ? [$e->getMessage()] : $blocksImport->getErrorMessages();
        }

        $this->layoutData['content'] = View::make('coaster::pages.themes.update', [
            'themeErrors' => $themeErrors,
            'theme' => $theme,
            'saved' => true
        ]);
    }

    public function getForms($template = null)
    {
        if ($template) {
            $rules = BlockFormRule::where('form_template', '=', $template)->get();
            $rules = $rules->isEmpty()?[]:$rules;
            $this->layoutData['content'] = View::make('coaster::pages.themes.forms', ['template' => $template, 'rules' => $rules]);
        }
        else {
            $formTemplates = [];
            $themes = base_path('resources/views/themes');
            if (is_dir($themes)) {
                foreach (scandir($themes) as $theme) {
                    if (is_dir($themes . DIRECTORY_SEPARATOR . $theme) && $theme != '.' && $theme != '..') {

                        $formsDirs = [$themes . DIRECTORY_SEPARATOR . $theme . '/blocks/form'];
                        $formsDirs[] = $formsDirs[0].'s';
                        foreach ($formsDirs as $forms) {
                            if (is_dir($forms)) {
                                foreach (scandir($forms) as $form) {
                                    if (!is_dir($forms . DIRECTORY_SEPARATOR . $form)) {
                                        $form_file = explode(".", $form);
                                        if (!empty($form_file[0])) {
                                            $formTemplates[] = $form_file[0];
                                        }
                                    }
                                }
                            }
                        }

                        $repeaterForms = $themes . DIRECTORY_SEPARATOR . $theme . '/blocks/repeaters';
                        if (is_dir($repeaterForms)) {
                            foreach (scandir($repeaterForms) as $repeaterForm) {
                                if (!is_dir($repeaterForms . DIRECTORY_SEPARATOR . $repeaterForm)) {
                                    $form_file = explode(".", $repeaterForm);
                                    if (!empty($form_file[0]) && substr($form_file[0], -5) ==  '-form') {
                                        $formTemplates[] = $form_file[0];
                                    }
                                }
                            }
                        }

                    }
                }
            }
            $this->layoutData['content'] = View::make('coaster::pages.themes.forms', ['templates' => array_unique($formTemplates)]);
        }
    }

    public function postEdit(\Illuminate\Http\Request $request)
    {
        try {
            $theme_id = $request->get('theme_id');
            $theme = Theme::find($theme_id);
            $file = $request->get('file');
            $path = $request->get('path');
            $f = fopen($path,"w");
            fwrite($f, $file);
            AdminLog::new_log('Theme: \'' . $theme->theme . '\' updated (Page ID ' . $theme->id . ')');
            return response(['success' => 1]);
        } catch (\Exception $e) {
            return response(['success' => 0, 'error' => $e->getMessage()]);
        }
    }

    public function loadTemplateFile(\Illuminate\Http\Request $request)
    {
        $tf = $request->get('template');
        return response(['file' => file_get_contents($tf), 'path' => $tf]);
    }

    public function getEdit($themeId)
    {
        AssetBuilder::add('cms-main', ['/ace/ace.js']);
        $theme = Theme::find($themeId);
        $tvbp = base_path('resources/views/themes/'.$theme->theme);
        $tcssbp = base_path('public/themes/'.$theme->theme.'/css');
        $ret = Theme::getViewFolderTree($tvbp);
        $filetree = View::make('coaster::partials.themes.filetree', ['directory' => $ret, 'theme' => $theme]);

        $css_filetree_data = Theme::getViewFolderTree($tcssbp);
        $css_filetree = View::make('coaster::partials.themes.filetree', ['directory' => $css_filetree_data, 'theme' => $theme]);

        $this->layoutData['content'] = View::make('coaster::pages.themes.edit', ['theme' => $theme, 'filetree' => $filetree, 'css_filetree' => $css_filetree]);
    }

    public function postForms($template)
    {
        $databaseRules = [];
        $inputRules = [];

        $rules = BlockFormRule::where('form_template', '=', $template)->get();
        if (!$rules->isEmpty()) {
            foreach ($rules as $rule) {
                $databaseRules[$rule->field] = $rule;
            }
        }

        $rules = Request::get('rule');
        if (!empty($rules)) {
            foreach ($rules as $rule) {
                $inputRules[$rule['field']] = $rule['rule'];
            }
        }

        $toAdd = array_diff_key($inputRules, $databaseRules);
        $toUpdate = array_intersect_key($inputRules, $databaseRules);
        $toDelete = array_diff_key($databaseRules, $inputRules);

        if (!empty($toDelete)) {
            BlockFormRule::where('form_template', '=', $template)->whereIn('field', array_keys($toDelete))->delete();
        }

        if (!empty($toAdd)) {
            foreach ($toAdd as $field => $rule) {
                $newBlockFormRule = new BlockFormRule;
                $newBlockFormRule->form_template = $template;
                $newBlockFormRule->field = $field;
                $newBlockFormRule->rule = $rule;
                $newBlockFormRule->save();
            }
        }

        if (!empty($toUpdate)) {
            foreach ($toUpdate as $field => $rule) {
                if ($rule != $databaseRules[$field]->rule) {
                    $databaseRules[$field]->rule = $rule;
                    $databaseRules[$field]->save();
                }
            }
        }

        return \redirect()->route('coaster.admin.themes.forms');
    }

    public function getSelects($blockId = null, $import = 0)
    {
        $blocksQ = Block::where(function ($query) {
            $query->orWhere('type', 'LIKE', '%select%')->orWhere('type', 'LIKE', '%wpricecolour%')->orWhere('type', 'LIKE', '%wcolour%');
        })->where('type', 'NOT LIKE', '%selectpage%');

        if ($blockId) {
            $block = $blocksQ->where('id', '=', $blockId)->first();
        }

        if (!empty($block)) {

            if ($import) {

                $import = [
                    'fa-4.6' => 'Font Awesome Class List 4.6.1'
                ];

                $this->layoutData['content'] = View::make('coaster::pages.themes.selects', ['block' => $block, 'import' => $import]);

            } else {

                $options = BlockSelectOption::where('block_id', '=', $blockId)->get();
                $options = $options->isEmpty()?[]:$options;
                $this->layoutData['content'] = View::make('coaster::pages.themes.selects', ['block' => $block, 'options' => $options]);

            }

        } else {

            $selectBlocks = [];

            $blocks = $blocksQ->get();
            if (!$blocks->isEmpty()) {
                foreach ($blocks as $block) {
                    $selectBlocks[$block->id] = $block->name;
                }
            }

            $this->layoutData['content'] = View::make('coaster::pages.themes.selects', ['blocks' => $selectBlocks]);
        }
    }

    public function postSelects($blockId, $import = 0)
    {
        $inputOptions = [];

        if ($import) {

            $optionText = Request::get('selectOptionText');
            $optionValue = Request::get('selectOptionValue');
            $importOption = Request::get('selectOptionImport');

            $importUrls = [
                'fa-4.6' => 'https://maxcdn.bootstrapcdn.com/font-awesome/4.6.1/css/font-awesome.min.css'
            ];
            $importRegex = [
                'fa-4.6' => '.(fa-\w+):before{content:"\\\\\w{4}"}'
            ];

            $client = new Client;
            $response = $client->get($importUrls[$importOption]);
            $matches = [];

            preg_match_all('#'.$importRegex[$importOption].'#', $response->getBody(), $matches);
            if (!empty($matches[1])) {
                foreach ($matches[1] as $match) {
                    $inputOptions[str_replace('$match', $match, $optionValue)] = str_replace('$match', $match, $optionText);
                }
            }

        } else {

            $options = Request::get('selectOption');
            if (!empty($options)) {
                foreach ($options as $option) {
                    $inputOptions[$option['value']] = $option['option'];
                }
            }

        }

        BlockSelectOption::import($blockId, $inputOptions);

        return \redirect()->route('coaster.admin.themes.selects');
    }

    private function _typeList()
    {
        $selectArray = [];
        foreach (Block::getBlockClasses() as $blockName => $blockClass) {
            $selectArray[$blockName] = $blockName;
        }
        return $selectArray;
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
