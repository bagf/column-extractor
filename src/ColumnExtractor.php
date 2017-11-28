<?php

/*
 * Construct this class with an associatve map of output keys to column/heading
 * names for example:
 * $ce = new ColumnExtractor([
 *      (new Column(['foo','bar']))->rename('foobar'),
 *      (new Column('foobar'))->rename('bar'),
 * ]);
 * 
 * When $ce->interpret([
 *  ['foo' => 'test','foobar' => 'lol'],
 *  ['bar' => 'test2', 'foobar' => 'testing'],
 * ]);
 * 
 * The returned collection object from $ce->rows() will contain
 * [
 *  [
 *      'foobar' => 'test',
 *      'bar' => 'lol',
 *  ]
 *  [
 *      'foobar' => 'test2',
 *      'bar' => 'testing
 *  ]
 * ]
 */

namespace Bagf\ColumnExtractor;

use Goodby\CSV\Import\Protocol\InterpreterInterface;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Collection;

class ColumnExtractor implements InterpreterInterface
{
    protected $columns = [];
    protected $rows = [];
    protected $processed = [];
    protected $errors = [];
    protected $headingRow = null;
    protected $lineNumber = 0;

    public function __construct($columns)
    {
        // Add each passed column via the local method. This helps guarantee
        // complete Column class instances are added
        foreach ($columns as $column) {
            $this->column($column);
        }
    }
    
    /**
     * Adds a Column instance to extract
     * @param \Bagf\ColumnExtractor\Column $column
     */
    public function column(Column $column)
    {
        $this->columns[] = $column;
    }
    
    /**
     * Returns a collection of a rows before Column classes transform the data
     * and name the key each column is associated with.
     * @return \Illuminate\Support\Collection
     */
    public function rows()
    {
        return new Collection($this->rows);
    }
    
    /**
     * Returns a collection of a rows after Column classes transform the data
     * and name the key each column is associated with.
     * @return \Illuminate\Support\Collection
     */
    public function processed()
    {
        return new Collection($this->processed);
    }
    
    /**
     * Returns a set of data for each erroneous row the nested, the following
     * keys can be used: 'consultant_code', 'row', 'error'
     * @return \Illuminate\Support\Collection
     */
    public function errors()
    {
        return new Collection($this->errors);
    }

    /**
     * This method should be called for each unprocessed row in a CSV file for
     * example, the first row should contain the headers.
     * @param array $line
     * @return void
     */
    public function interpret($line)
    {
        // Add to line count
        $this->lineNumber++;
        
        // If the header row isn't set, assign it how and return
        if (is_null($this->headingRow)) {
            $defused = [];
            
            foreach ($line as $k => $v) {
                $defused[$k] = $this->defuseBom($v);
            }
            
            $this->headingRow = $defused;
            return ;
        }
        
        try {
            // Row should contain the exact amount of columns as the header row
            if (count($this->headingRow) != count($line)) {
                throw new DataException("Row does not have enough columns");
            }
            
            // Bind the headings to the column keys
            $assocLine = array_combine($this->headingRow, $line);
            $columns = [];
            
            foreach ($this->columns as $column) {
                try {
                    // Get the transformed key name and data
                    $array = $column->line($assocLine);
                } catch (ModelNotFoundException $ex) {
                    throw new DataException($ex->getMessage());
                }
                // Assign key with data
                $columns[$array['name']] = $array['data'];
            }
            
            // Add new row
            $this->processed[] = $columns;
            // Add raw unprocessed row
            $this->rows[] = $columns;
        } catch (DataException $ex) {
            // Add new error row
            $this->errors[] = $ex->errorRow($this->lineNumber);
        }
    }
    
    /**
     * Resets the header row and line count to starting values.
     */
    public function reset()
    {
        $this->headingRow = [];
        $this->lineNumber = 0;
    }

    /**
     * Strips BOM words
     * @param string $text
     * @return string mixed
     */
    protected function defuseBom($text)
    {
        $bom = pack('H*','EFBBBF');
        $text = preg_replace("/^{$bom}/", '', $text);
        return $text;
    }
}
