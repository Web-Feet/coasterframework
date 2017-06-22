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
     * @var array
     */
    protected $_options;

    /**
     * @var boolean
     */
    protected $_failed;

    /**
     * @var ProgressBar
     */
    protected $_progressBar;

    /**
     * @var array
     */
    protected $_reportMessages;

    /**
     * AbstractAsset constructor.
     * @param string $coasterPublicFolder
     * @param string $version
     * @param array $options
     * @param ProgressBar $progressBar
     */
    public function __construct($coasterPublicFolder, $version, $options, $progressBar)
    {
        $this->_failed = false;
        $this->_baseFolder = $coasterPublicFolder . static::$name . DIRECTORY_SEPARATOR;
        $this->_currentVersion = $version;
        $this->_options = $options;
        $this->_progressBar = $progressBar;
        $this->_httpClient = new HttpClient;
    }

    /**
     *
     */
    public function execute()
    {
        $version = static::$version ?: config('coaster::site.version');
        if ($this->_options['force'] || version_compare($this->_currentVersion, $version, '<')) {
            if (!file_exists($this->_baseFolder) && static::$version != '-') {
                mkdir($this->_baseFolder, 0777, true);
            }
            $this->run();
            if (!$this->_failed) { // silent fail (throw \Exception in run() for normal failure)
                 $this->_currentVersion = $version;
            }
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
     * @param bool $inBaseFolder
     * @param bool $overwrite
     */
    public function downloadZip($url, $extracts, $method = 'GET', $params = [], $inBaseFolder = true, $overwrite = true)
    {
        $zipFile = $this->downloadFile($url, '', $method, $params, $inBaseFolder, $overwrite);
        $zip = new Zip;
        $zip->open($zipFile);
        foreach ($extracts as $zipPath => $filePath) {
            $zip->extractDir($zipPath, ($inBaseFolder ? $this->_baseFolder : '') . $filePath, $overwrite);
        }
        $zip->close();
        unlink($zipFile);
    }

    /**
     * @param string $url
     * @param string $fileName
     * @param string $method
     * @param array $params
     * @param bool $inBaseFolder
     * @param bool $overwrite
     * @return string
     */
    public function downloadFile($url, $fileName = '', $method = 'GET', $params = [], $inBaseFolder = true, $overwrite = true)
    {
        $fileName = $fileName ?: basename(parse_url($url, PHP_URL_PATH));
        $fileName = ($inBaseFolder ? $this->_baseFolder : '') . $fileName;
        if ($overwrite || !$fileName || !file_exists($fileName)) {
            $this->_setDetailedMessage('downloading: ' . $url);
            $this->_httpClient->request($method, $url, [
                'form_params' => $params,
                'sink' => $fileName
            ])->getBody()->close();
        }
        return $fileName;
    }

    /**
     * @param string $fromFolder
     * @param string $toFolder
     * @param bool $inBaseFolder
     * @param bool $overwrite
     */
    public function copyFrom($fromFolder = '', $toFolder = '', $inBaseFolder = true, $overwrite = true)
    {
        $this->_setDetailedMessage('copying: ' . $fromFolder);
        $fromFolder = $fromFolder ?: $this->_publicFiles(static::$name);
        $toFolder = ($inBaseFolder ? $this->_baseFolder : '') . $toFolder;
        Directory::copy($fromFolder, $toFolder, null, $overwrite);
    }

    /**
     * @param string $path
     * @return string
     */
    protected function _publicFiles($path)
    {
        return realpath(__DIR__ . '/../../../public/' . $path);
    }

    /**
     * @param string $message
     */
    protected function _setDetailedMessage($message)
    {
        $this->_progressBar->setMessage('['.$message.']', 'details');
        $this->_progressBar->display();
    }

    /**
     * @param string $message
     */
    protected function _report($message)
    {
        $this->_reportMessages[] = $message;
    }

    /**
     * @return array
     */
    public function getReport()
    {
        return $this->_reportMessages ?: [];
    }

}