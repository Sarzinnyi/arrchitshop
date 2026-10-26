<?php
namespace App\models;
use Database\database;
class products{
	private static function db(){
		return database::getInstance();
	}
	public static function getallproducts(){
		$st=self::db()->prepare("SELECT p.id AS p_id,p.category,p.name,p.price,ph.color,ph.photo,p.created_at FROM products p LEFT JOIN photos ph ON ph.p_id=p.id");
		$st->execute([]);
		return $st->fetchAll();
	}
	
	public static function insertproducts($id,$category,$name,$price){
		$st=self::db()->prepare("INSERT INTO products (id,category,name,price) VALUES (?,?,?,?)");
		$st->execute([$id,$category,$name,$price]);
		return;
	}
	public static function insertstocks($p_id,$color,$size,$qty){
		$sql="SELECT * FROM stocks WHERE p_id=? AND color=? AND size=?";
		$st=self::db()->prepare($sql);
		$st->execute([$p_id,$color,$size]);
		$row=$st->fetch();
		if(!$row){
			$sql="INSERT INTO stocks (p_id,color,size,quantity) VALUES (?,?,?,?)";
		$st=self::db()->prepare($sql);
		$st->execute([$p_id,$color,$size,$qty]);
		return ;
		}
		$sql="UPDATE stocks SET quantity=quantity+? WHERE p_id=? AND color=? AND size=?";
		$st=self::db()->prepare($sql);
		$st->execute([$qty,$p_id,$color,$size]);
		return ;
	}
	
	public static function insertinstocks($p_id,$color,$size,$qty){
		$sql="INSERT INTO instocks (p_id,color,size,quantity) VALUES (?,?,?,?)";
		$st=self::db()->prepare($sql);
		$st->execute([$p_id,$color,$size,$qty]);
		return ;
	}
	
	public static function insertphoto($p_id,$color,$photo){
		$sql="SELECT * FROM photos WHERE p_id=? AND color=? AND is_cover=1";
		$st=self::db()->prepare($sql);
		$st->execute([$p_id,$color]);
		$row=$st->fetch();
		if($row){
			$sql="INSERT INTO photos (p_id,color,photo) VALUES (?,?,?)";
			$st=self::db()->prepare($sql);
			$st->execute([$p_id,$color,$photo]);
			return;
		}
		$sql="INSERT INTO photos (p_id,color,photo,is_cover) VALUES (?,?,?,1)";
			$st=self::db()->prepare($sql);
			$st->execute([$p_id,$color,$photo]);
			return;
	}
	
	public static function nameexit($name){
		$sql="SELECT id FROM products WHERE name=?";
		$st=self::db()->prepare($sql);
		$st->execute([$name]);
		return $st->fetch();
	}
	
	public static function photoexit($p_id,$color){
			   $sql="SELECT p_id,color FROM photos WHERE p_id=? AND color=?";
	   $st=self::db()->prepare($sql);
	   $st->execute([$p_id,$color]);
	   return $st->fetch();
		
	}
	
	public static function insertstockarray($p_id,$color,$array){
		forEach($array as $row){
			$sql="INSERT INTO stocks (p_id,color,size,quantity) VALUES (?,?,?,?)";
			$st=self::db()->prepare($sql);
			$st->execute([$p_id,$color,$row['size'],$row['quantity']]);
		}
	}
	
	public static function productdetail($p_id,$color){
		
	}
	
	public static function sizesletter(){
		$sql="SELECT * FROM sizes_letter";
		$st=self::db()->prepare($sql);
		$st->execute([]);
		return $st->fetchAll();
	}
    public static function sizesnumber(){
		$sql="SELECT * FROM sizes_number";
		$st=self::db()->prepare($sql);
		$st->execute([]);
		return $st->fetchAll();
	}

}