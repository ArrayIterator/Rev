<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Request;

use ArrayIterator\Reactor\Source\Events\Interfaces\EventsManagerInterface;

class Client
{
    public function __construct(
        protected ?EventsManagerInterface $manager
    ) {
    }

    /**
     * @return ?EventsManagerInterface
     */
    public function getManager(): ?EventsManagerInterface
    {
        return $this->manager;
    }

    /**
     * @param EventsManagerInterface $manager
     */
    public function setManager(EventsManagerInterface $manager): void
    {
        $this->manager = $manager;
    }
}
