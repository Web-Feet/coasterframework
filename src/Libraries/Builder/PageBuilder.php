<?php
namespace CoasterCms\Libraries\Builder;

use CoasterCms\Exceptions\PageBuilderException;
use CoasterCms\Libraries\Builder\PageBuilder\DefaultInstance;
use CoasterCms\Libraries\Builder\PageBuilder\ThemeBuilderInstance;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Class PageBuilderLogger
 * @mixin DefaultInstance
 */
class PageBuilder implements \CoasterCms\Contracts\PageBuilder
{
    /**
     * @var string
     */
    protected $_defaultClass;

    /**
     * @var array
     */
    protected $_defaultArgs;

    /**
     * @var string
     */
    protected $_activeInstance;

    /**
     * @var DefaultInstance[]
     */
    protected $_instances;

    /**
     * @var Collection[]
     */
    protected $_instanceLogs;

    /**
     * @var bool[]
     */
    protected $_instanceLogsEnabled;

    /**
     * PageBuilder constructor.
     * @param string $defaultClass
     * @param array $defaultArgs
     */
    public function __construct($defaultClass = '', $defaultArgs = [])
    {
        $this->_defaultClass = $defaultClass;
        $this->_defaultArgs = $defaultArgs;
        $this->_instances = [];
        $this->_instanceLogs = [];
        $this->_instanceLogsEnabled = [];
    }

    /**
     * @param string $pageBuilderClass
     * @param array $pageBuilderArgs
     * @return static
     */
    public function make($pageBuilderClass, $pageBuilderArgs)
    {
        return $this->setInstance(null, $pageBuilderClass, $pageBuilderArgs);
    }

    /**
     * @param string $name
     * @param string $pageBuilderClass
     * @param array $pageBuilderArgs
     * @return static
     */
    public function setInstance($name = null, $pageBuilderClass = '', $pageBuilderArgs = [])
    {
        $pageBuilderArgs = ($pageBuilderClass || $pageBuilderArgs) ? $pageBuilderArgs : $this->_defaultArgs;
        $pageBuilderClass = $pageBuilderClass ?: $this->_defaultClass;
        $newInstance = new $pageBuilderClass($this, ...$pageBuilderArgs);

        if ($name === null) {
            array_push($this->_instances, $newInstance);
            end($this->_instances);
            $name = key($this->_instances);
        } else {
            $this->_instances[$name] = $newInstance;
        }

        $this->_instanceLogs[$name] = collect([]);
        $this->_instanceLogsEnabled[$name] = true;
        $this->_activeInstance = $name;

        return $this;
    }

    /**
     * @param string $name
     * @return static
     */
    public function getInstance($name = null)
    {
        $this->_activeInstance = is_null($name) ? $this->_activeInstance : $name;
        if (!array_key_exists($this->_activeInstance, $this->_instances)) {
            $this->setInstance($this->_activeInstance);
        }
        return $this;
    }

    /**
     * @return bool
     */
    public function isThemeBuilder()
    {
        return $this->_getInstance() instanceof ThemeBuilderInstance;
    }

    /**
     * @param string $key
     * @return Collection
     */
    public function logs($key = null)
    {
        if (!is_null($key)) {
            return $this->_instanceLogs[$this->_activeInstance]->pluck($key);
        }
        return $this->_instanceLogs[$this->_activeInstance];
    }

    /**
     * @return bool
     */
    public function getLogState()
    {
        return $this->_instanceLogsEnabled[$this->_activeInstance];
    }

    /**
     * @param bool $state
     */
    public function setLogState($state)
    {
        $this->_instanceLogsEnabled[$this->_activeInstance] = $state;
    }

    /**
     * @param null $name
     * @return DefaultInstance|mixed
     */
    protected function _getInstance($name = null)
    {
        $this->getInstance($name); // create if not exists
        return $this->_instances[$this->_activeInstance];
    }

    /**
     * @param string $methodName
     * @param array $args
     * @throws PageBuilderException
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        $instance = $this->_getInstance();
        $isThemeBuilder = $this->isThemeBuilder();
        $logFn = 'debug';
        $logContext = ['method' => $methodName, 'args' => $args, 'macro' => false];
        try {
            if ($instance->hasMacro($methodName)) {
                $logContext['macro'] = true;
                $return = $instance->macroCall($methodName, $args);
            } else {
                $return = call_user_func_array([$instance, $methodName], $args);
            }
        } catch (PageBuilderException $e) {
            if ($isThemeBuilder) {
                throw $e;
            }
            $logFn = 'error';
            $return = 'PageBuilder error: ' . $e->getMessage();
        }
        if ($this->getLogState() && !in_array($methodName, ['getData'])) {
            $this->logs()->push($logContext);
            Log::$logFn('PageBuilder method called: ' . $methodName, $logContext);
        }
        return $return;
    }

    /**
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        return $this->_getInstance()->$name;
    }

    /**
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->_getInstance()->$name = $value;
    }

}
