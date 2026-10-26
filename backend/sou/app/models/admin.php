<?php
namespace App\models;
use Database\database;
class admin{
	private static function db(){
		return database::getInstance();
	}
	public static function checkadmin($email){
		$st=self::db()->prepare("SELECT * FROM admin WHERE email=?");
		$st->execute([$email]);
		return $st->fetch();
	}
	public static function adminbyid($id){
		$st=self::db()->prepare("SELECT id,name,email,phone FROM admin WHERE id=?");
		$st->execute([$id]);
		return $st->fetch();
	}
	public static function accountverifysentotp($otp,$email){
		$sql="SELECT otp FROM account_verify_otp WHERE email=? LIMIT 1";
		$st=self::db()->prepare($sql);
		$st->execute([$email]);
		$row=$st->fetch();
		if($row){
			$sql="UPDATE account_verify_otp SET otp=?, window_start=NOW()  WHERE email=?";
			$st=self::db()->prepare($sql);
			$st->execute([$otp,$email]);
			return;
		}
		$st=self::db()->prepare("INSERT INTO account_verify_otp (otp,email) VALUES (?,?) ");
		$st->execute([$otp,$email]);
		return ;
	}
	public static function accountverifingotp(
	$otp,
	string $email,
	int $windowSeconds=120
	):bool{
		$sql="SELECT window_start FROM account_verify_otp WHERE otp=? AND email=? LIMIT 1";
		$st=self::db()->prepare($sql);
		$st->execute([$otp,$email]);
		$row=$st->fetch();
		if(!$row){
			return false;
		}
		$windowStart=strtotime($row['window_start']);
		$now=time();
		if($now - $windowStart>= $windowSeconds){
			return false;
		}
		return true;
		
	}
	
	public static function verifyaccountchangepassword($email,$hash){
		$sql="UPDATE admin SET password=? WHERE email=?";
		$st=self::db()->prepare($sql);
		$st->execute([$hash,$email]);
		return ;
	}
}