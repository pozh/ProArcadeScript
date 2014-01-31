<?php
/*******************************************************************
/ ProArcadeScript 
/ File description:
/ Database-related helpers
/
/*******************************************************************/
class CDatabase
{
	var $db_id;
	var $mysql_error = '';
	var $mysql_error_num = 0;
	var $queries_num = 0;
	
//-----------------------------------------------------------------------------------------
function CDatabase($db_user, $db_pass, $db_name, $db_location = 'localhost', $show_error=1)
{
	if( !$this->db_id = @mysql_connect($db_location, $db_user, $db_pass) ) 
	{
		$this->mysql_error = mysql_error();
		$this->mysql_error_num = mysql_errno();
		if($show_error == 1)
			$this->log_error( 'Database connect failed! '.$this->mysql_error, $this->mysql_error_num );
		return false;
	} 

	if( !@mysql_select_db($db_name, $this->db_id) ) 
	{
		$this->mysql_error = mysql_error();
		$this->mysql_error_num = mysql_errno();
		if($show_error == 1)
			$this->log_error( 'Select database failed! ' . $this->mysql_error, $this->mysql_error_num );
		return false;
	}
	return true;
}

//-----------------------------------------------------------------------------------------
function query($query, $show_error=true)
{
	$this->queries_num ++;
	if( !($result = mysql_query($query, $this->db_id) ) ) 
	{
		$this->mysql_error = mysql_error();
		$this->mysql_error_num = mysql_errno();

		if($show_error)
			$this->log_error( 'Query failed. <br />'.$this->mysql_error, $this->mysql_error_num, $query);
	}
	return $result;
}
	
//-----------------------------------------------------------------------------------------
function get_row($query_result)
{
	return mysql_fetch_assoc($query_result);
}

//-----------------------------------------------------------------------------------------
function get_array($query_result)
{
	return mysql_fetch_array($query_result);
}
	
//-----------------------------------------------------------------------------------------
function super_query($query, $multi = false)
{
	if(!$multi) 
		return $this->get_row($this->query($query));
	else 
	{
		$query_result = $this->query($query);
			
		$rows = array();
		while($row = $this->get_row($query_result))
			$rows[] = $row;
			
		return $rows;
	}
}
	
//-----------------------------------------------------------------------------------------
function num_rows($query_result)
{
	return mysql_num_rows($query_result);
}
	
//-----------------------------------------------------------------------------------------
function insert_id()
{
	return mysql_insert_id($this->db_id);
}

//-----------------------------------------------------------------------------------------
function get_result_fields($result)
{
	while ($field = mysql_fetch_field($result))
    	$fields[] = $field;
		
	//mysql_free_result($query_id);
		
	return $fields;
}

//-----------------------------------------------------------------------------------------
function close()
{
	@mysql_close($this->db_id);
}
	
//-----------------------------------------------------------------------------------------
function log_error($error, $error_num, $query = '')
{
	echo( "ProArcadeScript DB ERROR! <br/>$error - $error_num.<br/>QUERY: $query" );
	exit();
}

//-----------------------------------------------------------------------------------------
function last_error_num()
{
	return $this->mysql_error_num;
}

//-----------------------------------------------------------------------------------------
function last_error()
{
	return $this->mysql_error;
}

//-----------------------------------------------------------------------------------------
function reset_counter()
{
	$this->queries_num = 0;
}

//-----------------------------------------------------------------------------------------
function get_queries_num()
{
	return $this->queries_num;
}


} //class


?>