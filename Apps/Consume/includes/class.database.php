<?php
class pdo_database{

	public $last_query = "";
	public $last_query_array = "";
	public $last_query_type = "";
	
	public $error_code = "";
	public $error_message = "";
	
	public $row_count = "";
	public $insert_id = "";
	
	public $html = "";

	function __construct(){
		try {
			$this->pdo_conn = new PDO(DB_CONN,DB_USER,DB_PASS);
			$this->pdo_conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
		} catch (PDOException $err) {
			$this->bug_report("on",$err);
		}
	}

	public function clear_vars(){
		$this->last_query = "";
		$this->last_query_array = "";
		$this->last_query_type = "";
		$this->error_code = "";
		$this->error_message = "";
		$this->row_count = "";
		$this->insert_id = "";
	}

	public function query($sql="",$sql_array="") {
		return $this->run_sql_command("query",$sql,$sql_array);
	}
	public function execute($sql="",$sql_array="") {
		return $this->run_sql_command("execute",$sql,$sql_array);
	}
	public function insert($sql="",$sql_array="") {
		return $this->run_sql_command("insert",$sql,$sql_array);
	}
	
	public function run_sql_command( $sql_type , $sql , $sql_array ){
		if(!is_array($sql_array)) { $sql_array = array(); }
		$this->clear_vars();
		$this->last_query = $sql; // Store the last query we ran.
		$this->last_query_array = $sql_array; // Store the last query array we ran.
		$this->last_query_type = $sql_type; // Store the last query array we ran.

		try
			{
			if($sql_type=="execute") {
				$stmt = $this->pdo_conn->prepare( $sql );
				$stmt->execute( $sql_array );
				$result = $this->row_count = $stmt->rowCount();
			}
			if($sql_type=="query") {
				$stmt = $this->pdo_conn->prepare( $sql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
				$stmt->execute( $sql_array );
				$this->row_count = $stmt->rowCount();
				$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
				//if( $this->row_count==1 ){ $result = $result[0]; } // I'm not sure why this is here. It creates a problem with one row recordsets.
				if( $this->row_count==0 ){ $result = NULL; }
			}
			if($sql_type=="insert") {
				$sql = "SET NOCOUNT ON; " . $sql . "; SELECT SCOPE_IDENTITY() AS id ; ";
				$stmt = $this->pdo_conn->prepare( $sql );
				$stmt->execute( $sql_array );
				foreach ( $stmt->fetchAll() as $key => $value){
					$result = $this->insert_id = $value['id'];
				}
			}
			
			$this->bug_report(BUG_CHECK);
			$stmt = NULL;
			return $result;
			}
		catch (PDOException $err)
			{ $this->bug_report("on",$err); }
	}

	public function bug_report($state,$err=""){
		$html = "";
		if($err){
			$this->error_code = $err->getCode();
			$this->error_message = $err->getMessage();
		}
		if($state=="on"){
			$html = "<table cellspacing=\"0\" cellpadding=\"0\" class=\"bug_report\">";
			$html .= $this->bug_report_row( "Last Query" , $this->last_query );
			$html .= $this->bug_report_row( "Last Query Array" , $this->last_query_array );
			$html .= $this->bug_report_row( "Last Query Type" , $this->last_query_type );
			$html .= $this->bug_report_row( "Last Query Row Count" , $this->row_count );
			$html .= $this->bug_report_row( "Last Insert ID" , $this->insert_id );
			$html .= $this->bug_report_row( "Error Code" , $this->error_code );
			$html .= $this->bug_report_row( "Error Message" , $this->error_message );
			$html .= "</table>";

			// global $email;
			//$email->send_message_private( "Database Report" , $html );

			error_log( $html );

			//echo $html;
		}
		$this->html = $html;
		//echo $html;
	}
	public function bug_report_row($a,$b){
		$output = "<tr><th colspan=\"2\" scope=\"row\">" . $a . "</th><td>";
		if( is_array($b) ){
			$output .= print_r($b,true);
		} else {
			$output .= $b;
		};
		$output .= "</td></tr>";
		return $output;
	}
	function publish_database_response( $type = "" ){
		$html = '<div class="success">';
		if($type=="update") {
			$html .= 'This database record has been successfully saved.';
		} else {
			$html .= $type;
		}
		$html .= ' ( Time ' . date("n/j/Y g:i:s A") . ' )';
		$html .= '</div>';
		return $html;
	}

	public function cleanse_html( $a ){
		return  htmlentities( trim( $a . "" ) );
	}
	public function batch_process_sql( $processing_sql , $batch_size = 100 ){
		//Break the SQL statements into chunks
		$foo = array_chunk( $processing_sql , $batch_size , true );
		$sql_chain_merged = array();

		//loop through and convert the SQL arrays into chains. COnvert those chains into an array.
		foreach( $foo as $key => $value){
			$sql_chain = "";
			$sql_array = array();

			foreach( $value as $a => $b ){
				//publish_variable_data( "$b" , $b );
				$sql_chain .= $b[0];
				$sql_array = array_merge( $sql_array , $b[1] );
			}
			$sql_chain_merged[] = array( $sql_chain , $sql_array );
		}
		
		//Loop through the chain array
		foreach( $sql_chain_merged as $key => $value){
			//publish_variable_data( "foo" , $value );
			$rs = $this->execute( $value[0] , $value[1] );
		}
	}
}
?>