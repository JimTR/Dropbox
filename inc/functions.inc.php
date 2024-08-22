<?PHP
global $database;
	$build = "47904-935645429";
    function set_option($key, $val)
    {
        $db = Database::getDatabase();
        $db->query('REPLACE INTO options (`key`, `value`) VALUES (:key:, :value:)', array('key' => $key, 'value' => $val));
    }

    function get_option($key, $default = null)
    {
        $db = Database::getDatabase();
        $db->query('SELECT `value` FROM options WHERE `key` = :key:', array('key' => $key));
        if($db->hasRows())
            return $db->getValue();
        else
            return $default;
    }

    function delete_option($key)
    {
        $db = Database::getDatabase();
        $db->query('DELETE FROM options WHERE `key` = :key:', array('key' => $key));
        return $db->affectedRows();
    }

    function printr($var,$return)
    {
		
        $output = print_r($var,true);
        $output = str_replace("\n", "<br>", $output);
        $output = str_replace(' ', '&nbsp;', $output);
        if ($return === true){
			
        echo "<div style='font-family:courier;'>$output</div>";}
        if ($return === false) {
			
			return $output;
		}
		
			
    }

    // Formats a given number of seconds into proper mm:ss format
    function format_time($seconds)
    {
        return floor($seconds / 60) . ':' . str_pad($seconds % 60, 2, '0');
    }

    // Given a string such as "comment_123" or "id_57", it returns the final, numeric id.
    function split_id($str)
    {
        //return match('/[_-]([0-9]+)$/', $str, 1);
    }

    // Creates a friendly URL slug from a string
    function slugify($str)
    {
        $str = preg_replace('/[^a-zA-Z0-9 -]/', '', $str);
        $str = strtolower(str_replace(' ', '-', trim($str)));
        $str = preg_replace('/-+/', '-', $str);
        return $str;
    }

    // Computes the *full* URL of the current page (protocol, server, path, query parameters, etc)
    function full_url()
    {
        //$s = empty($_SERVER['HTTPS']) ? '' : ($_SERVER['HTTPS'] == 'on') ? 's' : '';
        $protocol = substr(strtolower($_SERVER['SERVER_PROTOCOL']), 0, strpos(strtolower($_SERVER['SERVER_PROTOCOL']), '/')) . $s;
        $port = ($_SERVER['SERVER_PORT'] == '80') ? '' : (":".$_SERVER['SERVER_PORT']);
        return $protocol . "://" . $_SERVER['HTTP_HOST'] . $port . $_SERVER['REQUEST_URI'];
    }

    // Returns an English representation of a date
    // Graciously stolen from http://ejohn.org/files/pretty.js
    function time2str($ts)
    {
        if(!ctype_digit($ts))
            $ts = strtotime($ts);

        $diff = time() - $ts;
        if($diff == 0)
            return 'now';
        elseif($diff > 0)
        {
            $day_diff = floor($diff / 86400);
            if($day_diff == 0)
            {
                if($diff < 60) return 'just now';
                if($diff < 120) return '1 minute ago';
                if($diff < 3600) return floor($diff / 60) . ' minutes ago';
                if($diff < 7200) return '1 hour ago';
                if($diff < 86400) return floor($diff / 3600) . ' hours ago';
            }
            if($day_diff == 1) return 'Yesterday';
            if($day_diff < 7) return $day_diff . ' days ago';
            if($day_diff < 31) return ceil($day_diff / 7) . ' weeks ago';
            if($day_diff < 60) return 'last month';
            $ret = date('F Y', $ts);
            return ($ret == 'December 1969') ? '' : $ret;
        }
        else
        {
            $diff = abs($diff);
            $day_diff = floor($diff / 86400);
            if($day_diff == 0)
            {
                if($diff < 120) return 'in a minute';
                if($diff < 3600) return 'in ' . floor($diff / 60) . ' minutes';
                if($diff < 7200) return 'in an hour';
                if($diff < 86400) return 'in ' . floor($diff / 3600) . ' hours';
            }
            if($day_diff == 1) return 'Tomorrow';
            if($day_diff < 4) return date('l', $ts);
            if($day_diff < 7 + (7 - date('w'))) return 'next week';
            if(ceil($day_diff / 7) < 4) return 'in ' . ceil($day_diff / 7) . ' weeks';
            if(date('n', $ts) == date('n') + 1) return 'next month';
            $ret = date('F Y', $ts);
            return ($ret == 'December 1969') ? '' : $ret;
        }
    }

    // Returns an array representation of the given calendar month.
    // The array values are timestamps which allow you to easily format
    // and manipulate the dates as needed.
    function calendar($month = null, $year = null)
    {
        if(is_null($month)) $month = date('n');
        if(is_null($year)) $year = date('Y');

        $first = mktime(0, 0, 0, $month, 1, $year);
        $last = mktime(23, 59, 59, $month, date('t', $first), $year);

        $start = $first - (86400 * date('w', $first));
        $stop = $last + (86400 * (7 - date('w', $first)));

        $out = array();
        while($start < $stop)
        {
            $week = array();
            if($start > $last) break;
            for($i = 0; $i < 7; $i++)
            {
                $week[$i] = $start;
                $start += 86400;
            }
            $out[] = $week;
        }

        return $out;
    }

    // Processes mod_rewrite URLs into key => value pairs
    // See .htacess for more info.
    function pick_off($grab_first = false, $sep = '/')
    {
        $ret = array();
        $arr = explode($sep, trim($_SERVER['REQUEST_URI'], $sep));
        if($grab_first) $ret[0] = array_shift($arr);
        while(count($arr) > 0)
            $ret[array_shift($arr)] = array_shift($arr);
        return (count($ret) > 0) ? $ret : false;
    }

    // Creates a list of <option>s from the given database table.
    // table name, column to use as value, column(s) to use as text, default value(s) to select (can accept an array of values), extra sql to limit results
    function get_options($table, $val, $text, $default = null, $sql = '')
    {
        $db = Database::getDatabase(true);
        $out = '';

        $table = $db->escape($table);
        $rows = $db->getRows("SELECT * FROM `$table` $sql");
        foreach($rows as $row)
        {
            $the_text = '';
            if(!is_array($text)) $text = array($text); // Allows you to concat multiple fields for display
            foreach($text as $t)
                $the_text .= $row[$t] . ' ';
            $the_text = htmlspecialchars(trim($the_text));

            if(!is_null($default) && $row[$val] == $default)
                $out .= '<option value="' . htmlspecialchars($row[$val], ENT_QUOTES) . '" selected="selected">' . $the_text . '</option>';
            elseif(is_array($default) && in_array($row[$val],$default))
                $out .= '<option value="' . htmlspecialchars($row[$val], ENT_QUOTES) . '" selected="selected">' . $the_text . '</option>';
            else
                $out .= '<option value="' . htmlspecialchars($row[$val], ENT_QUOTES) . '">' . $the_text . '</option>';
        }
        return $out;
    }

    // More robust strict date checking for string representations
    function chkdate($str)
    {
        // Requires PHP 5.2
        if(function_exists('date_parse'))
        {
            $info = date_parse($str);
            if($info !== false && $info['error_count'] == 0)
            {
                if(checkdate($info['month'], $info['day'], $info['year']))
                    return true;
            }

            return false;
        }

        // Else, for PHP < 5.2
        return strtotime($str);
    }

    // Converts a date/timestamp into the specified format
	function dater($date = null, $format = null)
    {
        if(is_null($format))
            $format = 'Y-m-d H:i:s';

        if(is_null($date))
            $date = time();

		if(is_int($date))
			return date($format, $date);
		if(is_float($date))
			return date($format, $date);
		if(is_string($date)) {
	        if(ctype_digit($date) === true)
	            return date($format, $date);
			if((preg_match('/[^0-9.]/', $date) == 0) && (substr_count($date, '.') <= 1))
				return date($format, floatval($date));
			return date($format, strtotime($date));
		}
		
		// If $date is anything else, you're doing something wrong,
		// so just let PHP error out however it wants.
		return date($format, $date);
    }

   
    // Returns an array of the values of the specified column from a multi-dimensional array
    function gimme($arr, $key = null)
    {
        if(is_null($key))
            $key = current(array_keys($arr));

        $out = array();
        foreach($arr as $a)
            $out[] = $a[$key];

        return $out;
    }

   
    // Serves an external document for download as an HTTP attachment.
    function download_document($filename, $mimetype = 'application/octet-stream')
    {
        if(!file_exists($filename) || !is_readable($filename)) return false;
        $base = basename($filename);
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Content-Disposition: attachment; filename=$base");
        header("Content-Length: " . filesize($filename));
        header("Content-Type: $mimetype");
        readfile($filename);
        exit();
    }

    // Retrieves the filesize of a remote file.
    function remote_filesize($url, $user = null, $pw = null)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        if(!is_null($user) && !is_null($pw))
        {
            $headers = array('Authorization: Basic ' .  base64_encode("$user:$pw"));
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $head = curl_exec($ch);
        curl_close($ch);

        preg_match('/Content-Length:\s([0-9].+?)\s/', $head, $matches);

        return isset($matches[1]) ? $matches[1] : false;
    }

	// Inserts a string within another string at a specified location
	function str_insert($needle, $haystack, $location)
	{
	   $front = substr($haystack, 0, $location);
	   $back  = substr($haystack, $location);

	   return $front . $needle . $back;
	}

    // Outputs a filesize in human readable format.
    function bytes2str($val, $round = 0)
    {
        $unit = array('','K','M','G','T','P','E','Z','Y');
        while($val >= 1000)
        {
            $val /= 1024;
            array_shift($unit);
        }
        return round($val, $round) . array_shift($unit) . 'B';
    }

   
    // Returns the lat, long of an address via Yahoo!'s geocoding service.
    // You'll need an App ID, which is available from here:
    // http://developer.yahoo.com/maps/rest/V1/geocode.html
    // Note: needs to be updated to use PlaceFinder instead.
   
    // Quick and dirty wrapper for curl scraping.
   

    // Accepts any number of arguments and returns the first non-empty one
    function pick()
    {
        foreach(func_get_args() as $arg)
            if(!empty($arg))
                return $arg;
        return '';
    }

    // Secure a PHP script using basic HTTP authentication
    function http_auth($un, $pw, $realm = "Secured Area")
    {
        if(!(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW']) && $_SERVER['PHP_AUTH_USER'] == $un && $_SERVER['PHP_AUTH_PW'] == $pw))
        {
            header('WWW-Authenticate: Basic realm="' . $realm . '"');
            header('Status: 401 Unauthorized');
            exit();
        }
    }

 
    // Class Autloader
    spl_autoload_register('framework_autoload');

    function framework_autoload($class_name)
    {
        $filename = DOC_ROOT . '/inc/class.' . strtolower($class_name) . '.php';
        //echo $filename.'<br>';
        if(file_exists($filename))
            require $filename;
    }

    // Returns a file's mimetype based on its extension
    function mime_type($filename, $default = 'application/octet-stream')
    {
        $mime_types = array('323'     => 'text/h323',
                            'acx'     => 'application/internet-property-stream',
                            'ai'      => 'application/postscript',
                            'aif'     => 'audio/x-aiff',
                            'aifc'    => 'audio/x-aiff',
                            'aiff'    => 'audio/x-aiff',
                            'asf'     => 'video/x-ms-asf',
                            'asr'     => 'video/x-ms-asf',
                            'asx'     => 'video/x-ms-asf',
                            'au'      => 'audio/basic',
                            'avi'     => 'video/x-msvideo',
                            'axs'     => 'application/olescript',
                            'bas'     => 'text/plain',
                            'bcpio'   => 'application/x-bcpio',
                            'bin'     => 'application/octet-stream',
                            'bmp'     => 'image/bmp',
                            'c'       => 'text/plain',
                            'cat'     => 'application/vnd.ms-pkiseccat',
                            'cdf'     => 'application/x-cdf',
                            'cer'     => 'application/x-x509-ca-cert',
                            'class'   => 'application/octet-stream',
                            'clp'     => 'application/x-msclip',
                            'cmx'     => 'image/x-cmx',
                            'cod'     => 'image/cis-cod',
                            'cpio'    => 'application/x-cpio',
                            'crd'     => 'application/x-mscardfile',
                            'crl'     => 'application/pkix-crl',
                            'crt'     => 'application/x-x509-ca-cert',
                            'csh'     => 'application/x-csh',
                            'css'     => 'text/css',
                            'dcr'     => 'application/x-director',
                            'der'     => 'application/x-x509-ca-cert',
                            'dir'     => 'application/x-director',
                            'dll'     => 'application/x-msdownload',
                            'dms'     => 'application/octet-stream',
                            'doc'     => 'application/msword',
                            'dot'     => 'application/msword',
                            'dvi'     => 'application/x-dvi',
                            'dxr'     => 'application/x-director',
                            'eps'     => 'application/postscript',
                            'etx'     => 'text/x-setext',
                            'evy'     => 'application/envoy',
                            'exe'     => 'application/octet-stream',
                            'fif'     => 'application/fractals',
                            'flac'    => 'audio/flac',
                            'flr'     => 'x-world/x-vrml',
                            'gif'     => 'image/gif',
                            'gtar'    => 'application/x-gtar',
                            'gz'      => 'application/x-gzip',
                            'h'       => 'text/plain',
                            'hdf'     => 'application/x-hdf',
                            'hlp'     => 'application/winhlp',
                            'hqx'     => 'application/mac-binhex40',
                            'hta'     => 'application/hta',
                            'htc'     => 'text/x-component',
                            'htm'     => 'text/html',
                            'html'    => 'text/html',
                            'htt'     => 'text/webviewhtml',
                            'ico'     => 'image/x-icon',
                            'ief'     => 'image/ief',
                            'iii'     => 'application/x-iphone',
                            'ins'     => 'application/x-internet-signup',
                            'isp'     => 'application/x-internet-signup',
                            'jfif'    => 'image/pipeg',
                            'jpe'     => 'image/jpeg',
                            'jpeg'    => 'image/jpeg',
                            'jpg'     => 'image/jpeg',
                            'js'      => 'application/x-javascript',
                            'latex'   => 'application/x-latex',
                            'lha'     => 'application/octet-stream',
                            'lsf'     => 'video/x-la-asf',
                            'lsx'     => 'video/x-la-asf',
                            'lzh'     => 'application/octet-stream',
                            'm13'     => 'application/x-msmediaview',
                            'm14'     => 'application/x-msmediaview',
                            'm3u'     => 'audio/x-mpegurl',
                            'man'     => 'application/x-troff-man',
                            'mdb'     => 'application/x-msaccess',
                            'me'      => 'application/x-troff-me',
                            'mht'     => 'message/rfc822',
                            'mhtml'   => 'message/rfc822',
                            'mid'     => 'audio/mid',
                            'mny'     => 'application/x-msmoney',
                            'mov'     => 'video/quicktime',
                            'movie'   => 'video/x-sgi-movie',
                            'mp2'     => 'video/mpeg',
                            'mp3'     => 'audio/mpeg',
                            'mpa'     => 'video/mpeg',
                            'mpe'     => 'video/mpeg',
                            'mpeg'    => 'video/mpeg',
                            'mpg'     => 'video/mpeg',
                            'mpp'     => 'application/vnd.ms-project',
                            'mpv2'    => 'video/mpeg',
                            'ms'      => 'application/x-troff-ms',
                            'mvb'     => 'application/x-msmediaview',
                            'nws'     => 'message/rfc822',
                            'oda'     => 'application/oda',
                            'oga'     => 'audio/ogg',
                            'ogg'     => 'audio/ogg',
                            'ogv'     => 'video/ogg',
                            'ogx'     => 'application/ogg',
                            'p10'     => 'application/pkcs10',
                            'p12'     => 'application/x-pkcs12',
                            'p7b'     => 'application/x-pkcs7-certificates',
                            'p7c'     => 'application/x-pkcs7-mime',
                            'p7m'     => 'application/x-pkcs7-mime',
                            'p7r'     => 'application/x-pkcs7-certreqresp',
                            'p7s'     => 'application/x-pkcs7-signature',
                            'pbm'     => 'image/x-portable-bitmap',
                            'pdf'     => 'application/pdf',
                            'pfx'     => 'application/x-pkcs12',
                            'pgm'     => 'image/x-portable-graymap',
                            'pko'     => 'application/ynd.ms-pkipko',
                            'pma'     => 'application/x-perfmon',
                            'pmc'     => 'application/x-perfmon',
                            'pml'     => 'application/x-perfmon',
                            'pmr'     => 'application/x-perfmon',
                            'pmw'     => 'application/x-perfmon',
                            'pnm'     => 'image/x-portable-anymap',
                            'pot'     => 'application/vnd.ms-powerpoint',
                            'ppm'     => 'image/x-portable-pixmap',
                            'pps'     => 'application/vnd.ms-powerpoint',
                            'ppt'     => 'application/vnd.ms-powerpoint',
                            'prf'     => 'application/pics-rules',
                            'ps'      => 'application/postscript',
                            'pub'     => 'application/x-mspublisher',
                            'qt'      => 'video/quicktime',
                            'ra'      => 'audio/x-pn-realaudio',
                            'ram'     => 'audio/x-pn-realaudio',
                            'ras'     => 'image/x-cmu-raster',
                            'rgb'     => 'image/x-rgb',
                            'rmi'     => 'audio/mid',
                            'roff'    => 'application/x-troff',
                            'rtf'     => 'application/rtf',
                            'rtx'     => 'text/richtext',
                            'scd'     => 'application/x-msschedule',
                            'sct'     => 'text/scriptlet',
                            'setpay'  => 'application/set-payment-initiation',
                            'setreg'  => 'application/set-registration-initiation',
                            'sh'      => 'application/x-sh',
                            'shar'    => 'application/x-shar',
                            'sit'     => 'application/x-stuffit',
                            'snd'     => 'audio/basic',
                            'spc'     => 'application/x-pkcs7-certificates',
                            'spl'     => 'application/futuresplash',
                            'src'     => 'application/x-wais-source',
                            'sst'     => 'application/vnd.ms-pkicertstore',
                            'stl'     => 'application/vnd.ms-pkistl',
                            'stm'     => 'text/html',
                            'svg'     => "image/svg+xml",
                            'sv4cpio' => 'application/x-sv4cpio',
                            'sv4crc'  => 'application/x-sv4crc',
                            't'       => 'application/x-troff',
                            'tar'     => 'application/x-tar',
                            'tcl'     => 'application/x-tcl',
                            'tex'     => 'application/x-tex',
                            'texi'    => 'application/x-texinfo',
                            'texinfo' => 'application/x-texinfo',
                            'tgz'     => 'application/x-compressed',
                            'tif'     => 'image/tiff',
                            'tiff'    => 'image/tiff',
                            'tr'      => 'application/x-troff',
                            'trm'     => 'application/x-msterminal',
                            'tsv'     => 'text/tab-separated-values',
                            'txt'     => 'text/plain',
                            'uls'     => 'text/iuls',
                            'ustar'   => 'application/x-ustar',
                            'vcf'     => 'text/x-vcard',
                            'vrml'    => 'x-world/x-vrml',
                            'wav'     => 'audio/x-wav',
                            'wcm'     => 'application/vnd.ms-works',
                            'wdb'     => 'application/vnd.ms-works',
                            'wks'     => 'application/vnd.ms-works',
                            'wmf'     => 'application/x-msmetafile',
                            'wps'     => 'application/vnd.ms-works',
                            'wri'     => 'application/x-mswrite',
                            'wrl'     => 'x-world/x-vrml',
                            'wrz'     => 'x-world/x-vrml',
                            'xaf'     => 'x-world/x-vrml',
                            'xbm'     => 'image/x-xbitmap',
                            'xla'     => 'application/vnd.ms-excel',
                            'xlc'     => 'application/vnd.ms-excel',
                            'xlm'     => 'application/vnd.ms-excel',
                            'xls'     => 'application/vnd.ms-excel',
                            'xlt'     => 'application/vnd.ms-excel',
                            'xlw'     => 'application/vnd.ms-excel',
                            'xof'     => 'x-world/x-vrml',
                            'xpm'     => 'image/x-xpixmap',
                            'xwd'     => 'image/x-xwindowdump',
                            'z'       => 'application/x-compress',
                            'zip'     => 'application/zip');
        $ext = pathinfo($filename, PATHINFO_EXTENSION);
        return isset($mime_types[$ext]) ? $mime_types[$ext] : $default;
    }
function getnid ()
	{
		srand(time());
		return md5(rand() . microtime());
	}

function log_to ($file,$info)
	{
		// log stuff
		//die("info = ".$info." file = ".$file); 
		if (!strrpos ($info , "\r\n" )){ $info .="\r\n";}
		file_put_contents($file, $info, FILE_APPEND );
		chmod($file, 0666); 
	}
		
function filelength($file)
	{
		/* returns the number of lines in a given file
		 * the file name must be fully qualified
		 * 
		 */
			$linecount = 0;
			$handle = fopen($file, "r");
			while(!feof($handle)){
				$line = fgets($handle);
				$linecount++;
				}

			fclose($handle);
			return $linecount;
}
  	   
function is_cli()
{
    if ( defined('STDIN') ){return true; }
    if ( php_sapi_name() == 'cli' ){return true;}
    if ( array_key_exists('SHELL', $_ENV) ) {return true;}
    if ( empty($_SERVER['REMOTE_ADDR']) and !isset($_SERVER['HTTP_USER_AGENT']) and count($_SERVER['argv']) > 0) {return true;} 
    if ( !array_key_exists('REQUEST_METHOD', $_SERVER) ){return true;}
    return false;
}

function root() {
	/*
	 * checks for root user not sudo user
	 * see check_sudo for priv user
	 */ 
 if (posix_getuid() === 0){return true;} 
   else {return false;}
}

function check_sudo($user) {
	$user=trim($user);
	// centos = wheel not sudo
	$j= shell_exec('getent group sudo | cut -d: -f4');
	$yes= strpos($j, $user);
	if ($yes ===0 or $yes>1) {return true;}
	else { return false;}
}

function orderBy(&$data, $field,$order){
  $args['field'] = $field;
  $args['order'] =$order;
   usort($data, function($a, $b) use ($args) {
		if ($args['order'] == "d") {return strnatcmp($b[$args['field']], $a[$args['field']]);}
		else {return strnatcmp($a[$args['field']], $b[$args['field']]);}
	});
}
  
function getObscuredText($strMaskChar='*') {
	if(!is_string($strMaskChar) || $strMaskChar==''){$strMaskChar='*';}
	$strMaskChar=substr($strMaskChar,0,1);
	readline_callback_handler_install('', function(){});
	$strObscured='';
	while(true){
		$strChar = stream_get_contents(STDIN, 1);
		$intCount=0;
		// Protect against copy and paste passwords
		// Comment \/\/\/ to remove password injection protection
		$arrRead = array(STDIN);
		$arrWrite = NULL;
		$arrExcept = NULL;
		while (stream_select($arrRead, $arrWrite, $arrExcept, 0,0) && in_array(STDIN, $arrRead)) {
			stream_get_contents(STDIN, 1);
			$intCount++;
		}
		//        /\/\/\
		// End of protection against copy and paste passwords
		if($strChar===chr(10)){break;}
		if ($intCount===0){
			if(ord($strChar)===127){
				if(strlen($strObscured)>0){
					$strObscured=substr($strObscured,0,strlen($strObscured)-1);
					echo(chr(27).chr(91)."D"." ".chr(27).chr(91)."D");
				}
			}
			elseif ($strChar>=' '){
				$strObscured.=$strChar;
				echo($strMaskChar);
				//echo(ord($strChar));
			}
		}
	}
	readline_callback_handler_remove();
	return($strObscured);
}
    
    function ping($addr,$port,$timeout) {
		// ping port
		if($fp = fsockopen($addr,$port,$errCode,$errStr,$timeout)){   
   //echo ' It worked'.cr;
       // echo $errStr.cr; 
        return true;
} else {
   echo 'It didn\'t work'.cr;
        echo $errStr.cr;
        return false; 
} 
fclose($fp);

	}
	
function array_find($needle, array $haystack)
{
    foreach ($haystack as $key => $value) {
        if (false !== stripos($value, $needle)) {
            return $key;
        }
    }
    return -1;
}	

function arg($x="",$default=null,$preserve=true) {
	
    static $arginfo = [];
    //echo "what is this ? $x\r\n";
	//error_reporting(0);
    /* helper */ $contains = function($h,$n) {return (false!==strpos($h,$n));};
    /* helper */ $valuesOf = function($s) {return explode(",",$s);};

    //  called with a multiline string --> parse arguments
    if($contains($x,"\n")) {
		//  parse multiline text input
        $args = $GLOBALS["argv"] ?: [];
        //$args = array_map('strtolower', $args); // everything lower case
        $rows = preg_split('/\s*\n\s*/',trim($x));
        $data = $valuesOf("char,word,type,help");
        foreach($rows as $row) {
			//$row=strtolower($row);
            list($char,$word,$type,$help) = preg_split('/\s\s+/',$row);
            $char = trim($char,"-");
            $word = trim($word,"-");
            $key  = $word ?: $char ?: ""; if($key==="") continue;
            $arginfo[$key] = compact($data);
            $arginfo[$key]["value"] = null;
        }

        $nr = 0;
        while($args) {

            $x = array_shift($args); if($x[0]<>"-") {$arginfo[$nr++]["value"]=$x;continue;}
            $x = ltrim($x,"-");
            $v = null; if($contains($x,"=")) list($x,$v) = explode("=",$x,2);
            $k = "";foreach($arginfo as $k=>$arg) if(($arg["char"]==$x)||($arg["word"]==$x)) break;
            $t = $arginfo[$k]["type"];
            switch($t) {
                case "bool" : $v = true; break;
                case "str"  : if(is_null($v)) $v = array_shift($args); break;
                case "int"  : if(is_null($v)) $v = array_shift($args); $v = intval($v); break;
            }
            if ($preserve === true) {
				$arginfo[$k]["value"] = $v;
			}
			else {
				
				$arginfo[$k]["value"] = strtolower($v);
            }

        }
		//error_reporting(err);
        return $arginfo;

    }
	//error_reporting(err);
    //  called with a question --> read argument value
    if($x==="") return $arginfo;
    if(isset($arginfo[$x]["value"])) return $arginfo[$x]["value"];
    return $default;

}
function formatBytes($bytes, $precision = 0) { 
    $units = array('B ', 'KB', 'MB', 'GB', 'TB'); 
    $bytes = max($bytes, 0); 
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024)); 
    $pow = min($pow, count($units) - 1); 
    // Uncomment one of the following alternatives
    // $bytes /= pow(1024, $pow);
     $bytes /= (1 << (10 * $pow)); 
    return round($bytes, $precision) . ' ' . $units[$pow];
    // $base = log($size, 1024);
    //$suffixes = array('', 'K', 'M', 'G', 'T');   
    //return round(pow(1024, $base - floor($base)), $precision) .' '. $suffixes[floor($base)]; 
} 