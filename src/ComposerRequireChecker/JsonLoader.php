<?php declare(strict_types=1);
namespace ComposerRequireChecker;

use ComposerRequireChecker\Exception\InvalidJsonException;
use ComposerRequireChecker\Exception\NotReadableException;

class JsonLoader
{

    /**
     * @var mixed
     */
    private $data;

    /**
     * @param string $path
     * @throws InvalidJsonException
     * @throws NotReadableException
     * @internal
     */
    public function __construct($path)
    {
        if (!is_readable($path) || ($content = file_get_contents($path)) === false) {
            throw new NotReadableException('unable to read ' . $path);
        }
        $this->data = json_decode($content, true);
        if ($this->data === null && JSON_ERROR_NONE !== json_last_error()) {
            throw new InvalidJsonException('error parsing ' . $path . ': ' . json_last_error_msg());
        }
    }

    /**
     * @internal
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }
}
