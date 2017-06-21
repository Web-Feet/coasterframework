<?php namespace CoasterCms\Console\Assets;

use CoasterCms\Helpers\Cms\File\Directory;
use CoasterCms\Helpers\Cms\File\Zip;
use GuzzleHttp\Client as HttpClient;
use Symfony\Component\Console\Helper\ProgressBar;

abstract class AbstractAsset
{

    /**
     * @var string
     */
    public static $name;

    /**
     * @var string
     */
    public static $version;

    /**
     * @var string
     */
    public static $description;

    /**
     * @var HttpClient
     */
    protected $_httpClient;

    /**
     * @var string
     */
    protected $_baseFolder;

    /**
     * @var string
     */
    protected $_currentVersion;

    /**
     * @var boolean
     */
    protected $_force;

    /**
     * @var ProgressBar
     */
    protected $_progressBar;

    /**
     * AbstractAsset constructor.
     * @param string $coasterPublicFolder
     * @param string $version
     * @param boolean $force
     * @param ProgressBar $progressBar
     */
    public function __construct($coasterPublicFolder, $version, $force, $progressBar)
    {
        $this->_baseFolder = $coasterPublicFolder . static::$name . DIRECTORY_SEPARATOR;
        $this->_currentVersion = $version;
        $this->_force = $force;
        $this->_progressBar = $progressBar;
        $this->_httpClient = new HttpClient;
    }

    /**
     *
     */
    public function execute()
    {
        $version = static::$version ?: config('coaster::site.version');
        if ($this->_force || version_compare($this->_currentVersion, $version, '<')) {
            if (!file_exists($this->_baseFolder)) {
                mkdir($this->_baseFolder, 0777, true);
            }
            $this->run();
            $this->_currentVersion = $version;
        }
        return $this->_currentVersion;
    }

    /**
     *
     */
    public function run()
    {

    }

    /**
     * @param string $url
     * @param array $extracts
     * @param string $method
     * @param array $params
     */
    public function downloadZip($url, $extracts, $method = 'GET', $params = [])
    {
        $zipFile = $this->downloadFile($url, '', $method, $params);
        $zip = new Zip;
        $zip->open($zipFile);
        foreach ($extracts as $zipPath => $filePath) {
            $zip->extractDir($zipPath, $this->_baseFolder . $filePath);
        }
        $zip->close();
        unlink($zipFile);
    }

    /**
     * @param string $url
     * @param string $fileName
     * @param string $method
     * @param array $params
     * @return string
     */
    public function downloadFile($url, $fileName = '', $method = 'GET', $params = [])
    {
        $fileName = $fileName ?: basename(parse_url($url, PHP_URL_PATH));
        $this->_httpClient->request($method, $url, [
            'form_params' => $params,
            'sink' => $this->_baseFolder . $fileName
        ])->getBody()->close();
        return $this->_baseFolder . $fileName;
    }

    /**
     * @param string $fromFolder
     */
    public function copyFrom($fromFolder = '')
    {
        $fromFolder = $fromFolder ?: realpath(__DIR__ . '/../../../public/' . static::$name);
        Directory::copy($fromFolder, $this->_baseFolder);
    }

}