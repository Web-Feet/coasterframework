<?php

namespace CoasterCms\Console\Commands;

use CoasterCms\Console\Assets as Assets;
use CoasterCms\Helpers\Cms\Install;
use Illuminate\Console\Command;

class UpdateAssets extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'coaster:update-assets 
        {assets?* : $assets} 
        {--f|force : Will run asset updates regardless of stored version}
        {--default-themes : Overwrite default theme files with latest updates}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Downloads/Updates public asset files required by the admin interface & captcha.';

    /**
     * @var array
     */
    protected $_folders;

    /**
     * @var array
     */
    protected $_assetNames;

    /**
     * @var array
     */
    protected $_assetInstalledVersions;

    /**
     * @var string
     */
    protected $_assetInstallFile;

    /**
     * @var array
     */
    protected $_assets = [
        Assets\App::class,
        Assets\Bootstrap::class,
        Assets\AceEditor::class,
        Assets\FileManager::class,
        Assets\JQuery::class,
        Assets\JQueryUI::class,
        Assets\SecureImage::class,
        Assets\GalleryFileUpdates::class,
        Assets\StorageFileUpdates::class,
        Assets\Themes::class
    ];

    /**
     * UpdateAssets constructor.
     */
    public function __construct()
    {
        foreach ($this->_assets as $asset) {
            $this->_assetNames[$asset::$name] = $asset;
        }
        $this->signature = str_replace('$assets', implode(' ', array_keys($this->_assetNames)), $this->signature);
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->_initVersions();
        $this->info("Running public asset file update:\n");

        $assets = $this->input->getArgument('assets');
        $forceAssets = [];
        if ($this->input->getOption('default-themes') && !array_key_exists(Assets\Themes::$name, $assets)) {
            $assets[] = Assets\Themes::$name;
            $forceAssets[] = Assets\Themes::$name;
        }
        $updateAssets = $assets ? array_intersect_key($this->_assetNames, array_fill_keys($assets, null)) : $this->_assetNames;

        $bar = $this->output->createProgressBar(count($updateAssets));
        $bar->setFormatDefinition('custom', ' %current%/%max% [%bar%] -- %message% %details%');
        $bar->setFormat('custom');
        $bar->setMessage('', 'details');
        $bar->setMessage('Initializing ...');
        $bar->display();

        $errors = [];
        $reportMessages = [];
        foreach ($updateAssets as $assetName => $assetClass) {
            $bar->setMessage('Updating: ' . ($assetClass::$description ?: $assetName) . ' (' . $assetClass::$version . ')');
            $bar->display();
            $options = $this->input->getOptions();
            $options['force'] = in_array($assetName, $forceAssets) ? true : $options['force'];
            /** @var Assets\AbstractAsset $asset */
            $asset = new $assetClass($this->_folders['public'], $this->_assetInstalledVersions[$assetName], $options, $bar);
            try {
                $this->_setVersion($assetName, $asset->execute());
                $reportMessages[$assetName] = $asset->getReport();
            } catch (\Exception $e) {
                if ($e->getMessage()) {
                    $errors[$assetName] = $e->getMessage() . ' [' . $e->getFile() . ':' . $e->getLine() . ']';
                }
            }
            $bar->advance();
        }

        $bar->setMessage('', 'details');
        $bar->setMessage('Finished' . ($errors ? ' with errors' : ''));
        $bar->finish();
        $this->info("\n");
        if ($errors) {
            foreach ($errors as $assetName => $error) {
                $this->error($assetName . ': ' . $error);
            }
        }
        foreach ($reportMessages as $assetName => $assetReport) {
            foreach ($assetReport as $reportMessage) {
                $this->comment($assetName . ': ' . $reportMessage);
            }
        }

        // if in console and not installed, display notice
        if (app()->runningInConsole() && !Install::isComplete()) {
            $this->comment('To complete Coaster installation visit /install in your web browser .');
        }
    }

    /**
     *
     */
    protected function _initVersions()
    {
        $this->_folders['public'] = public_path(trim(config('coaster::admin.public'), '/')) . '/';
        $this->_folders['storage'] = storage_path(trim(config('coaster::site.storage_path'), '/')) . '/';
        foreach ($this->_folders as $folder) {
            if (!file_exists($folder)) {
                mkdir($folder, 0777, true);
            }
        }
        $this->_assetInstallFile = $this->_folders['storage'] . '/assets.json';
        if (file_exists($this->_assetInstallFile)) {
            $this->_assetInstalledVersions = json_decode(file_get_contents($this->_assetInstallFile), true);
        } else {
            $this->_assetInstalledVersions = [];
        }
        foreach ($this->_assetNames as $asset) {
            $this->_assetInstalledVersions[$asset::$name] = array_key_exists($asset::$name, $this->_assetInstalledVersions) ? $this->_assetInstalledVersions[$asset::$name] : '';
        }
    }

    /**
     * @param string $assetName
     * @param string $newVersion
     */
    protected function _setVersion($assetName, $newVersion)
    {
        if ($this->_assetInstalledVersions[$assetName] != $newVersion) {
            $this->_assetInstalledVersions[$assetName] = $newVersion;
            file_put_contents($this->_assetInstallFile, json_encode($this->_assetInstalledVersions));
        }
    }

}
