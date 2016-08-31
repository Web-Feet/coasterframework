<?php namespace CoasterCms\Exceptions;

use Exception;
use Symfony\Component\HttpFoundation\Response;

class CmsPageException extends Exception
{

    /**
     * @var Response
     */
    protected $_alternateResponse;

    /**
     * CmsPageException constructor.
     * @param string $message
     * @param int $code
     * @param Exception|null $previous
     * @param Response|null $alternateResponse
     */
    public function __construct($message, $code = 0, Exception $previous = null, Response $alternateResponse = null)
    {
        $this->_alternateResponse = $alternateResponse;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return null|Response
     */
    public function getAlternateResponse()
    {
        return isset($this->_alternateResponse) ? $this->_alternateResponse : null;
    }

}
