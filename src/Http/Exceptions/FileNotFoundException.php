<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Exceptions;

use InvalidArgumentException;
use Throwable;

class FileNotFoundException extends InvalidArgumentException
{
    /**
     * @var string
     */
    protected string $fileName;

    public function __construct(string $file, $message = "", $code = 0, Throwable $previous = null)
    {
        $this->fileName = $file;
        if (!$message) {
            $message = sprintf('File %s has not found', $file);
        }
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return string
     */
    public function getFileName() : string
    {
        return $this->fileName;
    }
}
