<?php

declare(strict_types=1);

namespace JLSalinas\SimpleMapReduce;

final class NullWriter implements Writer
{
    public function write(mixed $item): void
    {
    }

    public function close(): void
    {
    }
}
