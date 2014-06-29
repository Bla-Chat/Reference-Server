<?PHP
	function startsWith($haystack, $needle) {
		return $needle === "" || strpos($haystack, $needle) === 0;
	}

	function endsWith($haystack, $needle) {
		return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
	}

	function xjcpSecureString($str) {
		return mysql_real_escape_string(htmlspecialchars($str));
	}
	
	function xjcpSecureNick($str) {
		return xjcpSecureString(LOWER($str));
	}
    
	function randomstring($length = 32) {
		$chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890";
		srand((double)microtime()*1000000);
		$i = 0;
		while ($i < $length) {
		$num = rand() % strlen($chars);
		$tmp = substr($chars, $num, 1);
		$pass = $pass . $tmp;
		$i++;
		}
		return $pass;
	}
	
	function LOWER($str) {
		return strtolower($str);
	}

	function my_callback($a) {
		return chr(hexdec($a[1]));
	}
	
	function unescapeJS($string) {
		$outStr = preg_replace_callback(
  			"(\\\\x([0-9a-f]{2}))i",
  			my_callback,
  			$string
		);
		return $outStr;
	}
?>
