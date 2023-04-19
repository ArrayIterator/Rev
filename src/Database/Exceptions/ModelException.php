<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Exceptions;

use ArrayIterator\Rev\Source\Database\Orm\Model;
use RuntimeException;
use Throwable;

class ModelException extends RuntimeException
{
    public function __construct(
        protected readonly Model $model,
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return Model
     */
    public function getModel(): Model
    {
        return $this->model;
    }
}
