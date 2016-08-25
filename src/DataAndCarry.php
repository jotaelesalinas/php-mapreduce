<?php
namespace JLSalinas\MapReduce;

class DataAndCarry
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
     * @var mixed $_carry
     */
    protected $_carry;
    
    /**
     * Create a new DataAndCarry instance
     *
     * @param mixed $data
     * @param mixed $carry `null` when the data is the mapped item.
     *                         Use different values (i.e. `null`/`false`) if you need to know when you are dealing
     *                         with a direct mapped item or with an already reduced value with no carry-over.
     */
    public function __construct($data, $carry = null)
    {
        $this->data = $data;
        $this->carry = $carry;
    }
    
    /**
     * Magic method to retrieve both properties.
     */
    function __get($name)
    {
        if ($name === 'data') {
            return $this->_data;
        } elseif ($name === 'carry') {
            return $this->_carry;
        }
        user_error("Invalid property: " . __CLASS__ . "->$name");
    }
}
