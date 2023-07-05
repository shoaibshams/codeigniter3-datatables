# Datatables Server Side

A library for implementing server-side processing with DataTables in CodeIgniter.

## Table of Contents

- [Introduction](#introduction)
- [Installation](#installation)
- [Usage](#usage)
- [Configuration](#configuration)
- [Example Usage](#example)
- [Contributing](#contributing)
- [License](#license)

## Introduction

Datatables Server Side is a library designed to handle server-side processing with DataTables in CodeIgniter. It provides a simple and efficient way to retrieve data from a database and generate the necessary JSON response required by DataTables.

## Installation

1. Download the `Datatables_server_side.php` file from this repository.
2. Place the `Datatables_server_side.php` file in your CodeIgniter application's `application/libraries` directory.

## Usage

To use the Datatables Server Side library, follow these steps:

1. Load the library in your controller:

  ```php
   $this->load->library('datatables_server_side', $params);
  ```
   Replace $params with an array containing the necessary configuration parameters (see Configuration section for details).

Call the process() method to generate the JSON response:

```php
$this->datatables_server_side->process();
```
Configure your DataTables instance to make an AJAX request to the URL corresponding to your controller method.

## Configuration

The library accepts an array of configuration parameters during initialization. The available parameters are:

table: The database table to fetch data from.
primary_key: The primary key of the table.
columns: An array of column names to fetch.
where: A WHERE clause to filter the data (optional).
joins: An array of JOIN statements (optional).
group_by: An array of column names to group by (optional).

## Example:

```php
// Load the 'datatables_server_side' library
$this->load->library('datatables_server_side', [
  'table' => 'table1',  // The name of the main table
  'primary_key' => 'id',  // The primary key column of the main table
  'columns' => ['table1.column1', 'table2.column2'],  // The columns to fetch from the main table and table2
  'where' => [
    'table1.column1' => 'value1',  // WHERE condition for table1.column1
    'table2.column2' => 'value2'   // WHERE condition for table2.column2
  ],
  'joins' => [
    ['table2', 'table1.column = table2.column', 'left'],  // LEFT JOIN with table2 on column equality
    ['table3', 'table1.column = table3.column', 'inner']  // INNER JOIN with table3 on column equality
  ],
  'group_by' => ['table1.column1']  // GROUP BY clause for table1.column1
]);

$this->datatables_server_side->process();
```

## Contributing

Contributions are welcome! If you find any issues or have suggestions for improvement, please open an issue or submit a pull request on GitHub.

## License

This library is licensed under the MIT License.
