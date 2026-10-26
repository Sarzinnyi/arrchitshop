<?php
namespace App\controllers;
use App\models\products;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\models\admin;
class productsController{
	public function addnewproduct(){
		header("Content-Type:application/json");
		session_start();
		$token=$_COOKIE['token'] ?? null;
		if(!$token){
		http_response_code(401);
			echo json_encode(['message'=>'Invalid user']);
			exit;	
		}
		$category=$_POST['category'] ?? null;
		$name=$_POST['name'] ?? null;
		$color=$_POST['color'] ?? null;
		$price=$_POST['price'] ?? null;
		$file=$_FILES['photo'] ?? null;
		$size_quantity=$_POST['size_quantity'] ?? null;
		$size_array=[];
		for($i=1;$i<=$size_quantity;$i++){
			$size_array=[...$size_array,['size'=>$i,'quantity'=>0]];
		}
		
		if(!$category || !$name || !$color || !$price|| !$file || !$size_quantity){
			http_response_code(401);
			echo json_encode(['message'=>'empty']);
			exit;
		}
		$nameexit=products::nameexit($name);
		if($nameexit) {
			$p_id=$nameexit['id'] ?? null;
			$photoexit=products::photoexit($nameexit['p_id'],$color);
		}
		try{
			$key=$GLOBALS['config']['jwt']['jwt_key'];
			$decoded=JWT::decode($token,new Key($key,'HS256'));
			$id=$decoded->id;
			$admin=admin::adminbyid($id);
			if(!$admin){
				http_response_code(401);
				echo json_encode(['message'=>'Your are not authorize for this page']);
				exit;
			}
			if(!$nameexit && !$photoexit){
				$p_id=time();
			$tmpname=$file['tmp_name'] ?? null;
			$filedir=__DIR__.'/../../../public/photo/';
			$ext=pathinfo($file['name'],PATHINFO_EXTENSION);
			$photoname=time().'.'.$ext;
			$filename=$filedir.$photoname;
				if(move_uploaded_file($tmpname,$filename)){
				products::insertproducts($p_id,$category,$name,$price);
				products::insertphoto($p_id,$color,$photoname);	
				products::insertstockarray($p_id,$color,$size_array);
				echo json_encode(['message'=>'successfully add new product!!','success'=>true]);
				exit;
				}else{
					http_response_code(401);
					echo json_encode(['message'=>'upload fail']);
					exit;
				}
				
			}
			
			if($nameexit && !$photoexit){
			$tmpname=$file['tmp_name'] ?? null;
			$filedir=__DIR__.'/../../../public/photo/';
			$ext=pathinfo($file['name'],PATHINFO_EXTENSION);
			$photoname=time().'.'.$ext;
			$filename=$filedir.$photoname;
				if(move_uploaded_file($tmpname,$filename)){
				products::insertphoto($p_id,$color,$photoname);	
				products::insertstockarray($p_id,$color,$size_array);
				echo json_encode(['message'=>'successfully add new color for product!!','success'=>true]);
				exit;
				}else{
					http_response_code(401);
					echo json_encode(['message'=>'upload fail']);
					exit;
				}
				
			}
			
			if($name && $photo){
				
					http_response_code(401);
					echo json_encode(['message'=>'products already exit!!']);
					exit;
				
				
			}
			
		}catch(Exception $e){
			http_response_code(401);
			echo json_encode(['message'=>'Invalid user']);
			exit;
		}
	}
	
	public function productslist(){
		header("Content-Type:application/json");
		session_start();
		$token=$_COOKIE['token'] ?? null;
		if(!$token){
		http_response_code(401);
			echo json_encode(['message'=>'Invalid user']);
			exit;	
		}
		try{
			$key=$GLOBALS['config']['jwt']['jwt_key'];
			$decoded=JWT::decode($token,new Key($key,'HS256'));
			$id=$decoded->id;
			$admin=admin::adminbyid($id);
			if(!$admin){
				http_response_code(401);
				echo json_encode(['message'=>'Your are not authorize for this page']);
				exit;
			}
			$productslist=products::getallproducts();
			echo json_encode(['success'=>true,'productslist'=>$productslist]);
			exit;
		}catch(Firebase\JWT\ExpiredException $e){
			http_response_code(401);
				echo json_encode(['message'=>'Expired token']);
				exit;
		}catch(Exception $e){
			http_response_code(401);
				echo json_encode(['message'=>'Invalid user']);
				exit;
		}
	}
	
	public function productdetail(){
		header("Content-Type:application/json");
		session_start();
		$token=$_COOKIE['token'] ?? null;
		if(!$token){
		http_response_code(401);
			echo json_encode(['message'=>'Invalid user']);
			exit;	
		}
		$p_id=$_POST['p_id'] ?? null;
		$color=$_POST['color'] ?? null;
		if(!$p_id || !$color){
			http_response_code(401);
			echo json_encode(['message'=>'Empty ']);
			exit;	
		}
		try{
			$key=$GLOBALS['config']['jwt']['jwt_key'];
			$decoded=JWT::decode($token,new Key($key,'HS256'));
			$id=$decoded->id;
			$admin=admin::adminbyid($id);
			if(!$admin){
				http_response_code(401);
				echo json_encode(['message'=>'Your are not authorize for this page']);
				exit;
			}
			$productdetail=products::productdetail($p_id,$color);
			echo json_encode(['success'=>true,'productdetail'=>$productdetail]);
			exit;
		}catch(Firebase\JWT\ExpiredException $e){
			http_response_code(401);
				echo json_encode(['message'=>'Expired token']);
				exit;
		}catch(Exception $e){
			http_response_code(401);
				echo json_encode(['message'=>'Invalid user']);
				exit;
		}
	}
	public function productssizes(){
		header("Content-Type:application/json");
		session_start();
		$token=$_COOKIE['token'] ?? null;
		if(!$token){
		http_response_code(401);
			echo json_encode(['message'=>'Invalid user']);
			exit;	
		}
		
		try{
			$key=$GLOBALS['config']['jwt']['jwt_key'];
			$decoded=JWT::decode($token,new Key($key,'HS256'));
			$id=$decoded->id;
			$admin=admin::adminbyid($id);
			if(!$admin){
				http_response_code(401);
				echo json_encode(['message'=>'Your are not authorize for this page']);
				exit;
			}
			$sizes_letter=products::sizesletter();
			$sizes_number=products::sizesnumber();
			echo json_encode(['success'=>true,'sizes_letter'=>$sizes_letter,'sizes_number'=>$sizes_number]);
			exit;
		}catch(Firebase\JWT\ExpiredException $e){
			http_response_code(401);
				echo json_encode(['message'=>'Expired token']);
				exit;
		}catch(Exception $e){
			http_response_code(401);
				echo json_encode(['message'=>'Invalid user']);
				exit;
		}
	}
}