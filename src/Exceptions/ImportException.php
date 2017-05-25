<?php namespace CoasterCms\Exceptions;

use Exception;

class ImportException extends Exception
{
    /**
     * @var array
     */
    protected $_importErrors;

    /**
     * ImportException constructor.
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     * @param array $importErrors
     */
    public function __construct($message, $code = 0, Exception $previous = null, $importErrors = [])
    {
        parent::__construct($message, $code, $previous);
        $this->_importErrors = $importErrors;
    }

    /**
     * @return array
     */
    public function getImportErrors()
    {
        return $this->_importErrors;
    }

}