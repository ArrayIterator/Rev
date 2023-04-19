<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Routes\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
final readonly class Get extends Abstracts\HttpMethodAttributeAbstract
{
}
