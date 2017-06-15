<?php namespace CoasterCms\Libraries\Builder;

class PageBuilderFactory
{

    /**
     * @var PageBuilder[]
     */
    protected $_instances;

    /**
     * @var string
     */
    protected $_activeInstance;

    /**
     * @var int
     */
    protected $_unNamedIndex;

    /**
     * @var string
     */
    protected $_defaultClass;

    /**
     * @var array
     */
    protected $_defaultArgs;

    /**
     * PageBuilderFactory constructor.
     * @param string $defaultClass
     * @param array $defaultArgs
     */
    public function __construct($defaultClass = '', $defaultArgs = [])
    {
        $this->_defaultClass = $defaultClass;
        $this->_defaultArgs = $defaultArgs;
        $this->_instances = [];
        $this->_unNamedIndex = 0;
        $this->_activeInstance = 'default';
    }

    /**
     * @param string $pageBuilderClass
     * @param array $pageBuilderArgs
     * @param bool $setActive
     * @return PageBuilder
     */
    public function make($pageBuilderClass, $pageBuilderArgs, $setActive = true)
    {
        return $this->setInstance('', $pageBuilderClass, $pageBuilderArgs, $setActive);
    }

    /**
     * @param string $activeInstance
     */
    public function switchActiveInstance($activeInstance)
    {
        $this->_activeInstance = $activeInstance;
    }

    /**
     * @param string $name
     * @return PageBuilder
     */
    public function getInstance($name = null)
    {
        $name = is_null($name) ? $this->_activeInstance : $name;
        return $this->setInstance($name);
    }

    /**
     * @param string $name
     * @param string $pageBuilderClass
     * @param array $pageBuilderArgs
     * @param bool $setActive
     * @return PageBuilder
     */
    public function setInstance($name = '', $pageBuilderClass = '', $pageBuilderArgs = [], $setActive = true)
    {
        if ($name === '') {
            $name = $this->_unNamedIndex++;
            while (array_key_exists($name, $this->_instances)) {
                $name = $this->_unNamedIndex++;
            }
        }
        if (!array_key_exists($name, $this->_instances)) {
            $pageBuilderArgs = ($pageBuilderClass || $pageBuilderArgs) ? $pageBuilderArgs : $this->_defaultArgs;
            $pageBuilderClass = $pageBuilderClass ?: $this->_defaultClass;
            $this->_instances[$name] = new PageBuilder($pageBuilderClass, $pageBuilderArgs);
        }
        if ($setActive) {
            $this->switchActiveInstance($name);
        }
        return $this->_instances[$name];
    }

    /**
     * @param $methodName
     * @param $args
     * @return mixed
     */
    public function __call($methodName, $args)
    {
        return call_user_func_array([$this->getInstance(), $methodName], $args);
    }

}