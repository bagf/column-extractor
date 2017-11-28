# column-extractor
Classes that extract, rename, transform CSV data

## Usage

```
composer require bagf/column-extractor
```

## Example

```php
use Goodby\CSV\Import\Standard\LexerConfig;
use Goodby\CSV\Import\Standard\Lexer as CSVLexer;
use Bagf\ColumnExtractor\Column;
use Bagf\ColumnExtractor\ColumnExtractor;

$interpreter = new ColumnExtractor([
    // Matches the column 'User Code' and maps it to 'code' key in the rows() sub arrays
    (new Column('User Code'))->rename('code'),
    // You can chain operations currently rename and transform are supported
    // transform() accepts a closure that gets the current $line and matched column $contents
    (new Column('Number'))->transform(function($line, $contents) {...})->rename('id'),
]);

$config = new LexerConfig();
$config->setDelimiter(($commaDelimiter?',':"\t"));
(new CSVLexer($config))->parse('file.csv', $interpreter);

print_r($interpreter->rows());
print_r($interpreter->errors());
```
