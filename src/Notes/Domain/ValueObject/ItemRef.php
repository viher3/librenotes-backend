<?php

declare(strict_types=1);

namespace App\Notes\Domain\ValueObject;

/** A reference to one selected item: what it is and its id. */
readonly class ItemRef
{
    public function __construct(
        public ItemType $type,
        public string $id
    ) {
    }

    public function key(): string
    {
        return $this->type->value . ':' . $this->id;
    }
}
