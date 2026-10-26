<?php
namespace App\controllers;
use App\models\admin;
use App\models\loginratelimiter;
use App\services\mailer;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
class adminController{
	
	public function login(){
		header("Content-Type:application/json");
		$email=$_POST['email'] ?? null;
		$password=$_POST['password'] ?? null;
		$ip=$_SERVER['REMOTE_ADDR'] ?? 'unknow';
		if(!$email || !$password){
			http_response_code(401);
			echo json_encode(['message'=>'incorrect email or password']);
			exit;
		}
		if(loginratelimiter::isBlocked($ip,$email,5,3600)){
			http_response_code(429);
			echo json_encode(['message'=>'too many attempts try again later']);
			exit;
		}
		$exuser=admin::checkadmin($email);
		if(!$exuser){
			http_response_code(401);
			echo json_encode(['message'=>'This email is not registered!!']);
			exit;
		}
		try{
			$hash=$exuser['password'];
		if(!password_verify($password,$hash)){
			loginratelimiter::hit($ip,$email,3600);
			http_response_code(401);
			echo json_encode(['message'=>$password]);
			exit;
		}
		session_start();
		$key=$GLOBALS['config']['jwt']['jwt_key'];
		$payload=['id'=>$exuser['id']];
		$token=JWT::encode($payload,$key,'HS256');
		setcookie('token',$token,
		[
		'expires'=>time()+2500000,
		'path'=>'/',
		'secure'=>false,
		'httponly'=>true,
		]
		);
		echo json_encode(['message'=>'login success','success'=>true,'token'=>$token]);
		exit;
		}catch(Exception $e){
			
		}
	}
	
	public function admin(){
		header("Content-Type:application/json");
		session_start();
		$token=$_COOKIE['token'] ?? null;
		if (!$token) {
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

    if (preg_match('/Bearer\s+(.+)/i', $authHeader, $matches)) {
        $token = $matches[1];
    }
}
		if(!$token){
			http_response_code(401);
			echo json_encode(['message'=>'Invalid use tr']);
			exit;
		}
		try{
			$key=$GLOBALS['config']['jwt']['jwt_key'];
			$decoded=JWT::decode($token,new Key($key,'HS256'));
			$id=$decoded->id;
			if(!$id){
			http_response_code(401);
			echo json_encode(['message'=>'Invalid user']);
			exit;	
			}
			$admin=admin::adminbyid($id);
				if(!$admin){
			http_response_code(401);
			echo json_encode(['message'=>'Invalid user ad']);
			exit;	
			}
			echo json_encode(['admin'=>$admin,'success'=>true]);
			exit;
		}catch(JWT\ExpriedException $e){
			http_response_code(401);
			echo json_encode(['message'=>'Login Expired']);
			exit;
		}catch(Exception $e){
			http_response_code(401);
			echo json_encode(['message'=>'Invalid user']);
			exit;
		}
	}
	
	public function logout(){
	header("Content-Type:application/json");
		session_start();
		$token=$_COOKIE['token'] ?? null;
		if(!$token){
			http_response_code(401);
			echo json_encode(['message'=>'Invalid user t']);
			exit;
		}
		try{
			$key=$GLOBALS['config']['jwt']['jwt_key'];
			$decoded=JWT::decode($token,new Key($key,'HS256'));
			$id=$decoded->id;
			if(!$id){
			http_response_code(401);
			echo json_encode(['message'=>'Invalid user']);
			exit;	
			}
			$admin=admin::adminbyid($id);
			if(!$admin){
			http_response_code(401);
			echo json_encode(['message'=>'Invalid user ad']);
			exit;	
			}
		  session_start();
		  setcookie('token',$token,
		  [
		  'expires'=>time()-50000,
		  'path'=>'/',
		  'secure'=>false,
		  'httponly'=>true,
		  ]
		  );
		  echo json_encode(['success'=>true]);
			exit;
		}catch(JWT\ExpriedException $e){
			http_response_code(401);
			echo json_encode(['message'=>'Login Expired']);
			exit;
		}catch(Exception $e){
			http_response_code(401);
			echo json_encode(['message'=>'Invalid user']);
			exit;
		}	
	}
	
	public function accountverifysentotp(){
		header("Content-Type:applicatiion/json");
		$otp=rand(100000,999999);
		$email=$_POST['email'] ?? null;
		admin::accountverifysentotp($otp,$email);
		mailer::sentOtp($otp,$email);
		echo json_encode(['message'=>'Otp sent','success'=>true]);
		exit;
	}
	
	public function accountverifingotp(){
		header("Content-Type:application/json");
		$otp=$_POST['otp'] ?? null;
		$email=$_POST['email'] ?? null;
		if(!$otp || !$email ){
			http_response_code(401);
			echo json_encode(['message'=>'enter otp']);
			exit;
		}
		if(!admin::accountverifingotp($otp,$email,120)){
			http_response_code(401);
			echo json_encode(['message'=>'Wrong otp']);
			exit;
		}
		session_start();
		$_SESSION['acc_verify']=true;
		$_SESSION['verify_email']=$email;
		echo json_encode(['message'=>'Success verify','success'=>true]);
		exit;
	}
	
	public function accountchangepassword(){
		header("Content-Type:application/json");
		$password=$_POST['password'] ?? null;
		$re=$_POST['reenterpassword'] ?? null;
		$ip_address=$_SERVER['REMOTE_ADDR'] ?? 'unknown';
		session_start();
		if(!$_SESSION['acc_verify']){
			http_response_code(401);
			echo json_encode(['message'=>'first verify otp']);
			exit;
		}
		$email=$_SESSION['verify_email'] ?? null;
		if($password !== $re){
			http_response_code(401);
			echo json_encode(['message'=>'does not match password']);
			exit;
		}
		$hash=password_hash($password,PASSWORD_BCRYPT);
		admin::verifyaccountchangepassword($email,$hash);
		loginratelimiter::clear($ip_address,$email);
		echo json_encode(['message'=>'success change password','success'=>true]);
		exit;
	}
}