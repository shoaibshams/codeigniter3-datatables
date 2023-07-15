<?php

defined('BASEPATH') or exit('No direct script access allowed');

class Datatables_server_side
{
	/**
	 * Database table
	 *
	 * @var    string
	 */
	private $table;

	/**
	 * Primary key
	 *
	 * @var    string
	 */
	private $primary_key;

	/**
	 * Columns to fetch
	 *
	 * @var    array
	 */
	private $columns;

	/**
	 * Where clause
	 *
	 * @var    mixed
	 */
	private $where;

	/**
	 * Where clause
	 *
	 * @var    mixed
	 */
	private $joins;

	/**
	 * Where clause
	 *
	 * @var    mixed
	 */
	private $group_by;

	/**
	 * CI Singleton
	 *
	 * @var    object
	 */
	private $CI;

	/**
	 * GET request
	 *
	 * @var    array
	 */
	private $request;

	// --------------------------------------------------------------------

	/**
	 * Constructor
	 *
	 * @param  array  $params  Initialization parameters
	 * @return    void
	 */
	public function __construct($params)
	{
		$this->table = (array_key_exists('table', $params) === true && is_string($params['table']) === true) ? $params['table'] : '';

		$this->primary_key = (array_key_exists('primary_key', $params) === true && is_string($params['primary_key']) === true) ? $params['primary_key'] : '';

		$this->columns = (array_key_exists('columns', $params) === true && is_array($params['columns']) === true) ? $params['columns'] : [];

		$this->where = (array_key_exists('where', $params) === true && (is_array($params['where']) === true || is_string($params['where']) === true)) ? $params['where'] : [];

		$this->joins = (array_key_exists('joins', $params) === true && (is_array($params['joins']) === true)) ? $params['joins'] : [];

		$this->group_by = (array_key_exists('group_by', $params) === true && (is_array($params['group_by']) === true)) ? $params['group_by'] : [];

		$this->CI =& get_instance();

		$this->request = $this->CI->input->get();

		$this->validate_table();

		$this->validate_primary_key();

		$this->validate_columns();

		$this->validate_request();
	}

	// --------------------------------------------------------------------

	/**
	 * Validate database table
	 *
	 * @return    void
	 */
	private function validate_table()
	{
		if ($this->CI->db->table_exists($this->table) === false) {
			$this->response(array(
				'error' => 'Table doesn\'t exist.'
			));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Validate primary key
	 *
	 * @return    void
	 */
	private function validate_primary_key()
	{
		if ($this->CI->db->field_exists($this->primary_key, $this->table) === false) {
			$this->response(array(
				'error' => 'Invalid primary key.'
			));
		}
	}

	// --------------------------------------------------------------------

	/**
	 * validate columns to fetch
	 *
	 * @return    void
	 */
	private function validate_columns()
	{
		foreach ($this->columns as $column_name) {
			$column = $this->get_column_name($column_name);
			$table = $this->get_table_name($column_name);

			if (is_string($column) === false || $this->CI->db->field_exists($column, $table) === false) {
				$this->response(['error' => "The column '$column' does not exist in the table '$table'."]);
			}
		}
	}

	// --------------------------------------------------------------------

	/**
	 * validate GET request
	 *
	 * @return    void
	 */
	private function validate_request()
	{
		if (count($this->request['columns']) !== count($this->columns)) {
			$this->response(array(
				'error' => 'Column count mismatch.'
			));
		}

		foreach ($this->request['columns'] as $column) {
			if (isset($this->columns[$column['data']]) === false) {
				$this->response(array(
					'error' => 'Missing column.'
				));
			}

			$this->request['columns'][$column['data']]['name'] = $this->columns[$column['data']];
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Generates the ORDER BY portion of the query
	 *
	 * @return    CI_DB_query_builder
	 */
	private function order()
	{
		foreach ($this->request['order'] as $order) {
			$column = $this->request['columns'][$order['column']];

			if ($column['orderable'] === 'true') {
				$this->CI->db->order_by($this->get_column_name($column['name']), strtoupper($order['dir']));
			}
		}
	}

	/**
	 * Generates the GROUP BY portion of the query
	 *
	 * @return    CI_DB_query_builder
	 */
	private function group()
	{
		foreach ($this->group_by as $column) {
			$this->CI->db->group_by($column);
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Generates the LIKE portion of the query
	 *
	 * @return    CI_DB_query_builder
	 */
	private function search()
	{
		$global_search_value = $this->request['search']['value'];
		$likes = [];

		foreach ($this->request['columns'] as $column) {
			if (empty($column['name'])) {
				continue;
			}

			if ($column['searchable'] === 'true') {
				if (empty($global_search_value) === false) {
					$likes[] = array(
						'field' => $this->get_column_name_with_table_name($column['name']),
						'match' => $global_search_value
					);
				}

				if (empty($column['search']['value']) === false) {
					$likes[] = array(
						'field' => $this->get_column_name_with_table_name($column['name']),
						'match' => $column['search']['value']
					);
				}
			}
		}

		if (count($likes) > 0) {
			$this->CI->db->group_start();
		}

		foreach ($likes as $index => $like) {
			if ($index === 0) {
				$this->CI->db->like('CAST('.$like['field'].' AS CHAR)', $like['match']);
			} else {
				$this->CI->db->or_like('CAST('.$like['field'].' AS CHAR)', $like['match']);
			}
		}

		if (count($likes) > 0) {
			$this->CI->db->group_end();
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Generates the WHERE portion of the query
	 *
	 * @return    CI_DB_query_builder
	 */
	private function where()
	{
		if ( ! is_array($this->where)) {
			$this->CI->db->where($this->where);
		} else {
			foreach ($this->where as $key => $where) {
				if (is_numeric($key)) {
					$this->CI->db->where($where);
				} else {
					$this->CI->db->where($key, $where);
				}
			}
		}
	}

	/**
	 * Generates the JOINS portion of the query
	 *
	 * @return    CI_DB_query_builder
	 */
	private function joins()
	{
		foreach ($this->joins as $join) {
			$this->CI->db->join($join[0], $join[1], $join[2] ?? 'inner');
		}
	}

	// --------------------------------------------------------------------

	/**
	 * Send response to DataTables
	 *
	 * @param  array  $data
	 * @return    void
	 */
	private function response($data)
	{
		$this->CI->output->set_content_type('application/json');
		$this->CI->output->set_output(json_encode($data));
		$this->CI->output->_display();

		exit;
	}

	// --------------------------------------------------------------------

	/**
	 * Calculate total number of records
	 *
	 * @return    int
	 */
	private function records_total()
	{
		$this->CI->db->reset_query();

		$this->where();

		$this->joins();

		$this->group();

		$this->CI->db->from($this->table);

		return $this->CI->db->get()->num_rows();
	}

	// --------------------------------------------------------------------

	/**
	 * Calculate filtered records
	 *
	 * @return    int
	 */
	private function records_filtered()
	{
		$this->CI->db->reset_query();

		$this->where();

		$this->joins();

		$this->search();

		$this->group();

		$this->CI->db->from($this->table);

		return $this->CI->db->get()->num_rows();
	}

	// --------------------------------------------------------------------

	/**
	 * Process
	 *
	 * @param  string  $row_id  = 'data'
	 * @param  string  $row_class  = ''
	 * @return    void
	 */
	public function process($row_id = 'data', $row_class = '')
	{
		if (in_array($row_id, array('id', 'data', 'none'), true) === false) {
			$this->response(array(
				'error' => 'Invalid DT_RowId.'
			));
		}

		if (is_string($row_class) === false) {
			$this->response(array(
				'error' => 'Invalid DT_RowClass.'
			));
		}

		$columns = array();

		$add_primary_key = true;

		foreach ($this->columns as $column) {
			$columns[] = $column;

			if ($column === $this->primary_key) {
				$add_primary_key = false;
			}
		}

		if ($add_primary_key === true) {
//			$columns[] = $this->primary_key;
		}

		$this->CI->db->select(implode(',', $columns));

		$this->where();

		$this->joins();

		$this->search();

		$this->group();

		$this->order();

		$query = $this->CI->db->get($this->table, $this->request['length'], $this->request['start']);

		$data['data'] = array();

		foreach ($query->result_array() as $row) {
			$r = [];

			foreach ($row as $column) {
				$r[] = $column;
			}

			if ($row_id === 'id') {
				$r['DT_RowId'] = $row[$this->primary_key];
			}

			if ($row_id === 'data') {
				$r['DT_RowData'] = array(
					'id' => $row[$this->primary_key]
				);
			}

			if ($row_class !== '') {
				$r['DT_RowClass'] = $row_class;
			}

			$data['data'][] = $r;
		}

		$data['draw'] = intval($this->request['draw']);

		$data['recordsTotal'] = $this->records_total();

		$data['recordsFiltered'] = $this->records_filtered();

		$this->response($data);
	}

	/**
	 * Extracts the column name from a given column string, which can be either a single column name (e.g., `id`) or a column name with a table name (e.g., `students`.`id`).
	 *
	 * @param  string  $column  The column string to process.
	 * @return string The extracted column name.
	 */
	private function get_column_name(string $column): string
	{
		// Remove any backticks or single quotes from the variable
		$column = str_replace(array("`", "'"), "", $column);
		// Check if the variable contains parentheses
		if (preg_match("/\((.*?)\)/", $column, $matches)) {
			// Extract the part inside the parentheses
			$column = $matches[1];
		}
		// Split the variable by spaces or dots
		$parts = preg_split("/[\s\.]+/", $column);
		// Check if the last part is "as" followed by something
		if (count($parts) >= 2 && strtolower($parts[count($parts) - 2]) == "as") {
			// Return the second last part, which should be the column name
			return $parts[count($parts) - 3];
		} else {
			// Return the last part, which should be the column name
			return end($parts);
		}
	}

	/**
	 * Extracts the table name from a given column string, which can be either a single column name (e.g., `id`) or a column name with a table name (e.g., `students`.`id`).
	 *
	 * @param  string  $column  The column string to process.
	 * @return string The extracted table name.
	 */
	private function get_table_name(string $column): string
	{
		// Remove any backticks or single quotes from the variable
		$column = str_replace(array("`", "'"), "", $column);

		// Check if the variable contains parentheses
		if (preg_match("/\((.*?)\)/", $column, $matches)) {
			// Extract the part inside the parentheses
			$column = $matches[1];
		}

		// Split the variable by spaces or dots
		$parts = preg_split("/[\s\.]+/", $column);

		// Check if the variable contains a dot
		if (strpos($column, ".") !== false) {
			// Return the first part, which should be the table name
			return $parts[0];
		}

		return $this->table;
	}

	/**
	 * Extracts the column name with table name from a given column string
	 *
	 * @param  string  $column  The column string to process.
	 * @return string The extracted column name.
	 */
	private function get_column_name_with_table_name(string $column): string
	{
		// Remove any backticks or single quotes from the variable
		$column = str_replace(array("`", "'"), "", $column);

		// Check if the variable contains parentheses
		if (preg_match("/\((.*?)\)/", $column, $matches)) {
			// Extract the part inside the parentheses
			$column = $matches[1];
		}
		// Split the variable by spaces or dots
		$parts = preg_split("/[\s]+/", $column);

		// Check if the last part is "as" followed by something
		if (count($parts) >= 2 && strtolower($parts[count($parts) - 2]) == "as") {
			// Return the second last part, which should be the column name
			return $parts[count($parts) - 3];
		} else {
			// Return the last part, which should be the column name
			return end($parts);
		}
	}

}
