<?php

namespace Bagf\ColumnExtractor;

class Column
{
    protected $match = [];
    protected $rename = null;
    protected $transformFunc = null;

    public function __construct($match)
    {
        if (!is_array($match)) {
            $match = [$match];
        }
        $this->match = $match;
    }
    
    public function rename($name)
    {
        $this->rename = $name;
        
        return $this;
    }
    
    public function transform($function)
    {
        $this->transformFunc = $function;
        
        return $this;
    }
    
    public function line($line)
    {
        foreach ($this->match as $m) {
            if (!isset($line[$m])) {
                throw new DataException("{$m} is not found in row");
            }
            
            $name = $this->name($m);
            $data = $this->data($line, $line[$m]);
            return compact('name', 'data');
        }
    }
    
    protected function name($name)
    {
        if (!is_null($this->rename)) {
            return $this->rename;
        }
        
        return $name;
    }
    
    protected function data($line, $data)
    {
        if (is_null($this->transformFunc)) {
            return $data;
        }
        
        $func = $this->transformFunc;
        
        return is_callable($func)?$func($line, $data):$data;
    }
}
