<?php
require_once( 'includes/initialize.php' );

$html = ''; // Notification variable
$debug_narrative = ''; // Narrative variable

if( $_POST ){ // Check if the connection is a POST

	$appliance = new appliance_variable();

	if( $appliance->obvius_mode == "LOGFILEUPLOAD") { // Check if the connection is of the correct mode

		//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		$appliance->add_to_narrative("We are now in LOGFILEUPLOAD mode.");
		//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		
		if( $_FILES ){ // Check if the connection contains files

			$appliance->add_to_narrative("There are files in this connection.");

			// Processing Script
			// ---------------------------------------------------------------------------------------------
			
			if ( file_exists($_FILES['LOGFILE']['tmp_name']) ) { // Confirm if a file was uploaded successfully

				//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
				$appliance->add_to_narrative("A file was usccessfully uploaded: " . $_FILES['LOGFILE']['tmp_name'] . " .");
				//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

				$szChecksum = md5_file($_FILES['LOGFILE']['tmp_name']);
				
				if ($szChecksum == $_REQUEST['MD5CHECKSUM']) { // Confirm that file checksum matches

					//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
					$appliance->add_to_narrative("We have a checksum Match.");
					//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

					$appliance->obvius_filename = $_FILES['LOGFILE']['name'];
					$processing_zip	=	PATH_FILE_PROCESSING . $appliance->obvius_serialnumber ."-" . $appliance->obvius_filename;

					if( move_uploaded_file($_FILES['LOGFILE']['tmp_name'],$processing_zip) ){
						
						//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
						$appliance->add_to_narrative("Temp File has been relocated to the processing folder (" . $processing_zip . ").");
						//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
						
						$gz = gzfile( $processing_zip );
						if ( is_array( $gz )  ) {
							
							//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
							$appliance->add_to_narrative("The contents of the .gz file has been loaded into an array of " . count($gz) . " rows.");
							//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
						
							if( $appliance->device_type != "" ){

								if (
										$appliance->device_type == "Weather Station"
										|| $appliance->device_type == "Elkor - Solar"
										|| $appliance->device_type == "Elkor - Load"
										|| $appliance->device_type == "Inverter"
										|| $appliance->device_type == "Veris - Solar"
									){

									//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
									$appliance->add_to_narrative("We have identified a device type (" . $appliance->device_type . ") and serial number (" . $appliance->obvius_serialnumber . ").");
									//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

									// ---------------------------------------------------------------------------------------------
									// Prepare storage folder details.
									// ---------------------------------------------------------------------------------------------
									$storage_year	= date('Y', strtotime( $appliance->storage_time ) ); // 2012 - year
									$storage_month	= date('m', strtotime( $appliance->storage_time ) ); // 01 - month
									$storage_day	= date('d', strtotime( $appliance->storage_time ) ); // 01 - day
									$storage_ampm	= date('A', strtotime( $appliance->storage_time ) ); // AM or PM
									$storage_hour	= date('h', strtotime( $appliance->storage_time ) ); // 01 - Hour

									$storage_path 	= PATH_FILE_STORAGE . "AquiSuite.EMB.A8810-0\\" . $appliance->obvius_serialnumber . "\\" . $appliance->device_type . "\\" . $appliance->obvius_modbusdevice . " - MODBUS\\" . $storage_year . " - YEAR\\" . $storage_month . " - MONTH\\" . $storage_day . " - DAY\\";
									$storage_zip 	= $storage_path . $appliance->obvius_filename;
									// ---------------------------------------------------------------------------------------------

									// ---------------------------------------------------------------------------------------------
									// Run all of the SQL Commands and test if they were successfull at the end.
									// ---------------------------------------------------------------------------------------------
									$cnt = 0;
									foreach( $gz as $key => $value ){ // Cycle through all records in .gz file

										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
										$appliance->add_to_narrative("Beginning processing of row " . $cnt . " of " . count($gz) . ".");
										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
										
										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
										$appliance->add_to_narrative("Row contents: " . print_r($value,true) );
										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

										$sql = ""; // Clear SQL command variable

										$sql = $appliance->build_sql_query( );

										if( $sql != "" ) {
											if( $database->error_code == ""){
												$insert = $appliance->send_data_to_database( $sql , $value );
											}
										}
										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
										$appliance->add_to_narrative("Completed processing of row " . $cnt . " of " . count($gz) . ".");
										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
										$appliance->add_to_narrative("Database Error Code: " . $database->error_code);
										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
										$appliance->add_to_narrative("Database Error Message: " . $database->error_message);
										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
										$appliance->add_to_narrative("Appliance Class Error: " . $appliance->error);
										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
										
										$cnt ++;
									}

									// SQL Commands have now run and $applaince->error should == ""
									if( $database->error_code == "" ){
										
										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
										$appliance->add_to_narrative("We are now past the database error block.");
										//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
										
										// At this point, we can move the file to the storage folder.
										
										// ---------------------------------------------------------------------------------------------
										//Create our storage path, if it does not exist
										// ---------------------------------------------------------------------------------------------
										if( !file_exists($storage_path) ){
											mkdir( $storage_path , 0 , true );
										}
										
										if ( file_exists($storage_path) ) {

											//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
											$appliance->add_to_narrative("A storage path has been created.");
											//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

											if( copy( $processing_zip , $storage_zip ) ){

												//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
												$appliance->add_to_narrative("The work file has been moved to storage.");
												//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

												if( unlink( $processing_zip ) ){
													
													//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
													$appliance->add_to_narrative("A success message can be issued..");
													//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
													
													echo "\nSUCCESS\n";
												} else {
													$appliance->error_notification( "The processing file was not deleted. Delete this file manually. File : " . $processing_zip );
												}
											} else {
												$sql = $appliance->execute_failure_delete();
												$appliance->error_notification( "The source file could not be copied into the storage directory. The following DELETE was run : " . $sql );
											}
										} else {
											$sql = $appliance->execute_failure_delete();
											$appliance->error_notification( "The storage path does not exist. The path is : " . $storage_path , $html );
										}
									} else {
										$sql = $appliance->execute_failure_delete();
										//$msg = "<p>There was a problem committing the data to the database.</p>";
										//$msg .= "<p>Class Error : " . $appliance->error . "</p>";
										//$msg .= "<p>The following DELETE was run : " . $sql . "</p>";
										$appliance->error_notification( $appliance->error );
									}
								} else {
									$appliance->error_notification( "The device type does not match a device I know. The device is : " . $appliance->device_type , $html );
								}
							} else {
								$appliance->error_notification( "I can't tell what kind of device is squawking. I think it is a : " . $appliance->obvius_modbusdevice , $html );
							}
						} else {
							$appliance->error_notification( "GZ Archive does not read as an array." , $html );
						}
					} else {
						$appliance->error_notification( "Uploaded temp file could not be moved to the processing folder." , $html );
					}
				} else {
					$appliance->error_notification( "A checksum failure took place." , $html );
				}
			} else {
				$appliance->error_notification( "No temp file present." , $html );
			}
		} else {
			$appliance->error_notification( "There are no files in the POST." , $html );
		}
	} else {
		//$appliance->error_notification( "Mode is not LOGFILEUPLOAD." , $html );
	}
} else {
	//$appliance->error_notification( "There is no valid POST." , $html );
}

class appliance_variable {
	public $obvius_ip						=	""; //Constructor
	public $obvius_filename					=	"";
	public $obvius_filetime					=	""; //Constructor
	public $obvius_mode						=	""; //Constructor
	public $obvius_serialnumber				=	""; //Constructor
	public $obvius_password					=	""; //Constructor
	public $obvius_loopname					=	""; //Constructor
	public $obvius_modbusip					=	""; //Constructor
	public $obvius_modbusport				=	""; //Constructor
	public $obvius_modbusdevice				=	""; //Constructor
	public $obvius_modbusdevicename			=	""; //Constructor
	public $obvius_modbusdevicetype			=	""; //Constructor
	public $obvius_modbusdevicetypenumber	=	""; //Constructor
	public $obvius_modbusdeviceclass		=	""; //Constructor

	// Helpers
	public $device_type						=	"";
	public $storage_time					=	"";
	public $error							=	"";
	public $narrative						=	array();
	
	public function add_to_narrative( $a ){
		$this->narrative[] = "<li>" . $a . " <strong>Time:</strong> " . date("n/j/Y  g:i a" ) . "</li>";
	}

	function __construct(){
		$this->obvius_ip = $_SERVER['REMOTE_ADDR'];
		if( isset( $_POST["FILETIME"]) ){				$this->obvius_filetime = $_POST["FILETIME"];										}
		if( isset( $_POST["MODE"]) ){					$this->obvius_mode = $_POST["MODE"];												}
		if( isset( $_POST["SERIALNUMBER"]) ){			$this->obvius_serialnumber = $_POST["SERIALNUMBER"];								}
		if( isset( $_POST["PASSWORD"]) ){				$this->obvius_password = $_POST["PASSWORD"];										}
		if( isset( $_POST["LOOPNAME"]) ){				$this->obvius_loopname = $_POST["LOOPNAME"];										}
		if( isset( $_POST["MODBUSIP"]) ){				$this->obvius_modbusip = $_POST["MODBUSIP"];										}
		if( isset( $_POST["MODBUSPORT"]) ){				$this->obvius_modbusport = $_POST["MODBUSPORT"];									}
		if( isset( $_POST["MODBUSDEVICE"]) ){			$this->obvius_modbusdevice = $_POST["MODBUSDEVICE"];								}
		if( isset( $_POST["MODBUSDEVICENAME"]) ){		$this->obvius_modbusdevicename = $_POST["MODBUSDEVICENAME"];						}
		if( isset( $_POST["MODBUSDEVICETYPE"]) ){		$this->obvius_modbusdevicetype = $_POST["MODBUSDEVICETYPE"];						}
		if( isset( $_POST["MODBUSDEVICETYPENUMBER"]) ){	$this->obvius_modbusdevicetypenumber = intval($_POST["MODBUSDEVICETYPENUMBER"]);	}
		if( isset( $_POST["MODBUSDEVICECLASS"]) ){		$this->obvius_modbusdeviceclass = $_POST["MODBUSDEVICECLASS"];						}

		if( $this->obvius_modbusdevice >= MODBUS_WEATHER_STATION_START && $this->obvius_modbusdevice <= MODBUS_WEATHER_STATION_END ){
			$this->device_type = "Weather Station";
		}
		if( $this->obvius_modbusdevice >= MODBUS_ELKOR_SOLAR_START && $this->obvius_modbusdevice <= MODBUS_ELKOR_SOLAR_END ){
			$this->device_type = "Elkor - Solar";
		}
		if( $this->obvius_modbusdevice >= MODBUS_ELKOR_LOAD_START && $this->obvius_modbusdevice <= MODBUS_ELKOR_LOAD_END ){
			$this->device_type = "Elkor - Load";
		}
		if( $this->obvius_modbusdevice >= MODBUS_INVERTER_START && $this->obvius_modbusdevice <= MODBUS_INVERTER_END ){
			$this->device_type = "Inverter";
		}
		
		if( $this->obvius_modbusdevice >= MODBUS_VERIS_LOAD_START && $this->obvius_modbusdevice <= MODBUS_VERIS_LOAD_END ){
			$this->device_type = "Veris - Solar";
		}

		$this->storage_time = $this->obvius_filetime;
		
	}
	public function obvius_sql_array(){
		$sql_array = array();
		$sql_array[] = $this->obvius_ip;
		$sql_array[] = $this->obvius_filename;
		$sql_array[] = $this->obvius_mode;
		$sql_array[] = $this->obvius_serialnumber;
		$sql_array[] = $this->obvius_password;
		$sql_array[] = $this->obvius_loopname;
		$sql_array[] = $this->obvius_modbusip;
		$sql_array[] = $this->obvius_modbusport;
		$sql_array[] = $this->obvius_modbusdevice;
		$sql_array[] = $this->obvius_modbusdevicename;
		$sql_array[] = $this->obvius_modbusdevicetype;
		$sql_array[] = $this->obvius_modbusdevicetypenumber;
		$sql_array[] = $this->obvius_modbusdeviceclass;
		return $sql_array;
	}
	public function send_data_to_database( $sql , $value ){
		global $database;
		
		// .gz fields
		$gz_field = explode( "," , $value ); // Explode record into an array
		

		$cnt = 0;
		foreach( $gz_field as $a => $b ){ // cycle through array and add each column to sql_array
			$c = trim(str_replace( "'" ,"" , $b ) . ""); // Remove single quotes and trim
			if( $c == ""){ $c = NULL; } // Convert blanks to NULL

			if($cnt == 0){ // if the value is the first in the array, make it a nice date value
				$c = strtotime( $c );
				$c = date("n/j/Y  g:i a" , $c  );
			}
			$cnt ++;

			$gz_array[] = $c;
		}
		
		// For any Veris device, we need to explicitly set the order of the fields.
		// We'l take the processed aray any replace it with one that has only the value we need.
		if( $this->device_type == "Veris - Solar" ){
			$veris_array[] = $gz_array[0]; // time(UTC)
			$veris_array[] = $gz_array[1]; // error
			$veris_array[] = $gz_array[2]; // lowalarm
			$veris_array[] = $gz_array[3]; // highalarm
			$veris_array[] = $gz_array[4]; // Accumulated Real Energy Net (kWh)
			$veris_array[] = $gz_array[14]; // Total Net Instantaneous Real Power (kW)
			$veris_array[] = $gz_array[15]; // Total Net Instantaneous Reactive Power (kVAR)
			$veris_array[] = $gz_array[16]; // Total Net Instantaneous Apparent Power (kVA)
			$veris_array[] = $gz_array[14]; // Total Net Instantaneous Real Power (kW)
			$veris_array[] = $gz_array[15]; // Total Net Instantaneous Reactive Power (kVAR)
			$veris_array[] = $gz_array[16]; // Total Net Instantaneous Apparent Power (kVA)
			$veris_array[] = $gz_array[19]; // Voltage, L-N, 3p Ave (Volts)
			$veris_array[] = $gz_array[18]; // Voltage, L-L, 3p Ave (Volts)
			$veris_array[] = $gz_array[20]; // Current, 3p Ave (Amps)
			$veris_array[] = $gz_array[17]; // Total Power Factor
			$veris_array[] = $gz_array[17]; // Total Power Factor
			$veris_array[] = $gz_array[21]; // Frequency (Hz)
			$veris_array[] = $gz_array[70]; // Voltage, Phase A-N (Volts)
			$veris_array[] = $gz_array[71]; // Voltage, Phase B-N (Volts)
			$veris_array[] = $gz_array[72]; // Voltage, Phase C-N (Volts)
			$veris_array[] = $gz_array[67]; // Voltage, Phase A-B (Volts)
			$veris_array[] = $gz_array[68]; // Voltage, Phase B-C (Volts)
			$veris_array[] = $gz_array[69]; // Voltage, Phase A-C (Volts)
			$veris_array[] = $gz_array[73]; // Current, Phase A (Amps)
			$veris_array[] = $gz_array[74]; // Current, Phase B (Amps)
			$veris_array[] = $gz_array[75]; // Current, Phase C (Amps)
			$veris_array[] = $gz_array[55]; // Real Power, Phase A (kW)
			$veris_array[] = $gz_array[56]; // Real Power, Phase B (kW)
			$veris_array[] = $gz_array[57]; // Real Power, Phase C (kW)
			$veris_array[] = $gz_array[58]; // Reactive Power, Phase A (kVAR)
			$veris_array[] = $gz_array[59]; // Reactive Power, Phase B (kVAR)
			$veris_array[] = $gz_array[60]; // Reactive Power, Phase C (kVAR)
			$veris_array[] = $gz_array[61]; // Apparent Power, Phase A (kVA)
			$veris_array[] = $gz_array[62]; // Apparent Power, Phase B (kVA)
			$veris_array[] = $gz_array[63]; // Apparent Power, Phase C (kVA)
			$veris_array[] = $gz_array[64]; // Power Factor, Phase A
			$veris_array[] = $gz_array[65]; // Power Factor, Phase B
			$veris_array[] = $gz_array[66]; // Power Factor, Phase C
			$veris_array[] = $gz_array[11]; // Apparent Energy Net (VAh)
			
			$gz_array = $veris_array;
		}

		// Merge the obvius fields with the gz fields.
		$sql_array = array_merge( $this->obvius_sql_array() , $gz_array );
		
		
		//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		$this->add_to_narrative("SQL String created: " . $sql );
		//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

		$database->insert( $sql , $sql_array );

		//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		$this->add_to_narrative("Insert processed with the following error code: " . $database->error_code );
		//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

		//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
		$this->add_to_narrative("Insert processed with the following insert ID: " . $database->insert_id );
		//~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

		if( $database->error_code != "" ){
			$tmp = "Bad Database Insert. ";
			$tmp .= "SQL : " . $sql . ". ";
			$tmp .= "SQL Array " . print_r( $sql_array , true ) . ". ";
			$tmp .= "Error Code : " . $database->error_code . ". ";
			$tmp .= "Error Message : " . $database->error_message . ". ";
			$this->error = $tmp;
		}
	}
	public function execute_failure_delete(){
		global $database;

		//Delete entries related to this session
		$sql = " DELETE FROM ";
		if( $this->device_type == "Weather Station" ){ $sql .= " [appliance_inverter] "; }
		if( $this->device_type == "Inverter" ){ $sql .= " [appliance_inverter] "; }

		if( $this->device_type == "Elkor - Solar" ){ $sql .= " [appliance_elkor] "; }
		if( $this->device_type == "Elkor - Load" ){ $sql .= " [appliance_elkor] "; }

		$sql .= " WHERE [obvius_ip] = '" . $this->obvius_ip ."' ";
		$sql .= " AND [obvius_serialnumber] = '" . $this->obvius_serialnumber ."' ";
		$sql .= " AND [obvius_filename] = '" . $this->obvius_filename ."' ";
		$sql .= " AND [obvius_modbusdevice] = '" . $this->obvius_modbusdevice ."' ";
		
		$database->execute( $sql );
		return $sql;
	}
 	public function build_sql_query( ){
		$obvius_fields = " [obvius_ip] , [obvius_filename] , [obvius_mode] , [obvius_serialnumber] , [obvius_password] , [obvius_loopname] , [obvius_modbusip] , [obvius_modbusport] , [obvius_modbusdevice] , [obvius_modbusdevicename] , [obvius_modbusdevicetype] , [obvius_modbusdevicetypenumber] , [obvius_modbusdeviceclass] , ";
		
		$sql = "";
		if( $this->device_type == "Weather Station" ){ // INFO : .gz file = 8 entries
			$sql = " INSERT INTO appliance_weather ( " . $obvius_fields ;
			// .gz fields
			$sql .= " [time-utc] , [error] , [lowalarm] , [highalarm] , [iradiance] , [cell_temp] , [external_temp] , [wind_speed] ";
			$sql .= " ) VALUES ( ";
			$sql .= " ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? ";
			$sql .= " ) ; ";
		}
		if( $this->device_type == "Inverter" ){ // INFO : .gz file = 32 entries
			$sql = " INSERT INTO [appliance_inverter] ( " . $obvius_fields;
			// .gz fields
			$sql .= " [time-utc] , [error] , [lowalarm] , [highalarm] , [pv_input_voltage] , [mppt_dc_voltage_target] , [grid_current] , [target_grid_current] , [pv_current_range] , [grid_voltage_l1-l2] , [grid_voltage_l1-n] , [grid_voltage_l2-n] , [grid_frequency] , [generated_power_fed_to_grid] , [pv_voltage_to_earth] , [out_of_range_grid_volts_l1-l2] , [out_of_range_grid_volts_l1-n] , [out_of_range_grid_volts_l2-n] , [grid_frequency_range] , [temperature_degrees] , [pv_current] , [maximum_temperature] , [maximum_input_voltage] , [error_current] , [pv_differential_current_range] , [fan_voltage] , [total_energy_yield] , [total_operating_hours_hours] , [total_sufficient_dc_hours_hours] , [total_system_startup_counts] , [total_events] , [total_co2_saved] ";
			$sql .= " ) VALUES ( ";
			$sql .= " ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? ";
			$sql .= " ) ; ";
		}
		if( $this->device_type == "Elkor - Solar" || $this->device_type == "Elkor - Load" ){ // INFO : .gz file = 69 entries
			$sql = " INSERT INTO [appliance_elkor] ( " . $obvius_fields;
			// .gz fields
			$sql .= " [time-utc] , [error] , [lowalarm] , [highalarm] , [net_total_energy_cumulative_kWh] , [real_power_abc_total_kW] , [reactive_power_abc_total_kVAR] , [apparent_power_abc_total_kVA] , [avg_voltage_LN_average_V] , [avg_voltage_LL_average_V] , [avg_current_abc_average_A] , [power_factor_abc_total_] , [frequency_instantaneous_Hz] , [real_power_demand_abc_average_kW] , [voltage_a_instantaneous_V] , [voltage_b_instantaneous_V] , [voltage_c_instantaneous_V] , [voltage_ab_instantaneous_V] , [voltage_bc_instantaneous_V] , [voltage_ac_instantaneous_V] , [current_a_instantaneous_A] , [current_b_instantaneous_A] , [current_c_instantaneous_A] , [real_power_a_instantaneous_kW] , [real_power_b_instantaneous_kW] , [real_power_c_instantaneous_kW] , [reactive_power_a_instantaneous_kVAR] , [reactive_power_b_instantaneous_kVAR] , [reactive_power_c_instantaneous_kVAR] , [apparent_power_a_instantaneous_kVA] , [apparent_power_b_instantaneous_kVA] , ";
			$sql .= " [apparent_power_c_instantaneous_kVA] , [power_factor_a_instantaneous_none] , [power_factor_b_instantaneous_none] , [power_factor_c_instantaneous_none] , [import_energy_a_cumulative_KWh] , [import_energy_b_cumulative_KWh] , [import_energy_c_cumulative_KWh] , [ttl_import_energy_abc_total_KWh] , [export_energy_a_cumulative_KWh] , [export_energy_b_cumulative_KWh] , [export_energy_c_cumulative_KWh] , [ttl_export_energy_abc_total_KWh] , [net_real_energy_a_cumulative_KWh] , [net_real_energy_b_cumulative_KWh] , [net_real_energy_c_cumulative_KWh] , [ttl_net_real_energy_abc_total_KWh] , [inductive_energy_a_cumulative_kVARh] , [inductive_energy_b_cumulative_KVARh] , [inductive_energy_c_cumulative_KVARh] , [ttl_inductive_energy_abc_total_KVARh] , [capacititive_energy_a_cumulative_KVARh] , [capacititive_energy_b_cumulative_KVARh] , [capacititive_energy_c_cumulative_KVARh] , [ttl_capatitive_energy_abc_total_KVARh] , ";
			$sql .= " [net_reactive_energy_a_cumulative_KVARh] , [net_reactive_energy_b_cumulative_KVARh] , [net_reactive_energy_c_cumulative_KVARh] , [ttl_net_reactive_energy_abc_total_KVARh] , [apparent_energy_a_cumulative_KVAh] , [apparent_energy_b_cumulative_KVAh] , [apparent_energy_c_cumulative_KVAh] , [ttl_apparent_energy_abc_total_KVAh] ";
			$sql .= " ) VALUES ( ";
			$sql .= " ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? ";
			$sql .= " ) ; ";
		}
		if( $this->device_type == "Veris - Solar" ){ // INFO : .gz file = 76 entries, but many are not used.
			$sql = " INSERT INTO [appliance_elkor] ( " . $obvius_fields;
			// .gz fields
			$sql .= " [time-utc] , [error] , [lowalarm] , [highalarm] , [net_total_energy_cumulative_kWh] , [real_power_ab_total_kW] , [reactive_power_ab_total_kVAR] , [apparent_power_ab_total_kVA] ,";
			$sql .= " [real_power_abc_total_kW] , [reactive_power_abc_total_kVAR] , [apparent_power_abc_total_kVA] , [avg_voltage_LN_average_V] , [avg_voltage_LL_average_V] , [avg_current_abc_average_A] ,";
			$sql .= " [power_factor_ab_total_] , [power_factor_abc_total_] , [frequency_instantaneous_Hz] , [voltage_a_instantaneous_V] , [voltage_b_instantaneous_V] , [voltage_c_instantaneous_V] , ";
			$sql .= " [voltage_ab_instantaneous_V] , [voltage_bc_instantaneous_V] , [voltage_ac_instantaneous_V] , [current_a_instantaneous_A] , [current_b_instantaneous_A] , [current_c_instantaneous_A] , ";
			$sql .= " [real_power_a_instantaneous_kW] , [real_power_b_instantaneous_kW] , [real_power_c_instantaneous_kW] , [reactive_power_a_instantaneous_kVAR] , [reactive_power_b_instantaneous_kVAR] , ";
			$sql .= " [reactive_power_c_instantaneous_kVAR] , [apparent_power_a_instantaneous_kVA] , [apparent_power_b_instantaneous_kVA] , [apparent_power_c_instantaneous_kVA] , [power_factor_a_instantaneous_none] , ";
			$sql .= " [power_factor_b_instantaneous_none] , [power_factor_c_instantaneous_none] , [ttl_apparent_energy_abc_total_KVAh] ";
			$sql .= " ) VALUES ( ";
			//$sql .= " ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? ";
			$sql .= " ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? , ? ";
			$sql .= " ) ; ";
		}
		return $sql;
	}
	public function error_notification( $a , $html = "" ){
		//Header("WWW-Authenticate: Basic realm=\"UploadRealm\"");    // realm name is actually ignored by the AcquiSuite.
		//Header("HTTP/1.0 406 Not Acceptable");                      // generate a 400 series http server error response.
		$html .= "<h2>Notification</h2><p>". $a ."</p>";
		
		
		//global $email;
		// $email->send_message_private( "Consumption Report" , $html );
		error_log( $html );
	}
}
?>
