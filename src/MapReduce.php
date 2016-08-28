<?php
namespace JLSalinas\MapReduce;

use League\Event\Emitter;
use JLSalinas\RWGen\Writers\Console;

class MapReduce
{
    const EVENT_START           = 'start';
    const EVENT_FINISHED        = 'end';
    const EVENT_START_INPUT     = 'start.input';
    const EVENT_FINISHED_INPUT  = 'end.input';
    const EVENT_START_MERGE     = 'start.merge';
    const EVENT_FINISHED_MERGE  = 'end.merge';
    const EVENT_START_OUTPUT    = 'start.output';
    const EVENT_FINISHED_OUTPUT = 'end.output';
    const EVENT_MAPPED          = 'mapped';
    const EVENT_REDUCED         = 'reduced';
    
    protected $inputs        = [];
    protected $mapper        = null;
    protected $reducer       = null;
    protected $group_by      = null;
    protected $outputs       = [];
    protected $emitters      = [];
    
    public function __construct()
    {
        foreach (func_get_args() as $arg) {
            $this->readFrom($arg);
        }
    }
    
    // $input is_array() || instanceOf Traversable
    // PHP, why u not add traversable as you added callable ?!
    public function readFrom($input)
    {
        if ((!is_array($input)) && (! ($input instanceof \Traversable))) {
            throw new \InvalidArgumentException('Input is not an array nor Traversable.');
        }
        $this->inputs[] = $input;
        return $this;
    }
    
    public function map(callable $mapper)
    {
        $fct = new \ReflectionFunction($mapper);
        if ($fct->getNumberOfRequiredParameters() != 1) {
            throw new \InvalidArgumentException('Mapper function must accept one parameter.');
        }
        
        $this->mapper = $mapper;
        return $this;
    }
    
    // $group_by can be:
    //  - true: group by first element of mapped item
    //  - Closure: group by the value returned by the closure after passing the mapped item
    //  - string || numeric: use the value as index for the mapped item
    public function reduce(callable $reducer, $group_by = null)
    {
        $fct = new \ReflectionFunction($reducer);
        if ($fct->getNumberOfRequiredParameters() != 2) {
            throw new \InvalidArgumentException('Reducer function must accept two parameters.');
        }
        
        if (!is_null($group_by) && !is_bool($group_by) && !is_callable($group_by) && !is_numeric($group_by) && !is_string($group_by)) {
            throw new \InvalidArgumentException('Group_by must be bool, callable, numeric or string.');
        } elseif (is_callable($group_by)) {
            $fct = new \ReflectionFunction($group_by);
            if ($fct->getNumberOfRequiredParameters() != 1) {
                throw new \InvalidArgumentException('Group_by, when callable, must accept one parameter.');
            }
        }
        
        $this->reducer = $reducer;
        $this->group_by = $group_by;
        return $this;
    }
    
    // $output Generator (has method `send()`)
    public function writeTo($output)
    {
        if (!is_object($output) || !method_exists($output, 'send')) {
            throw new \InvalidArgumentException('Output does not have a send() method.');
        }
        $this->outputs[] = $output;
        return $this;
    }
    
    public function handleWith(Emitter $emitter)
    {
        $this->emitters[] = $emitter;
        return $this;
    }
    
    protected function emit()
    {
        $args = null;
        foreach ($this->emitters as $em) {
            $args = $args ?: func_get_args();
            call_user_func_array([$em, 'emit'], $args);
        }
    }
    
    protected function getKeyFunction()
    {
        if ($this->group_by === true) {
            $func_key = function ($item) {
                return reset($item);
            };
        } elseif (is_callable($this->group_by)) {
            $func_key = $this->group_by;
        } elseif (is_string($this->group_by) || is_numeric($this->group_by)) {
            $group_by = $this->group_by;
            $func_key = function ($item) use ($group_by) {
                return $item[$group_by];
            };
        } else {
            $func_key = function ($item) {
                return '__no_key__';
            };
        }
        return $func_key;
    }
    
    /*
    protected function processInput($input, $name)
    {
        // $this->maper($data) does not work :(
        // http://stackoverflow.com/questions/5605404/calling-anonymous-functions-defined-as-object-variables-in-php
        $func_map = $this->mapper;
        $func_reduce = $this->reducer;
        $func_key = $this->getKeyFunction();
        
        $reduced = array();
        
        $this->emit(self::EVENT_START_INPUT, $name);
        
        foreach ($input as $row) {
            if ($row === null) {
                continue;
            }
            
            $mapped = $func_map($row);
            $this->emit(self::EVENT_MAPPED, $name, $row, $mapped);
            if ($mapped === null) {
                continue;
            }
            
            $key = $func_key($mapped);
            
            if (!isset($reduced[$key])) {
                $reduced[$key] = null;
            }
            
            $reduced[$key] = $func_reduce($reduced[$key], $mapped);
            $this->emit(self::EVENT_REDUCED, $name, $mapped, $reduced[$key]);
        }
        
        $this->emit(self::EVENT_FINISHED_INPUT, $name);
        
        return $reduced;
    }
    
    protected function mergeInputResults($input_results)
    {
        // $this->reducer($data) does not work :(
        // http://stackoverflow.com/questions/5605404/calling-anonymous-functions-defined-as-object-variables-in-php
        $func_reduce = $this->reducer;
        
        $this->emit(self::EVENT_START_MERGE);
        
        $reduced = array();
        $already_reduced_keys = [];
        
        foreach ($input_results as $ir) {
            foreach (array_keys($ir) as $k) {
                if (isset($already_reduced_keys[$k])) {
                    continue;
                }
                $already_reduced_keys[$k] = 1;
                $items = array_map(function ($item) use ($k) {
                    return isset($item[$k]) ? $item[$k] : null;
                }, $input_results);
                $items = array_filter($items, function ($item) {
                    return $item !== null;
                });
                
                $reduced[$k] = $func_reduce($items);
                $this->emit(self::EVENT_REDUCED, '__merge__', $items, $reduced[$k]);
            }
        }
        
        $this->emit(self::EVENT_FINISHED_MERGE, $reduced);
        
        return $reduced;
    }
    */
    
    public function run()
    {
        $this->emit(self::EVENT_START);
        
        // $this->maper($data) does not work :(
        // http://stackoverflow.com/questions/5605404/calling-anonymous-functions-defined-as-object-variables-in-php
        $func_map = $this->mapper;
        $func_reduce = $this->reducer;
        $func_key = $this->getKeyFunction();
        
        $reduced = array();
        
        foreach ($this->inputs as $name => $input) {
            $this->emit(self::EVENT_START_INPUT, $name);
            
            foreach ($input as $row) {
                if ($row === null) {
                    continue;
                }
                
                $mapped = $func_map($row);
                $this->emit(self::EVENT_MAPPED, $name, $row, $mapped);
                if ($mapped === null) {
                    continue;
                }
                
                $key = $func_key($mapped);
                
                if (!isset($reduced[$key])) {
                    $reduced[$key] = null;
                }
                
                $reduced[$key] = $func_reduce($reduced[$key], $mapped);
                $this->emit(self::EVENT_REDUCED, $name, $mapped, $reduced[$key]);
            }
            
            $this->emit(self::EVENT_FINISHED_INPUT, $name);
        }
        
        $this->emit(self::EVENT_START_OUTPUT);
        
        if (count($this->outputs) == 0) {
            $this->writeTo(new Console());
        }
        
        foreach ($reduced as $item) {
            foreach ($this->outputs as $output) {
                $output->send($item);
            }
        }
        foreach ($this->outputs as $output) {
            $output->send(null);
        }
        
        $this->emit(self::EVENT_FINISHED_OUTPUT);
        
        $this->emit(self::EVENT_FINISHED);
    }
}
