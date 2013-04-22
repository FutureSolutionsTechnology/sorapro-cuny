<?php
// Note: Staging/Testing Code - Remove before installing on production
// ------------------------------------------------------------------------------------------------------------------------------------------
error_reporting(E_ALL|E_STRICT|E_NOTICE);
ini_set('display_errors', 'on');
// ==========================================================================================================================================

// Global Variables
// ------------------------------------------------------------------------------------------------------------------------------------------
defined('BUG_CHECK')						? null :  define('BUG_CHECK','on');
	
defined('DB_CONN')							? null :  define('DB_CONN','sqlsrv:Server=XXXXX;Database=XXXXX');
defined('DB_USER')							? null :  define('DB_USER','XXXXX');
defined('DB_PASS')							? null :  define('DB_PASS','XXXXX');

defined('BASE_PATH')						? null :  define( 'BASE_PATH' , 'D:\Web Server\IIS\Applications\Sorapro Applications\\' );
defined('PATH_FILE_PROCESSING')	? null :  define( 'PATH_FILE_PROCESSING','D:\Web Server\IIS\Applications\Sorapro Applications - Production\File Processing\\');
defined('PATH_FILE_STORAGE')		? null :  define( 'PATH_FILE_STORAGE','D:\Web Server\IIS\Applications\Sorapro Applications - Production\File Storage\\');

defined('NOTIFICATION_LIST')		? null :  define( 'NOTIFICATION_LIST','john@verdantautomation.com,monitor@verdanttech.com');

// Modbus Assignment ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
defined('MODBUS_WEATHER_STATION_START')	? null :  define( 'MODBUS_WEATHER_STATION_START' , 1 );
defined('MODBUS_WEATHER_STATION_END')		? null :  define( 'MODBUS_WEATHER_STATION_END' , 1 );
defined('MODBUS_ELKOR_SOLAR_START')			? null :  define( 'MODBUS_ELKOR_SOLAR_START' , 2 );
defined('MODBUS_ELKOR_SOLAR_END')				? null :  define( 'MODBUS_ELKOR_SOLAR_END' , 2 );
defined('MODBUS_ELKOR_LOAD_START')			? null :  define( 'MODBUS_ELKOR_LOAD_START' , 3 );
defined('MODBUS_ELKOR_LOAD_END')				? null :  define( 'MODBUS_ELKOR_LOAD_END' , 3 );
defined('MODBUS_VERIS_LOAD_START')			? null :  define( 'MODBUS_VERIS_LOAD_START' , 10 );
defined('MODBUS_VERIS_LOAD_END')				? null :  define( 'MODBUS_VERIS_LOAD_END' , 10 );
defined('MODBUS_INVERTER_START')				? null :  define( 'MODBUS_INVERTER_START' , 218 );
defined('MODBUS_INVERTER_END')					? null :  define( 'MODBUS_INVERTER_END' , 247 );
defined('DEFAULT_DATE_FORMAT')					? null :  define( 'DEFAULT_DATE_FORMAT' , 'Y-m-d h:ia' );
// ==========================================================================================================================================

// Includes
// ------------------------------------------------------------------------------------------------------------------------------------------
require_once('functions.php');
require_once('class.database.php');

// ==========================================================================================================================================
session_start();
$error_message = '';

$database				=	new pdo_database();

$html			=	'';
?>