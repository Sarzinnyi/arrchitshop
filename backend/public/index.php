<?php
$origins=['http://localhost:5173','http://localhost:5174','http://localhost:8081','http://10.42.0.1:8000'];
$origin=in_array($_SERVER['HTTP_ORIGIN'],$origins) ? $_SERVER['HTTP_ORIGIN'] : null;
header("Access-Control-Allow-Origin: $origin");
header('Access-Control-Allow-Headers: Content-Type,Authorization,AUTHORIZATION');
header('Access-Control-Allow-Methods: POST,GET,PUT,DELETE,OPTIONS');
header('Access-Control-Allow-Credentials: true');
require_once __DIR__."/../vendor/autoload.php";
$GLOBALS['config']=require_once __DIR__."/../sou/config/config.php";
use App\controllers\adminController;
use App\controllers\usersController;
use App\controllers\productsController;
use Router\routing;
if($_SERVER['REQUEST_METHOD']=='OPTIONS'){
	http_response_code(200);
	exit;
}
$router=new routing($_SERVER['REQUEST_URI'],$_SERVER['REQUEST_METHOD']);
$router->post('/admin/admin/login',[new adminController(),'login']);
$router->get('/admin/admin/logout',[new adminController(),'logout']);
$router->get('/admin/admin/info',[new adminController(),'admin']);
$router->post('/admin/admin/accountverifyreqotp',[new adminController(),'accountverifysentotp']);
$router->post('/admin/admin/accountverifingotp',[new adminController(),'accountverifingotp']);
$router->post('/admin/admin/accountchangepassword',[new adminController(),'accountchangepassword']);

$router->post('/user/login',[new usersController(),'login']);
$router->get('/user/logout',[new usersController(),'logout']);
$router->get('/user/info',[new usersController(),'user']);
$router->post('/user/accountverifyreqotp',[new usersController(),'accountverifysentotp']);
$router->post('/user/accountverifingotp',[new usersController(),'accountverifingotp']);
$router->post('/user/accountchangepassword',[new usersController(),'accountchangepassword']);

$router->post('/products/addnew',[new productsController(),'addnewproduct']);
$router->get('/products/list',[new productsController(),'productslist']);
$router->post('/products/detail',[new productsController(),'productdetail']);
$router->get('/admin/products/sizes',[new productsController(),'productssizes']);
$router->dispatch();