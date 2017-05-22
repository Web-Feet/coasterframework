<?php
namespace CoasterCms\Libraries\Builder;

use CoasterCms\Exceptions\PageBuilderException;
use CoasterCms\Libraries\Builder\PageBuilder\PageBuilderInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Traits\Macroable;

class PageBuilderLogger
{
    use Macroable {
        __call as macroCall;
    }

    /**
     * @var PageBuilderInstance
     */
    protected $_pageBuilder;

    /**
     * @var Collection
     */
    protected $_logs;

    /**
     * @param string $key
     * @return Collection
     */
    public function logs($key = '')
    {
        if ($key) {
            return $this->_logs->pluck($key);
        }
        return $this->_logs;
    }

    /**
     * PageBuilderLogger constructor.
     * @param string $pageBuilderClass
     * @param array $pageBuilderArgs
     */
    public function __construct($pageBuilderClass, $pageBuilderArgs)
    {
        $this->_logs = collect([]);
        $this->_pageBuilder = new $pageBuilderClass($this, ...$pageBuilderArgs);
    }

    /**
     * @param $methodName
     * @param $args
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        $logFn = 'debug';
        $logContext = ['method' => $methodName, 'args' => $args, 'marco' => false];
        try {
            if ($this->hasMacro($methodName)) {
                $logContext['macro'] = true;
                $return = $this->macroCall($methodName, $args);
            } else {
                $return = call_user_func_array([$this->_pageBuilder, $methodName], $args);
            }
        } catch (PageBuilderException $e) {
            $logFn = 'error';
            $return = 'PageBuilder error: ' . $e->getMessage();
        }
        $this->_logs->push($logContext);
        Log::$logFn('PageBuilder method called: '. $methodName, $logContext);
        return $return;
    }

}
