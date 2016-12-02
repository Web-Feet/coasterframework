<?php namespace CoasterCms\Libraries\Traits;

use CoasterCms\Models\AdminLog;

trait Logger
{

    /**
     * @var array
     */
    protected $_logger = [];

    /**
     * @param string $log
     */
    public function addLog($log)
    {
        $logId = AdminLog::log($log);
        $this->_logger[$logId] = $log;
    }

    /**
     * @param string $log
     * @param mixed $object
     */
    public function addLogWithBackup($log, $object)
    {
        $logId = AdminLog::logWithBackup($log, $object);
        $this->_logger[$logId] = $log;
    }

    /**
     * @return array
     */
    public function getLogs()
    {
        return $this->_logger;
    }

    /**
     * @return array
     */
    public function getJSONLogIds()
    {
        return json_encode(array_keys($this->getLogs()));
    }

}