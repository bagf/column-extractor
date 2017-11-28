<?php

namespace Bagf\ColumnExtractor;

use ErrorException;

class DataException extends ErrorException
{
    protected $dataCode;

    public function getDataCode()
    {
        return $this->dataCode;
    }

    public function setDataCode($dataCode)
    {
        $this->dataCode = $dataCode;
        return $this;
    }
    
    public function errorRow($row)
    {
        return [ 'data_code' => $this->getDataCode(), 'row' => $row, 'error' => $this->getMessage(), ];
    }
}
