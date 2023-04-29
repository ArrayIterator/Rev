<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Database\Caster;

use ArrayIterator\Rev\Source\Database\Caster\Interfaces\CasterInterface;
use ArrayIterator\Rev\Source\Database\Caster\Traits\CasterTrait;
use ArrayIterator\Rev\Source\Utils\Filter\Consolidation;
use DateTimeImmutable;
use DateTimeInterface;
use Exception;

class DateTimeCaster implements CasterInterface
{
    use CasterTrait;

    /**
     * @throws Exception
     * @return DateTimeInterface|mixed
     */
    public function cast($data, array $params = []): mixed
    {
        $connection = $this->getConnection();
        if (is_numeric($data)) {
            $data = new DateTimeImmutable(date('c', (int) $data));
        } elseif (is_string($data)) {
            $date = Consolidation::callbackReduceError(
                static function () use ($data) {
                    return strtotime($data);
                }
            );
            if (is_int($date)) {
                if ($date >= 0) {
                    $data = new DateTimeImmutable(date('c', $date));
                } else {
                    $data = new DateTimeImmutable($data);
                }
            }
        }
        if ($data instanceof DateTimeInterface) {
            return $connection->convertDateToSystem($data);
        }
        return $data;
    }
}
