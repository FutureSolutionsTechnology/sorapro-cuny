<?php
function publish_variable_data($a,$b){
	$html = "<h1>" . $a . "</h1>";

	if( !empty($b) ){
		$html .= "<pre>";
		if(is_array($b)){
			$html .= print_r( $b , true);
		} elseif( is_object($b) ) {
			$html .= print_r(get_object_vars( $b ) , true );
		} else {
			$html .= $b;
		}
		$html .= "</pre>";
	} else {
		$html .= "There is no data to print";
	}
	return $html;
}
function publish_generic_error( $type = "" ){
	return '<div class="error">' .  $type . '</div>';
}
function redirect_to( $location = NULL ) {
	if ($location != NULL){
		header("Location: {$location}");
		exit;
	}
}
function output_tab_delimited( $a ){
	$new_data = array();
	foreach( $a as $key => $value){
		$new_data[] = implode( "\t",$value);
	}
	return implode( "\r\n" ,$new_data);
}

function convert_to_true_false( $a ){
	if( $a == 1 ){
		return "True";
	} else {
		return "False";
	}
}
function html_entity_function( $a ){
	return trim( htmlentities( $a , ENT_COMPAT|'ENT_XHTML' , 'UTF-8' , false ) . "" );
}
function trim_function( $a ){
	return trim( $a . "" );
}
function limited_string( $a , $b ){
	$tmp = trim( $a . "" );
	$tmp = substr( $tmp , 0 , $b );
	return $tmp;
}

function clean_request_uri( $a ){
	$tmp = str_replace( $a , "" , $_SERVER['REQUEST_URI'] );

	if( strpos( $tmp , "?" ) ){
		$tmp = explode( "?" , $tmp );
		$tmp = $tmp[0];
	}
	return $tmp;
}
function get_value_from_request( $a ){
	$tmp = "";
	if( $_GET ){
		if( isset( $_GET[$a] ) ){
			$tmp = $_GET[$a];
		}
	}
	if( $_POST ){
		if( isset( $_POST[$a] ) ){
			$tmp = $_POST[$a];
		}
	}
	return $tmp;
}
function get_value_from_post( $a ){
	$tmp = "";
	if( $_POST ){
		if( isset( $_POST[$a] ) ){
			$tmp = $_POST[$a];
		}
	}
	return $tmp;
}
function get_value_from_get( $a ){
	$tmp = "";
	if( $_GET ){
		if( isset( $_GET[$a] ) ){
			$tmp = $_GET[$a];
		}
	}
	return $tmp;
}
function remove_white_space( $a ){
	$a = trim( $a );
	$a = str_replace( '    ' , ' ' , $a	);
	$a = str_replace( '   ' , ' ' , $a	);
	$a = str_replace( '  ' , ' ' , $a	);
	$a = str_replace( ' ' , ' ' , $a	);
	$a = str_replace( "\t" , ' ' , $a);
	$a = str_replace( "\r" , '' , $a );
	$a = str_replace( "\n" , '' , $a );
	
	return $a;
}
?>