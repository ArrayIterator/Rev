<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\App\Controllers;

use ArrayIterator\Rev\Source\Routes\Attributes\Any;
use ArrayIterator\Rev\Source\Routes\Attributes\Group;
use ArrayIterator\Rev\Source\Routes\Controller;

#[Group('/sample')]
class Example extends Controller
{
    protected function beforeMapping(string $method, array $arguments): void
    {
        $this->setResponseAsJson();
    }

    #[Any('/', priority: -100)]
    public function mainSample($params, $firstPath, $lastPath)
    {
        return [__FUNCTION__ => $params];
    }

}
