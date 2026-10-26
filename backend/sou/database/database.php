<?php
namespace Database;
use PDO,PDOException;
class database{
	private $connection;
	private static $instance=null;
	public function __construct(){
		try{
			$dbhost=$GLOBALS['config']['db']['dbhost'];$dbname=$GLOBALS['config']['db']['dbname'];$dbuser=$GLOBALS['config']['db']['dbuser'];$dbpass=$GLOBALS['config']['db']['dbpass'];
			$this->connection=new PDO("mysql:host={$dbhost};dbname={$dbname};",$dbuser,$dbpass,
			[
			PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
             PDO::ATTR_EMULATE_PREPARES => TRUE,
			]
			);
		}catch(PDOException $e){
			die("database".$e->getMessage());
		}
		}
		
		public static function getInstance(){
			if(self::$instance == null){
				self::$instance=new database();
			}
			return self::$instance->connection;
		}
	}
