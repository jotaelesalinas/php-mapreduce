<?php

declare(strict_types=1);

namespace JLSalinas\SimpleMapReduce;

interface Writer
{
    public function write(mixed $item): void;

    public function close(): void;
}
