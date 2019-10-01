/*
//Destoon original function
function send_sms($mobile, $message, $word = 0, $time = 0) {
	global $DT, $_username;
	if(!$DT['sms'] || !DT_CLOUD_UID || !DT_CLOUD_KEY || !is_mobile($mobile) || strlen($message) < 5) return false;
	$word or $word = word_count($message);
	$sms_message = $message;
	$data = 'sms_uid='.DT_CLOUD_UID.'&sms_key='.md5(DT_CLOUD_KEY.'|'.$mobile.'|'.md5($sms_message)).'&sms_charset='.DT_CHARSET.'&sms_mobile='.$mobile.'&sms_message='.rawurlencode($sms_message).'&sms_time='.$time.'&sms_url='.rawurlencode(DT_PATH);
	$code = dcurl('http://sms.destoon.com/send.php', $data);
	if($code && strpos($code, 'destoon_sms_code=') !== false) {
		$code = explode('destoon_sms_code=', $code);
		$code = $code[1];
	} else {
		$code = 'Can Not Connect SMS Server';
	}
	DB::query("INSERT INTO ".DT_PRE."sms (mobile,message,word,editor,sendtime,ip,code) VALUES ('$mobile','$message','$word','$_username','".DT_TIME."','".DT_IP."','$code')");
	return $code;
}
*/

/*
//	106.ihuyi.cn以fopen文件方式发送短信，需要php配置allow_url_fopen=Off
function Post($curlPost,$url){
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_NOBODY, true);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);
		$return_str = curl_exec($curl);
		curl_close($curl);
		return $return_str;
}

function xml_to_array($xml){
	$reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";
	if(preg_match_all($reg, $xml, $matches)){
		$count = count($matches[0]);
		for($i = 0; $i < $count; $i++){
		$subxml= $matches[2][$i];
		$key = $matches[1][$i];
			if(preg_match( $reg, $subxml )){
				$arr[$key] = xml_to_array( $subxml );
			}else{
				$arr[$key] = $subxml;
			}
		}
	}
	return $arr;
}

function send_sms($mobile, $message, $word = 0, $time = 0) {
	global $DT, $_username;
	if(!$DT['sms'] || !DT_CLOUD_UID || !DT_CLOUD_KEY || !is_mobile($mobile) || strlen($message) < 5) return false;
	$word or $word = word_count($message);
	$sms_message = $message;
	$target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
	$post_data = "account=".DT_CLOUD_UID."&password=".DT_CLOUD_KEY."&mobile=".$mobile."&content=".rawurlencode($sms_message);
	$gets =  xml_to_array(Post($post_data, $target));
	$code = $gets['SubmitResult']['code'];
	if($code == 2) {
		$sms_num = ceil($word/$DT['sms_len']);
		$code = $DT['sms_ok'].'/'.$sms_num;
	} else {
		$code = $gets['SubmitResult']['msg'].$code;
	}
	DB::query("INSERT INTO ".DT_PRE."sms (mobile,message,word,editor,sendtime,ip,code) VALUES ('$mobile','$message','$word','$_username','".DT_TIME."','".DT_IP."','$code')");
	return $code;
}
*/

/*
//	smsapi.c123.cn以文件传递方式发送短信
	function Get($url)
	{
		if(function_exists('file_get_contents'))
		{
			$file_contents = file_get_contents($url);
		}
		else
		{
			$ch = curl_init();
			$timeout = 5;
			curl_setopt ($ch, CURLOPT_URL, $url);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
			$file_contents = curl_exec($ch);
			curl_close($ch);
		}
		return $file_contents;
	} 

function send_sms($mobile, $message, $word = 0, $time = 0) {
	global $DT, $_username;
	if(!$DT['sms'] || !DT_CLOUD_UID || !DT_CLOUD_KEY || !is_mobile($mobile) || strlen($message) < 5) return false;
	$word or $word = word_count($message);
	$sms_message = $message;
	$url='http://smsapi.c123.cn/OpenPlatform/OpenApi';
	$cgid = 733;
	$data = $url.'?action=sendOnce&ac='.DT_CLOUD_UID.'&authkey='.DT_CLOUD_KEYa.'&cgid='.$cgid.'&c='.rawurlencode($sms_message).'&m='.$mobile;
	$code_xml = Get($data);
	$code_str = "<xml name=\"sendOnce\" result=\"";
	$code_mid = str_replace($code_str,"",$code_xml);
	$code_pos = strpos($code_mid,"\"");
	$code = substr($code_mid,0,$code_pos);
	if(trim($code) == '1' ) {
		$code = $DT['sms_ok'];
	} else {
		$code = "Error:".$code_xml;
	}
	DB::query("INSERT INTO ".DT_PRE."sms (mobile,message,word,editor,sendtime,ip,code) VALUES ('$mobile','$message','$word','$_username','".DT_TIME."','".DT_IP."','$code')");
	return $code;
}

//	smsapi.c123.cn以POST方式发送短信，帐号与签名相关例如1001@500856680001预计为【优途网】，1002估计为第二个签名，待验证
function postSMS($url,$data='')
{
	$row = parse_url($url);
	$host = $row['host'];
	$port = $row['port'] ? $row['port']:80;
	$file = $row['path'];
	while (list($k,$v) = each($data)) 
	{
		$post .= rawurlencode($k)."=".rawurlencode($v)."&";	//转URL标准码
	}
	$post = substr( $post , 0 , -1 );
	$len = strlen($post);
	$fp = @fsockopen( $host ,$port, $errno, $errstr, 10);
	if (!$fp) {
		return "$errstr ($errno)\n";
	} else {
		$receive = '';
		$out = "POST $file HTTP/1.0\r\n";
		$out .= "Host: $host\r\n";
		$out .= "Content-type: application/x-www-form-urlencoded\r\n";
		$out .= "Connection: Close\r\n";
		$out .= "Content-Length: $len\r\n\r\n";
		$out .= $post;		
		fwrite($fp, $out);
		while (!feof($fp)) {
			$receive .= fgets($fp, 128);
		}
		fclose($fp);
		$receive = explode("\r\n\r\n",$receive);
		unset($receive[0]);
		return implode("",$receive);
	}
}

function send_sms($mobile, $message, $word = 0, $time = 0) {
	global $DT, $_username;
	if(!$DT['sms'] || !DT_CLOUD_UID || !DT_CLOUD_KEY || !is_mobile($mobile) || strlen($message) < 3) return false;
	$word or $word = word_count($message);
	$sms_message = $message;
	$url='http://smsapi.c123.cn/OpenPlatform/OpenApi';
	$cgid='733';
	$csid='325';
	$t = $time ? date('yyyyMMddHHmmss',$time) : '';
	$data = array
		(
		'action'=>'sendOnce',
		'ac'=>DT_CLOUD_UID,
		'authkey'=>DT_CLOUD_KEY,
		'cgid'=>$cgid,
		'm'=>$mobile,
		'c'=>$sms_message,
		'csid'=>$csid,
		't'=>$t
		);
	$xml= postSMS($url,$data);
	$re=simplexml_load_string(utf8_encode($xml));
	if(trim($re['result'])==1) {
		$code = $DT['sms_ok'];
	} else {
		switch(trim($re['result'])){
			case  0: $code = "帐户格式不正确(正确的格式为:员工编号@企业编号)";break; 
			case -1: $code = "服务器拒绝(速度过快、限时或绑定IP不对等)如遇速度过快可延时再发";break;
			case -2: $code = "密钥不正确";break;
			case -3: $code = "密钥已锁定";break;
			case -4: $code = "参数不正确(内容和号码不能为空，手机号码数过多，发送时间错误等)";break;
			case -5: $code = "无此帐户";break;
			case -6: $code = "帐户已锁定或已过期";break;
			case -7: $code = "帐户未开启接口发送";break;
			case -8: $code = "不可使用该通道组";break;
			case -9: $code = "帐户余额不足";break;
			case -10: $code = "内部错误";break;
			case -11: $code = "扣费失败";break;
			default: $code = "Error";break;
		}
	}
	DB::query("INSERT INTO ".DT_PRE."sms (mobile,message,word,editor,sendtime,ip,code) VALUES ('$mobile','$message','$word','$_username','".DT_TIME."','".DT_IP."','$code')");
	return $code;
}
*/

//smsapi.c123.cn即奇瑞云发送格式，2018-05-24
function send_sms($mobile, $message, $word = 0, $time = 0) {
	global $DT, $_username;
	if(!$DT['sms'] || !DT_CLOUD_UID || !DT_CLOUD_KEY || !is_mobile($mobile) || strlen($message) < 5) return false;
	$word or $word = word_count($message);
	$target = 'http://api.qirui.com:7891/mt';
	$isreport = 1;
	$requestData = array(
		'un' => DT_CLOUD_UID,
		'pw' => DT_CLOUD_KEY,
		'sm' => $message,
		'da' => $mobile,
		'rd' => $isreport,
		'dc' => 15,
		'rf' => 2,
		'tf' => 3,
	);
	$data = http_build_query($requestData);
	$result = dcurl($target, $data);
	$result = json_decode($result, true);
	if(!$result['r']) {
		$code = $DT['sms_ok'];
	} else {
		$code = $result['r'];
	}
	DB::query("INSERT INTO ".DT_PRE."sms (mobile,message,word,editor,sendtime,ip,code) VALUES ('$mobile','$message','$word','$_username','".DT_TIME."','".DT_IP."','$code')");
	return $code;
}
