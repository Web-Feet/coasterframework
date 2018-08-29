<?php
namespace CoasterCms\Libraries\Builder;

use CoasterCms\Exceptions\PageBuilderException;
use CoasterCms\Libraries\Builder\PageBuilder\DefaultInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class PageBuilderLogger
{

    /**
     * @var DefaultInstance
     */
    protected $_pageBuilder;

    /**
     * @var Collection
     */
    protected $_logs;

    /**
     * @var bool
     */
    protected $_logEnabled;

    /**
     * @param string $key
     * @return Collection
     */
    public function logs($key = null)
    {
        if (!is_null($key)) {
            return $this->_logs->pluck($key);
        }
        return $this->_logs;
    }

    /**
     * @param bool $state
     */
    public function setLogState($state)
    {
        $this->_logEnabled = $state;
    }

    /**
     * PageBuilderLogger constructor.
     * @param string $pageBuilderClass
     * @param array $pageBuilderArgs
     */
    public function __construct($pageBuilderClass, $pageBuilderArgs)
    {
        $this->_logEnabled = true;
        $this->_logs = collect([]);
        $this->_pageBuilder = new $pageBuilderClass($this, ...$pageBuilderArgs);
    }

    /**
     * @param string $methodName
     * @param array $args
     * @throws PageBuilderException
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        $logFn = 'debug';
        $logContext = ['method' => $methodName, 'args' => $args, 'macro' => false];
        try {
            if ($this->_pageBuilder->hasMacro($methodName)) {
                $logContext['macro'] = true;
                $return = $this->_pageBuilder->macroCall($methodName, $args);
            } else {
                $return = call_user_func_array([$this->_pageBuilder, $methodName], $args);
            }
        } catch (PageBuilderException $e) {
            if ($this->_pageBuilder->getData('isThemeBuilder')) {
                throw $e;
            }
            $logFn = 'error';
            $return = 'PageBuilder error: ' . $e->getMessage();
        }
        if ($this->_logEnabled && !in_array($methodName, ['getData'])) {
            $this->_logs->push($logContext);
            Log::$logFn('PageBuilder method called: ' . $methodName, $logContext);
        }
        return $return;
    }

}
