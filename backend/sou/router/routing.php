<?php
namespace Router;
class routing{
	private $requri;
	private $reqmethod;
	private $routes=[];
	
	public function __construct($uri,$method){
		$this->requri=parse_url($uri,PHP_URL_PATH);
		$this->reqmethod=$method;
	}
	public function get($path,$callback){
		$this->routes['GET'][$path]=$callback;
	}
	public function post($path,$callback){
		$this->routes['POST'][$path]=$callback;
	}
	public function update($path,$callback){
		$this->routes['UPDATE'][$path]=$callback;
	}
	public function delete($path,$callback){
		$this->routes['DELETE'][$path]=$callback;
	}
	
	public function dispatch(){
		$routemethod=$this->routes[$this->reqmethod] ?? null;
		$callback=$routemethod[$this->requri] ?? null;
		if($callback){
			call_user_func($callback);
		}else{
			http_response_code(404);
			echo json_encode(['message'=>'end point no founc']);
			exit;
			}
	}
}
