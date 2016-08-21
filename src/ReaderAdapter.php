<?php
namespace JLSalinas\MapReduce;

use JLSalinas\RWGen\Reader;

/**
 * Takes data from a Traversable or array and applies a transformation to every item.
 */
class ReaderAdapter extends Reader
{
    /**
     * The Reader we want to read from.
     *
     * @var JLSalinas\RWGen\Reader $reader
     */
    protected $reader;
    
    /**
     * The transformation we want to appy to every item returned by the Reader.
     *
     * @var callable $transform
     */
    protected $transform;
    
    /**
     * Create a new ReaderAdapter instance
     */
    public function __construct($reader, callable $transform)
    {
        // PHP, why u not add traversable as you added callable ?!
        if (! is_array($reader) && ! $reader instanceof Traversable) {
            throw new Exception('ReaderAdapter: $reader is not an array nor Traversable.');
        }
        $this->reader = $reader;
        $this->transform = $transform;
    }
    
    protected function inputGenerator()
    {
        return $this->transformItems();
    }
    
    private function transformItems()
    {
        $trans = $this->transform;
        foreach ($this->reader as $item) {
            yield $trans($item);
        }
    }
}
