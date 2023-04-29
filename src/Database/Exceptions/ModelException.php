<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Exceptions;

use ArrayIterator\Rev\Source\Database\Mapping\AbstractModel;
use RuntimeException;
use Throwable;

class ModelException extends RuntimeException
{
    public function __construct(
        protected readonly AbstractModel $model,
        string                           $message = "",
        int                              $code = 0,
        ?Throwable                       $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return AbstractModel
     */
    public function getModel(): AbstractModel
    {
        return $this->model;
    }
}
