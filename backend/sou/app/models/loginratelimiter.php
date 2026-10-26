<?php
namespace App\models;
use Database\database;
class loginratelimiter{
	private static function db(){
		return database::getInstance();
	}
	public static function isBlocked(
	string $ip_address,
	string $email,
	int $maxAttempts=5,
	int $windowSeconds=3600
	):bool{
		$sql="SELECT attempts,window_start FROM login_rate_limiter WHERE ip_address=? AND email=? LIMIT 1";
		$st=self::db()->prepare($sql);
		$st->execute([$ip_address,$email]);
		$row=$st->fetch();
		if(!$row){
			return false;
		}
		$windowStart=strtotime($row['window_start']);
		$now=time();
		
		if($now - $windowStart >= $windowSeconds){
			return false;
		}
		return $row['attempts'] >= $maxAttempts;
	}
	
	public static function hit(
	string $ip_address,
	string $email,
	int $windowSeconds=3600,
	):void{
		$sql="SELECT attempts,window_start FROM login_rate_limiter WHERE ip_address=? AND email=? LIMIT 1";
		$st=self::db()->prepare($sql);
		$st->execute([$ip_address,$email]);
		$row=$st->fetch();
		if(!$row){
			$sql="INSERT INTO login_rate_limiter (ip_address,email,attempts,window_start) VALUES (?,?,1,NOW())";
			$st=self::db()->prepare($sql);
			$st->execute([$ip_address,$email]);
			return ;
		}
		$windowStart=strtotime($row['window_start']);
		$now=time();
		if($now - $windowStart >= $windowSeconds){
			$sql="UPDATE login_rate_limiter SET attempts=1,window_start=NOW() WHERE ip_address=? AND email=?";
			$st=self::db()->prepare($sql);
			$st->execute([$ip_address,$email]);
			return ;
		}
		$sql="UPDATE login_rate_limiter SET attempts=attempts+1  WHERE ip_address=? AND email=?";
			$st=self::db()->prepare($sql);
			$st->execute([$ip_address,$email]);
			return ;
	}
	
	public static function clear(
string $ip,
string $email
): void {
$sql = "
DELETE FROM login_rate_limiter
WHERE ip_address = ?
AND email = ?
";
$stmt = self::db()->prepare($sql);
$stmt->execute([
$ip,
$email
]);
}

}