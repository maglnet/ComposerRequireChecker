<?php

namespace ComposerRequireChecker;

class JsonLoader {

    const NO_ERROR = 0;
    const ERROR_NO_READABLE = 1;
    const ERROR_INVALID_JSON = 2;

    /**
     * @var string
     */
    private $path;

    /**
     * @var int
     */
    private $errorCode;

    /**
     * @var string
     */
    private $errorMessage;

    /**
     * @var mixed
     */
    private $data;

    /**
     * @param string $path
     */
    public function __construct($path)
    {
        $this->path = $path;
        if (!is_readable($path) || ($content = file_get_contents($path)) === false) {
            $this->errorCode = self::ERROR_NO_READABLE;
            return;
        }
        $this->data = json_decode($content, true);
        if ($this->data === null && JSON_ERROR_NONE !== json_last_error()) {
            $this->errorCode = self::ERROR_INVALID_JSON;
            $this->errorMessage = json_last_error_msg();
            return;
        }
        $this->errorCode = self::NO_ERROR;
    }

    /**
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @return string
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

}
