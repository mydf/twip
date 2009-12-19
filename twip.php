<?
class twip{
	const DEBUG = false;
	const WEBROOT = 'twip';
	const PARENT_API = 'http://twitter.com';
	const ERR_LOGFILE = 'err.txt';
	const LOGFILE = 'log.txt';
	const LOGTIMEZONE = 'Etc/GMT-8';


	public function twip ( $options = null ){
		$this->check_server();
		$this->method = $_SERVER['REQUEST_METHOD'];
		$this->debug = !!$options['debug'] || self::DEBUG;
		$this->webroot = !empty($options['webroot']) ? $this->mytrim($options['webroot']) : self::WEBROOT;
		$this->parent_api = !empty($options['parent_api']) ? $this->mytrim($options['parent_api']) : self::PARENT_API;
		$this->err_logfile = !empty($options['err_logfile']) ? $options['err_logfile'] : self::ERR_LOGFILE;
		$this->logfile = !empty($options['logfile']) ? $options['logfile'] : self::LOGFILE;
		$this->log_timezone = !empty($options['log_timezone']) ? $options['log_timezone'] : self::LOGTIMEZONE;
		$this->replace_shorturl = !!$options['replace_shorturl'];
		$this->pre_request();
		$this->dorequest();
		$this->post_request();
	}


	private function pre_request(){
		$this->request_api = strval(substr($_SERVER['REQUEST_URI'],strlen($this->webroot)+2));
		if($this->request_api =='' || strpos($this->request_api,'index.php')!==false){
			$this->err();
		}
		$arr = array();
		if($this->method == 'POST'){
			foreach($_POST as $key => $value){
				$arr[] = $key.'='.$value;
			}
			$this->post_data = implode('&',$arr);
		}
	}


	private function dorequest(){
		$url = $this->parent_api.'/'.$this->request_api;
		$ch = curl_init($url);
		$curl_opt = array();
		if($this->method == 'POST'){
			$curl_opt[CURLOPT_POST] = true;
			$curl_opt[CURLOPT_POSTFIELDS] = $this->post_data;
		}
		$curl_opt[CURLOPT_USERAGENT] = $_SERVER['HTTP_USER_AGENT'];
		$curl_opt[CURLOPT_RETURNTRANSFER] = true;
		$curl_opt[CURLOPT_USERPWD] = $this->user_pw();
		$curl_opt[CURLOPT_HEADERFUNCTION] = array($this,'echoheader');
		curl_setopt_array($ch,$curl_opt);
		$this->ret = curl_exec($ch);
		curl_close($ch);
	}
	private function post_request(){
        if($this->replace_shorturl){
		    $this->replace_shorturl();
        }
		header('Content-Length: '.strlen($this->ret));
        echo $this->ret;
		$this->dolog();
	}
	private function user_pw(){
		if(!empty($_SERVER['PHP_AUTH_USER'])){
			$this->username = $_SERVER['PHP_AUTH_USER'];
			return $_SERVER['PHP_AUTH_USER'].':'.$_SERVER['PHP_AUTH_PW'];
		}
		else if(!empty($_SERVER['HTTP_AUTHORIZATION'])||!empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])){
			$auth = empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION']) ? $_SERVER['HTTP_AUTHORIZATION']:$_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
			$a = base64_decode( substr($auth,6)) ;
			list($name, $password) = explode(':', $a);
			$this->username = $name;
			return $name.':'.$password;
		}
		else{
			$this->username = 'nobody';
			return '';
		}
	}
	private function mytrim($str){
		return trim($str,'/');
	}
	private function check_server(){
		if(		!function_exists('curl_init') &&
		!function_exists('curl_setopt_array') &&
		!function_exists('curl_exec') &&
		!function_exists('curl_close')){
			$this->err("curl functions doesn't exists!");
		}
		else if(!function_exists('file_get_contents') && !function_exists('file_put_contents')){
			$this->err("PHP 5 is needed!");
		}
	}
	private function err($str=null){
		if(empty($str)){
			$str = 'Seems every thing is fine.';
		}
		else{
			errlog($str);
		}
        $msg ="	
                <html>
				<head>
				<title>Twip Message Page</title>
				</head>
				<body>
				<h1>Twip Message Page</h1>
				<div>
				This is a Twitter API proxy,and is not intend to be viewed in a browser.<br />
				Visit Twip for more details. View test page HERE.View oauth page HERE.<br />
				</div>
				<div>
				".nl2br($str)."
				</div>
				</body>
				</html>
				";
		echo $msg;
		exit();
	}
	private function echoheader($ch,$str){
		if(strpos($str,'Content-Length:') === false ){
			header($str);
		}
		return strlen($str);
	}

	private function errlog($str){
		date_default_timezone_set($this->log_timezone);		//set timezone
		$msg = date('Y-m-d H:i:s').' '.$this->request_api.' '.$this->post_data.' '.$this->username.' '.$str."\n";
		file_put_contents($this->err_logfile,$msg,FILE_APPEND);
	}
	private function replace_shorturl(){
        $url_pattern = "/http:\/\/(?:j\.mp|bit\.ly|ff\.im)\/[\w]+/";
        preg_match_all($url_pattern,$this->ret,$matches);
        if(!empty($matches)){
            $query_arr = array();
            foreach($matches as $shorturl){
                $query_arr[] = "q=".$shorturl[0];
            }
            $query_str = implode("&",$query_arr);
            $json_str = file_get_contents("http://www.longurlplease.com/api/v1.1?".$query_str);
            $json_arr = json_decode($json_str,true);
            $this->ret = str_replace(array_keys($json_arr),array_values($json_arr),$this->ret);
        }
	}
	private function dolog(){
		date_default_timezone_set($this->log_timezone);		//set timezone
		$msg = date('Y-m-d H:i:s').' '.$this->request_api.' '.$this->username."\n";
		file_put_contents($this->logfile,$msg,FILE_APPEND);
	}
}
?>