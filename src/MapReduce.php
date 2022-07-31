<?php
namespace JLSalinas\MapReduce;

use \Generator;

class MapReduce
{
    private const NO_KEY = '__NO__KEY__';

    protected $preFilter  = null;
    protected $mapper     = null;
    protected $postFilter = null;
    protected $groupBy    = null;
    protected $reducer    = null;
    protected $input      = null;
    protected $output     = [];
    
    public static function create(?array $data = null)
    {
        $mr = new self;

        if ($data !== null) {
            foreach ($data as $key => $value) {
                switch ($key) {
                    case "preFilter":
                    case "mapper":
                    case "postFilter":
                    case "groupBy":
                    case "reducer":
                    case "input":
                    case "inputMulti":
                    case "output":
                    case "outputMulti":
                        $mr->$key($value);
                        break;
                    default:
                        throw new \InvalidArgumentException("Wrong data field '$key'.");
                }
            }
        }

        return $mr;
    }

    public static function createAndRun(array $data)
    {
        return self::create($data)->run();
    }

    public function setInput(iterable $input): self
    {
        $this->input = [$input];
        return $this;
    }
    
    public function setInputMulti(iterable $input): self
    {
        foreach ($input as $subInput) {
            if (!is_iterable($subInput)) {
                throw new \InvalidArgumentException('Input is not an iterable of iterables.');
            }
        }
        $this->input = $input;
        return $this;
    }
    
    public function setPreFilter(?callable $func): self
    {
        $this->preFilter = $func;
        return $this;
    }

    public function setMapper(callable $func): self
    {
        $this->mapper = $func;
        return $this;
    }
    
    public function setPostFilter(?callable $func): self
    {
        $this->postFilter = $func;
        return $this;
    }

    public function setGroupBy(int|string|callable|null $value): self
    {
        $func = $value;
        
        if (is_numeric($value)) {
            $func = function ($item) use ($value) {
                return $item[$value];
            };
        } elseif (is_string($value)) {
            $func = function ($item) use ($value) {
                return is_array($item) ? $item[$value] : $item->$value;
            };
        } elseif ($value === null) {
            $func = function ($item) {
                return MapReduce::NO_KEY;
            };
        }
        
        $this->groupBy = $func;
        return $this;
    }

    public function setReducer(callable $func): self
    {
        $this->reducer = $func;
        return $this;
    }
    
    public function setOutput(Generator $output)
    {
        $this->output = [$output];
        return $this;
    }
    
    public function setOutputMulti(iterable $output)
    {
        foreach ($output as $subOutput) {
            if (! $subOutput instanceof Generator) {
                throw new \InvalidArgumentException('Output is not an array of Generators.');
            }
        }
        $this->output = $output;
        return $this;
    }
    
    protected function mergeInputs()
    {
        $numInput = 0;
        foreach ($this->input as $input) {
            $numInput += 1;
            $numItems = 0;
            foreach ($input as $item) {
                $numItems += 1;
                yield $item;
            }
        }
    }

    private function checkProperties()
    {
        if ($this->input === null || count($this->input) === 0) {
            throw new \InvalidArgumentException("Missing input.");
        } elseif ($this->mapper === null) {
            throw new \InvalidArgumentException("Missing mapper function.");
        } elseif ($this->reducer === null) {
            throw new \InvalidArgumentException("Missing reducer function.");
        }
    }

    public function run(): array
    {
        $this->checkProperties();

        // $this->mapper($data) does not work :(
        // http://stackoverflow.com/questions/5605404/calling-anonymous-functions-defined-as-object-variables-in-php
        $funcPreFilter  = $this->preFilter;
        $funcMapper     = $this->mapper;
        $funcPostFilter = $this->postFilter;
        $funcReducer    = $this->reducer;
        $funcGroupBy    = $this->groupBy;
        
        $reduced = [];

        foreach ($this->mergeInputs() as $item) {
            if ($item === null) {
                continue;
            } elseif ($funcPreFilter !== null && !$funcPreFilter($item)) {
                continue;
            }

            $mapped = $funcMapper($item);

            if ($mapped === null) {
                continue;
            } elseif ($funcPostFilter !== null && !$funcPostFilter($mapped)) {
                continue;
            }

            $key = $funcGroupBy === null ? MapReduce::NO_KEY : $funcGroupBy($mapped);
            $reduced[$key] = $funcReducer($reduced[$key] ?? null, $mapped);
        }
        
        if (count($this->output) > 0) {
            foreach ($this->output as $output) {
                foreach ($reduced as $item) {
                    $output->send($item);
                }
                $output->send(null);
            }
        }

        return count(array_keys($reduced)) === 1 && isset($reduced[MapReduce::NO_KEY]) ?
            array_values($reduced) : $reduced;
    }
}
