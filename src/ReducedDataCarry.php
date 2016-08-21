<?php
namespace JLSalinas\MapReduce;

class ReducedDataCarry
{
    /**
     * The data we want to return.
     *
     * @var mixed $_data
     */
    protected $_data;
    
    /**
     * Extra data that maz be needed to further reduce this data with other data.
     *
     * @var mixed $_carryover
     */
    protected $_carryover;
    
    /**
     * Create a new ReducedItem Instance
     *
     * @param mixed $data
     * @param mixed $carryover `null` when the data is the mapped item.
     *                         Use different values (i.e. `null`/`false`) if you need to know when you are dealing
     *                         with a direct mapped item or with an already reduced value with no carry-over.
     */
    public function __construct($data, $carryover = null)
    {
        $this->data = $data;
        $this->carryover = $carryover;
    }
    
    function __get($name)
    {
        if ($name === 'data') {
            return $this->_data;
        } elseif ($name === 'carryover') {
            return $this->_carryover;
        }
        user_error("Invalid property: " . __CLASS__ . "->$name");
    }
}
