<?php
namespace JLSalinas\MapReduce;

use League\Event\Emitter;

class MapReduce {
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
    
    private $inputs     = [];
    private $mapper     = null;
    private $reducer    = null;
    private $group_by   = null;
    private $outputs    = [];
    private $emitters   = [];
    
    public function __construct (callable $mapper, callable $reducer, $group_by = null) {
        $this->map($mapper);
        $this->reduce($reducer, $group_by);
    }
    
    // $input is_array() || instanceOf Traversable
    // todo: named inputs (2nd argument)
    public function readFrom ($input) {
        // PHP, why u not add traversable as you added callable ?!
        if ( (!is_array($input)) && (! ($input instanceOf \Traversable)) ) {
            throw new \InvalidArgumentException('Input is not an array nor Traversable.');
        }
        $this->inputs[] = $input;
        return $this;
    }
    
    // $output Generator (has method `send()`)
    public function writeTo ($output) {
        if ( !method_exists($output, 'send') ) {
            throw new \InvalidArgumentException('MapReduce::writeTo() argument does not have a send() method.');
        }
        $this->outputs[] = $output;
        return $this;
    }
    
    public function notifyEventsTo (Emitter $emitter) {
        $this->emitters[] = $emitter;
        return $this;
    }
    
    protected function map (callable $mapper) {
        $this->mapper = $mapper;
        return $this;
    }
    
    // $group_by can be:
    //  - true: group by first element of mapped item
    //  - Closure: group by the value returned by the closure after passing the mapped item
    //  - string || numeric: use the value as index for the mapped item
    protected function reduce (callable $reducer, $group_by = null) {
        $this->reducer = $reducer;
        $this->group_by = $group_by;
        return $this;
    }
    
    protected function emit () {
        $args = null;
        foreach ($this->emitters as $em) {
            $args = $args ?: func_get_args();
            call_user_func_array([$em, 'emit'], $args);
        }
    }
    
    protected function getKeyFunction () {
        if ( $this->group_by === true ) {
            $func_key = function ($item) { return reset($item); };
        } elseif ( $this->group_by instanceOf Closure ) {
            $func_key = $this->group_by;
        } elseif ( is_string($this->group_by) || is_numeric($this->group_by) ) {
            $group_by = $this->group_by;
            $func_key = function ($item) use ($group_by) { return $item[$group_by]; };
        } else {
            $func_key = function ($item) { return '__no_key__'; };
        }
        return $func_key;
    }
    
    protected function processInput ($input, $name) {
        // $this->maper($data) does not work :(
        // http://stackoverflow.com/questions/5605404/calling-anonymous-functions-defined-as-object-variables-in-php
        $func_map = $this->mapper;
        $func_reduce = $this->reducer;
        $func_key = $this->getKeyFunction();
        
        $reduced = array();
        
        $this->emit(self::EVENT_START_INPUT, $name);
        
        foreach ( $input as $row ) {
            if ( $row === null ) {
                continue;
            }
            
            $mapped = $func_map($row);
            $this->emit(self::EVENT_MAPPED, $name, $row, $mapped);
            if ( $mapped === null ) {
                continue;
            }
            
            $key = $func_key($mapped);
            
            $items = [];
            if ( isset($reduced[$key]) ) {
                $items[] = $reduced[$key];
            }
            $items[] = $mapped;
            
            $reduced[$key] = $func_reduce($items);
            $this->emit(self::EVENT_REDUCED, $name, $items, $reduced[$key]);
        }
        
        $this->emit(self::EVENT_FINISHED_INPUT, $name);
        
        return $reduced;
    }
    
    protected function mergeInputResults ($input_results) {
        // $this->reducer($data) does not work :(
        // http://stackoverflow.com/questions/5605404/calling-anonymous-functions-defined-as-object-variables-in-php
        $func_reduce = $this->reducer;
        
        $this->emit(self::EVENT_START_MERGE);
        
        $reduced = array();
        $already_reduced_keys = [];
        
        foreach ($input_results as $ir) {
            foreach ( array_keys($ir) as $k ) {
                if ( isset($already_reduced_keys[$k]) ) {
                    continue;
                }
                $already_reduced_keys[$k] = 1;
                $items = array_map(function ($item) use ($k) { return isset($item[$k]) ? $item[$k] : null; }, $input_results);
                $items = array_filter($items, function ($item) { return $item !== null; });
                
                $reduced[$k] = $func_reduce($items);
                $this->emit(self::EVENT_REDUCED, '__merge__', $items, $reduced[$k]);
            }
        }
        
        $this->emit(self::EVENT_FINISHED_MERGE, $reduced);
        
        return $reduced;
    }
    
    public function run () {
        // $this->maper($data) does not work :(
        // http://stackoverflow.com/questions/5605404/calling-anonymous-functions-defined-as-object-variables-in-php
        $func_map = $this->mapper;
        $func_reduce = $this->reducer;
        $func_key = $this->getKeyFunction();
        
        $this->emit(self::EVENT_START);
        
        $reduced = array();
        
        foreach ( $this->inputs as $n => $input ) {
            $reduced[] = $this->processInput($input, $n + 1);
        }
        $reduced = $this->mergeInputResults($reduced);
        
        $this->emit(self::EVENT_START_OUTPUT);
        
        foreach ( $reduced as $item ) {
            foreach ( $this->outputs as $output ) {
                $output->send($item);
            }
        }
        
        $this->emit(self::EVENT_FINISHED_OUTPUT);
        
        $this->emit(self::EVENT_FINISHED);
    }
}
