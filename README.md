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

## Controller Example:

```php
public function method($param = null)
{
  // If you have optional parameters for where clause. 
  $where = [];
  if ($param != null) {
    $where = ['table.column' => $param];
  }
  // Load the 'datatables_server_side' library
  $this->load->library('datatables_server_side', [
    'table' => 'table1',  // The name of the main table
    'primary_key' => 'id',  // The primary key column of the main table
    'columns' => ['table1.column1', 'table2.column2'],  // The columns to fetch from the main table and table2
    'where' => array_merge($where, [
				'table1.column1' => 'value1',  // WHERE condition for table1.column1
        'table2.column2' => 'value2'   // WHERE condition for table2.column2
		]),
    'joins' => [
      ['table2', 'table1.column = table2.column', 'left'],  // LEFT JOIN with table2 on column equality
      ['table3', 'table1.column = table3.column', 'inner']  // INNER JOIN with table3 on column equality
    ],
    'group_by' => ['table1.column1']  // GROUP BY clause for table1.column1
  ]);

  $this->datatables_server_side->process();
}
```

## View Example:
### HTML

```
<tbody id="server_side_dt"></tbody>
```

### jQuery

```
<script>
  $(window).on('load', function() {
		// Setup - add a text input to each footer cell
		$('#server_side_dt tfoot th').each( function () {
			var title = $(this).text();
			if (title === '') return;
			$(this).html( '<input class="form-control" type="text" placeholder="'+title+'" />' );
		});

		$('#server_side_dt').DataTable({
			columnDefs: [
				{ className: "montserrat-bold text-center font-14", "targets": [ 1 ] },
				{ className: "montserrat text-center font-14", "targets": [ 0 ] },
				{ className: "text-right", "targets": [ 2, 3, 4, 5, 6 ] }
			],
			processing: true,
			serverSide: true,
			language: {
				search: "Search:",
				processing: '<i class="fa fa-spinner fa-spin fa-3x fa-fw"></i><span class="sr-only">Loading...</span> '
			},
			ajax: "<?= site_url("Controller/method"); ?>", // 'Controller/method/param'
			columns:[
				{data: 0},
				{
					data: 0, render: function (id, type, row) {
						return `<a href="<?= site_url("Controller/method/"); ?>${id}" target="_blank">
									${id}
								</a>`;
					}
				},
				{data: 1},
				{data: 2},
				{data: 3},
				{data: 4},
				{data: 5},
				{data: 6}
			],
			"order": [[0, "DESC"]],
			drawCallback: function( settings, json ) {
				$('#server_side_dt thead th, #server_side_dt tfoot th').removeClass('montserrat montserrat-bold'); // If you want to remove some class after append all dataTable.
			},
			rowCallback: function (nRow, aData, iDisplayIndex) {
				var oSettings = this.fnSettings ();
				$("td:first", nRow).html(oSettings._iDisplayStart+iDisplayIndex +1);
				return nRow;
			},
			initComplete: function () {
				// Apply the search
				this.api().columns().every( function () {
					var that = this;

					$( 'input', this.footer() ).on( 'keyup change clear', function () {
						if ( that.search() !== this.value ) {
							that
								.search( this.value )
								.draw();
						}
					} );
				} );
			}
		});
	});
</script>
```

## Contributing

Contributions are welcome! If you find any issues or have suggestions for improvement, please open an issue or submit a pull request on GitHub.

## License

This library is licensed under the MIT License.