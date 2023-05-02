#!/usr/bin/php -d memory_limit=2048M
<?php
include 'inc/master.inc.php';
	$version = 1.08;
	$build = "32460-2752354148";
	
/*
 * option a  = action
 * option b = backup path
 * option c = not used
 * option f = folder
 * option l = use local rather than dropbox folders
 * option m = mkdir
 * option p = path relative to option f
 * option d = delete file
 * option u = upload dir/filename
 * option o = overwrite
 * option t = use time
 */ 
 log_to('debug.log',shell_exec('printenv')); //debug
if (TERM === false) {
	log_to("debug.log",print_r($_SERVER,true));
	if (isset($_SERVER['BACKUP_DEST'])) {
		chdir( pathinfo($_SERVER['BACKUP_DEST'],PATHINFO_DIRNAME)); //webmin cp
	}
	else {
		chdir (pathinfo($_SERVER['PHP_SELF'],PATHINFO_DIRNAME)); // everything else
	}
}
else {
	// terminal options
	$cc = new Color();
	system("clear");
}
//
//die();
echo "DropBox Uploader  V$version ($build)".cr;
if (isset($options['debug'])) {
	echo 'DEBUG MODE'.cr;
	define('debug',true);
}
else {
	define('debug',false);
}
//config_upload_path() ;
if (isset($options['f'])) {
	if (debug) {
		echo 'f is set to '.options['f'].cr;
	}
	switch ($options['f']) {
		case 'local':
			$folder = gethostname(); // set folder to computer host name
			break;
		default:	
			$folder = $options['f']; 
	}
}
else {
	$folder= '';
}

$token = db_check_token(); // get dropbox tokens
if (debug) {
	echo "start main switch".cr;
}
if (isset($options['a'])) {
	
	switch ($options['a']) {
		
		//switch
		case  'b':
				if (debug) {
					echo "Timed Upload !".cr;
				}
				db_timed_upload();
				exit;
				
		case 'd':
			db_delete($token,$folder);
			exit;
		case 'l' :
		case 'list':
		if (!isset($options['p'])){
				db_list_files($token,$folder);
			}
			else {
				//echo $options['p'].cr;
				db_list_files($token,$folder.'/'.$options['p']);
			}
			exit;
		case 'L':
				echo 'option L not done yet!'.cr;
				exit;
					
		case 'm':
				db_mkdir($token,$folder);
				exit;
		case 'i':
		case 'info':
			$user =db_info($token);
			$quota =db_space($token,false);
			if($user['email_verified']==1) {
				$user['email_verified'] = 'Yes';
			}
			else {
				$user['email_verified'] == 'No';
			}
			//echo 'Dropbox Information'.cr;
			$table = new Table(CONSOLE_TABLE_ALIGN_RIGHT, CONSOLE_TABLE_BORDER_ASCII, 1, null, true);
			$table->addRow(array($cc->convert("%YUser%n"),$user['name']['display_name']));
			$table->addRow(array($cc->convert("%YEmail Address%n"),$user['email']));
			$table->addRow(array($cc->convert("%YVerified%n"),$user['email_verified']));
			$table->addRow(array($cc->convert("%YCountry%n"),$user['country']));
			$table->addRow(array($cc->convert("%YAccount Type%n"),$user['account_type']['.tag']));
			$table->addRow(array($cc->convert("%YQuota%n"),$quota['quota']));
			$table->addRow(array($cc->convert("%YUsed%n"),$quota['used']));
			$table->addRow(array($cc->convert("%YFree%n"),$quota['free']));
			echo $table->getTable();
			exit;
			
		case 'u':
		case 'upload':
			//echo 'Start'.cr;		
			if (isset($options['u'])) {
				
				if(!isset($options['p'])){
					$path = '';
					//echo "path = $path".cr;
				}
				$file = $options['u'];
				if (substr($file, -1) =='/') {
					$file = rtrim($file, "/ ");
					//echo "file=$file";
				}
				if(isset($options['t'])) {
					$file = date("d-m-y");
					//echo "file set to $file".cr;
				} // set up file to be an incremental backup
				if (is_dir($file)) {
					//echo 'about to do function'.cr;	
					db_upload_directory($token,$folder,$file);
					exit;
				}
				elseif(is_file($file)) {
					db_upload_file($token,$folder,$file);
				}
			}
			else {
				echo "$file does not exist, correct and retry".cr;
				if(debug) {echo "supplied value - $file".cr;}
			}
			exit;
			
			case 's':
			case 'space':
			db_space($token,true);
			exit;
			
			case 'x':
				db_setup();
				exit;
		default:
		
				echo 'unknown -a option '.$options['a'].cr;
				//exit;
	}
}
	else {
		
		echo $cc->convert("%RWarning%n"). ' no -a switch set'.cr;
	}
		
		$table = new Table(CONSOLE_TABLE_ALIGN_LEFT, '', 1, null, true);
		$table->addRow(array($cc->convert("%YHelp%n")));
		echo $table->getTable();
		$table = new Table(CONSOLE_TABLE_ALIGN_CENTER, CONSOLE_TABLE_BORDER_ASCII, 1, null, true);
		$table->setHeaders(array('Option', 'Use','Used with','example'));
			//$table->addRow(array($cc->convert("%Y-a%n"),'action','yes'));
			$table->addRow(array($cc->convert("%Y-ab%n"),'Timed Back up ','','webmin stub'));
			$table->addRow(array($cc->convert("%Y-al%n"),'lists files ','-f -p'));
			$table->addRow(array($cc->convert("%Y-as%n"),'Displays dropbox size statistics','',$argv[0].' -as'));
			$table->addRow(array($cc->convert("%Y-ai%n"),'Displays dropbox user info','',$argv[0].' -ai'));
			$table->addRow(array($cc->convert("%Y-au%n"),'Uploads a file ',' -f -p -u -o','dropbox.php -au -flocal -px -u x/test.img' ));
			$table->addRow(array($cc->convert("%Y-am%n"),'Creates a folder ',' -f -p'));
			$table->addRow(array($cc->convert("%Y-f%n"),'uses a dropbox folder, if blank/not set uses / '.cr.'if set to local will use the local hostname',''));
			$table->addRow(array($cc->convert("%Y-l%n"),'use local file system','-f'));
			$table->addRow(array($cc->convert("%Y-p%n"),'uses a dropbox path','-f'));
			$table->addRow(array($cc->convert("%Y-u%n"),'filename to upload to dropbox requires full path','-f -p'));
			$table->addRow(array($cc->convert("%Y-o%n"),'overwrite existing file, if blank/not set file is overwritten ','-au -u'));
			$table->addRow(array($cc->convert("%Y--debug%n"),'print debug output to terminal ','used with any other option'));
			echo $table->getTable();
		
	

function rrmdir($src) {
    $dir = opendir($src);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = $src . '/' . $file;
            if ( is_dir($full) ) {
                rrmdir($full);
            }
            else {
                unlink($full);
            }
        }
    }
    closedir($dir);
    rmdir($src);
}
function db_check_token() {
	include 'inc/settings.php';
	$postData = array(
  'grant_type' => 'refresh_token',
  'refresh_token'  => $settings['OAUTH_REFRESH_TOKEN'] 
  );
  $userpwd = $settings['OAUTH_APP_KEY' ].':'.$settings['OAUTH_APP_SECRET'];
	$ch = curl_init(API_OAUTH_TOKEN);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch,CURLOPT_USERPWD , $userpwd);
    curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
    curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
    $data = curl_exec($ch);
 
curl_close($ch);
 //print_r( json_decode($data,true));
return json_decode($data,true); // send it back
}

function db_info($data) {
	// get user data
	
	$headr[] = 'Authorization: Bearer '.$data['access_token']; 
  
	$ch = curl_init(API_ACCOUNT_INFO_URL);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_HTTPHEADER,$headr);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	$x = curl_exec($ch);
	curl_close($ch);
	return json_decode($x,true); 
}

function db_list_files($data,$path='' ) {
	//list data
	global $options;
	$cc = new Color();
	$table = new Table(CONSOLE_TABLE_ALIGN_RIGHT, CONSOLE_TABLE_BORDER_ASCII, 1, null, true);
	$table->setHeaders(array('Type', 'Name','Size','Modified'));
		if(empty($path)) {
		//echo 'path set to root'.cr;
	}
	else {
		// check
		if($path[0] <> '/') {
			$path ='/'.$path;
		}
	}
	$headr[] = 'Authorization: Bearer '.$data['access_token'];
	$headr[] =  "Content-Type: application/json";
	$postData['path'] =$path;
	$postData["include_media_info"] = false;
	$postData["include_deleted"] = false;
	$postData["include_has_explicit_shared_members"] = false ;
	$postData = json_encode($postData);
	$ch = curl_init(API_LIST_FOLDER_URL);
	curl_setopt($ch, CURLOPT_HTTPHEADER,$headr);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	$x = curl_exec($ch);
	curl_close($ch);
	$y =  json_decode($x,true); 
	
	if (empty($path) ) { 
			$path='dDropBox';
			}
		
	
if(isset($y['entries'])){	
	$total_size=0;
	foreach ($y['entries'] as $entry) {
		//echo print_r($entry,true).cr;
		if ($entry['.tag'] == 'file') {
			$path_parts = pathinfo($entry['path_display']);
			//echo 'path parts'.cr.print_r($path_parts,true).cr;
			$basename = $path_parts['basename'];
		//echo $entry['.tag'].' '.$path_parts['basename'].' '. formatBytes($entry['size'],2).cr;
		$total_size  +=$entry['size'];
		$table->addRow(array($entry['.tag'], $cc->convert("%b$basename%n"),trim(formatBytes($entry['size'],2)),date('d-m-Y  H:i:s',strtotime($entry['server_modified']))));
	}
		else {
			$path_parts = pathinfo($entry['path_display']);
			//echo 'path parts'.cr.print_r($path_parts,true).cr;
			$basename = $path_parts['basename'];
			$table->addRow(array($entry['.tag'], $cc->convert("%g$basename%n"),'N/A','N/A'));
			 //echo $entry['.tag'].' '.$path_parts['basename'].cr;
		 }
	}
	if(!isset($options['t'])){
		 
		echo cr.'Contents of '.$cc->convert("%y".substr($path,1)."%n").cr;
		if ($total_size >0 || !isset($total_size)) {
				$total_size = formatBytes($total_size,2);
				$table->addRow(array('Total','',$cc->convert("%Y$total_size%n"),''));
			}
		echo $table->getTable();
		
	}
	return $y['entries'];
}
else {
	echo 'Could not find '.$cc->convert("%y".substr($path,1)."%n").cr;
}
}


function db_space($data,$print) {
	//display space
	$headr[] = 'Authorization: Bearer '.$data['access_token'];
	$ch = curl_init(API_ACCOUNT_SPACE_URL);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = curl_exec($ch);
	curl_close($ch);
	$x =json_decode($response,true);
	$free = formatBytes($x['allocation']['allocated'] - $x['used'],2);
	$used = formatBytes($x['used'],2);
	$quota = formatBytes($x['allocation']['allocated']);
	if ($print) {
	$cc = new Color();
	$table = new Table(CONSOLE_TABLE_ALIGN_RIGHT, '', 1, null, true);
	$table->addRow(array('Quota', $cc->convert("%r$quota%n")));
	$table->addRow(array('Used', $cc->convert("%r$used%n")));
	$table->addRow(array('Free', $cc->convert("%r$free%n")));
	echo 'DropBox Statistics'.cr.$table->getTable();
	}
	else {
		$return['free'] = $free;
		$return['quota'] = $quota;
		$return['used'] = $used;
		return $return;
	}
}

function db_delete($data,$folder) {
	// delete stuff
	global $options;

	if(isset($options['t'])) {
	 echo "Checking for old backups in folder $folder".cr;	
		if(empty($folder)) {
			echo 'no folder set'.cr;
		}
		
		$fl = db_list_files($data,$folder);
		//print_r($fl);
		$now = time();
		$today = strtotime('00:00:00', $now); //set to midnight
		$remove = strtotime('-'.SETTINGS['FILE_RETAIN'].' day', $today-82800);
		$dtotal =0;
		foreach ($fl as $file) {
			if ($file['.tag'] == 'file') {continue;}
				$check_date = isDate($file['name']);
				$dsize=0;
				
				if($check_date >0){
					
					if ($remove > $check_date) {
						$rf = db_list_files($data,$folder.'/'.$file['name']);
						
						foreach ($rf as $tmp) {
							if ($tmp['.tag'] == 'file' ) {
								$dsize = $dsize+$tmp['size'];
								}
							}
							$erase =  '/'.$folder.'/'.$file['name'];
							echo 'deleting '.$file['name'].' ('.formatBytes($dsize,2).')'.cr;
							unset($headr);
							$postData = array();
							$headr[] = 'Authorization: Bearer '.$data['access_token'];
							$headr[] =  "Content-Type: application/json";
							$postData['path'] = $erase;
							$postData = json_encode($postData);
							$ch = curl_init(API_DELETE_URL);
							curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
							curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
							curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
							curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
							$response = curl_exec($ch);
							//echo $response.cr;
							curl_close($ch);
							$dtotal =$dtotal+$dsize;
						
						}
					else {
						echo $file['name'].' is still a current backup folder'.cr;
					}
					
				}
		}
		echo 'Total Deleted '.formatBytes($dtotal,2).cr;
		return; 
		
	}
	
	//echo "folder = $folder".cr;
	$erase ='';
	//print_r($options);
	if (!empty($folder)) {
			$erase = '/'.$folder;
			//echo "erase set to $erase".cr;
		}
	if (!isset($options['p'])){
		echo 'Errror no path set '.cr;
		exit;
	}
	else {
		//echo $options['p'].cr;
		//echo $options['p'][0].cr;
		if ($options['p'][0] <> '/')
	{
		//echo 'hit o'.cr;
		$erase .= '/'.$options['p'];
	}
	else {
				$erase .= $options['p'];
				//echo "erase now set to $erase".cr;
	}
	}
	if(isset($options['u']) && $erase <> '/') {
		$erase .='/'.$options['u'];
	}
	elseif (isset($options['u']))  {
		$erase .= $options['u'];
	}
	//echo "Deleting $erase".cr;	
	$headr[] = 'Authorization: Bearer '.$data['access_token'];
	$headr[] =  "Content-Type: application/json";
	$postData['path'] = $erase;
	//echo 'this is the post data '.print_r($postData).cr;
	$postData = json_encode($postData);
	$ch = curl_init(API_DELETE_URL);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	$response = curl_exec($ch);
	curl_close($ch);
	$tmp = json_decode($response,true);
	if (isset($tmp['error_summary'])){
		if (strpos($tmp['error_summary'],'not_found')){
			echo "Error $erase was not found".cr;
		}
	}
	else {
		echo "Sucess Deleted $erase".cr;
	} 
}



function split_file($source, $targetpath='./logs/'){
	//echo "target_path= $targetpath".cr;
	//echo "\033[1K";
	//die($source);
	if (!file_exists($targetpath)) {
    mkdir($targetpath, 0777, true);
}
else {
	// clean up if required function crashed last time around ?
	$tmp_files = array_values(array_diff(scandir($targetpath), array('..', '.')));
	if( count($tmp_files)){
		if (debug == true) { 
			echo "Cleaning up tempory folder $targetpath".cr;
		}
			foreach ($tmp_files as $erase) {
				unlink($targetpath.'/'.$erase);
				if (debug == true) { 
					echo "Removing $erase".cr;
				}
			}
		
	}
}
$cd = getcwd();
if (!is_file($source)) {
	echo "can not find $source !".cr;
}
		$targetpath .= '/';
    	//echo "\033[K";
		$path_parts = pathinfo($source);
	
	if (debug == true) { 
		echo "current working directory $cd".cr;
		echo "chunk target path $targetpath".cr;
		echo 'File break down '.cr;
		foreach ($path_parts as $k=> $v) {
			echo $k.'=>'.$v.cr;
		}
		echo "writing chunks from $source ".cr;
		
	}
  
	
   if (SETTINGS['CHUNK_SIZE'] >= 150) {
	   echo 'can not split '.$path_parts['basename'].' !'.cr;
	   echo 'chunk size is too large, correct your settings'.cr;
	   die('closing down'.cr);
   }
    exec('split -b '.SETTINGS['CHUNK_SIZE'].'M '.$source.' "'.$targetpath.$path_parts['basename'].'.part"',$a,$r);
    //print_r(scandir($targetpath));
    //echo "source = $source target path = $targetpath current directory =$cd".cr;
    //die();
    
}
function db_mkdir($data,$folder) {
	global $options;
	print_r($options);
	echo "supplied folder : $folder".cr;
	if (!empty($folder)) {
			$path = ''.$folder.'/';
			echo "path is now set to $path coz folder was not empty".cr;
			
		}
	if(!isset($options['p'])) {
		echo 'Error no directory supplied'.cr;
		return;
	}
	else {
		if ($options['p'][0] <> '/' && empty($folder))
		{
		//echo 'hit no folder'.cr;
			$path = '/'.$options['p'];
			echo "no folder so path is now $path".cr;
		}
		else {
			echo "path was $path".cr;
			$path .= $options['p'];
			echo "path is now $path".cr;
		}
	}
	//die ($path.cr);
	$headr[] = 'Authorization: Bearer '.$data['access_token'];
	$headr[] = 'Content-Type: application/json';
	$postData['path'] = $path;
	$postData = json_encode($postData);
	$ch = curl_init(API_MKDIR_URL);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
	curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	$response = curl_exec($ch);
	curl_close($ch);
	$tmp = json_decode($response,true);
	if(isset($tmp['error_summary'])) {
		echo "Error $path already exists".cr;
		print_r($tmp);
	}
	else {
		echo "Success $path created".cr;
	}
}
function isDate($value) 
{
	echo "[ $value ]".cr;
	//echo strtotime($value." 18:11").cr;
	$test =date_parse_from_format(SETTINGS['DATE_FORMAT'], $value);
	//print_r($test);
	if ($test['error_count'] == 0) {
		//$ct = strtotime($test['day'].'-'.$test['month'].'-'.$test['year']).cr;
		//$midnight = strtotime('00:00:00', $ct);
		$ct = new DateTime($test['year'].'-'.$test['month'].'-'.$test['day']);
		$ct->setTime(0,0,0);
		//echo 'return value '.$ct->getTimestamp().cr;
		return $ct->getTimestamp();

	}
    else {
		return false;
	}
}

function dirToArray($dir) {
  if (debug) {
	  echo 'dirToArray: start';
  }
  $fileSPLObjects =  new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir),
                RecursiveIteratorIterator::CHILD_FIRST
            );
try {
    foreach( $fileSPLObjects as $fullFileName => $fileSPLObject ) {
       
		if(is_file($fullFileName)){
        $result[] = $fullFileName;
        if (debug) {
			echo "found $fullFileName".cr;
			$tmp[] = explode("/",substr($fullFileName,1));
			$key =array_key_last($tmp);
			array_unshift($tmp[$key],$fullFileName);
			
		}
	}
    }
}
catch (UnexpectedValueException $e) {
    printf("Directory [%s] contained a directory we can not recurse into", $directory);
}


foreach ($result as $k => $v) {
	if (is_dir($v))  {
		unset( $result[$k]);
	}
}
$result= array_values($result);
//echo "temp = ".print_r($tmp,true).cr;
//echo  "result = ".print_r($result,true).cr;
//die();
  
   return $tmp;
}

function db_upload_directory($data,$folder,$directory) {
	/* upload complete folder 
	 * $data is the access token
	 * $folder is the dropbox folder
	 * $directory is the local directory
	 */
	  global $options;
	  //if (options['a']!=='t') {
	 $cc = new Color();
	 $folder_len = strlen($folder)+1;
	 $total_size = 0;
	 $start_time = time(); // get current timestamp 
	 $content = dirToArray($directory); // get file list
	 asort($content);
	 $total_files = count($content);
	 $file_count = 0;
	 echo "$total_files  file(s) to upload".cr;
	
	 if(TERM){echo  "\033[?25l"; }// turn cursor off
	 foreach ($content as $file){
		 $data = db_check_token();
		 $filen=$file[0];
		 if (debug){echo "db_upload_directory: \$file = $filen".cr;}
			 if (array_search(options['p'],$file)) {
				 $u='';
				 $key = array_search(options['p'],$file);
				 echo "starting at - $key".cr;
				 echo "split path =".cr.print_r($file,true).cr;
				for($i = $key; $i <=count($file)-1; $i++){
					//echo "The index is $i";
					$u .= '/'.$file[$i];
				}
				echo cr."\$upload_path corrected to $u".cr;
			 }
			 
		 $file_count++;
		 $x = strpos($filen,$folder);
		 if ($x >0 ) {
			 //echo'hit path chop'.cr;
			 $path = substr($filen,$x+$folder_len);
			
		 }
		 
		 else{
			if ($filen[0] == '/') {
			 //echo 'chop off leading slash'.cr;
			     $path = substr($filen,1);
			 }
			 
			 else {
				// echo 'std path'.cr;
				 $path = $filen;
			 }
		}
	
		//die($path);
		if (is_file($filen)){  
			
		$size = filesize($filen);
		if (empty($u)) {
			$short_file = basename($filen);
			$db_path = pathinfo($path,PATHINFO_DIRNAME);
		}
		else {
			echo "hit $u".cr;
			$short_file = basename($u);
			$db_path = pathinfo($u,PATHINFO_DIRNAME);
			echo "db_path = $db_path".cr; 
		}
		
		if(TERM){
			$short_file = $cc->convert("%G$short_file%n");
			$cdb_path = $cc->convert("%Y/$folder$db_path%n");	
			echo "\033[K";
			}
		else {
			$cdb_path = "/$folder$db_path";
		}	
	   	    	    
		echo "$file_count/$total_files Uploading $short_file to $cdb_path (".trim(formatBytes($size)).")".cr;
		//die();
		echo "file = $filen".cr;
		//$upload_path = "/$folder/$path";
		//157,286,400
		if ($size >= 157286400) {
			
				//die ("file set to $file".cr);
				db_upload_large_file($filen,'.tmp/dbtmp');
			
		}
		else {
			// regular upload
			unset($headr);
			$upload_path = "/$folder/$db_path/". basename($file[0]);
			echo "upload_path $upload_path".cr;
			if (!empty($u) >0) {
				echo cr."new upload path /$folder$u".cr;
				$upload_path= "/$folder$u";
			}
			//$upload_path= config_upload_path($upload_path).'/'.basename($file);
			//echo "$upload_path";
			if(TERM){echo "\033[K";}
			$fp = fopen($filen, 'rb');
			$headr[] = 'Authorization: Bearer '.$data['access_token'];
			$headr[] ='Content-Type: application/octet-stream';
			$headr[] = 'Dropbox-API-Arg: {"path":"'.$upload_path.'", "mode":"overwrite"}';
			$ch = curl_init(API_UPLOAD_URL);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
			curl_setopt($ch, CURLOPT_PUT, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_INFILE, $fp);
			curl_setopt($ch, CURLOPT_INFILESIZE, $size);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			if (debug) {
				print_r($headr);
				if(!empty($response)) {
					print_r($response);
				}
			}
			fclose($fp); 
			sleep(1); 
			$u='';
			$upload_path = "$folder/$db_path/". basename($file[0]);
			
		}
		
		$total_size = $total_size+$size;
	}
		
	 }
	 $ts = formatBytes($total_size,2);
	 if (TERM) {
		  echo "\033[?25h";
	  }
		  echo cr."Total Upload $ts".cr;
	
	if (isset($options['t'])) {
          //delete folders older then retention time
          db_delete($data,$folder); // delete old dropbox folders
          rrmdir($directory); // remove local copy of new transfer as we now have it on dropbox
        }

}
function db_upload_file($data,$folder,$file) {
	// upload single file
	$folder_len = strlen($folder)+1;
	if (debug) {
		echo "folder = $folder -file = $file".cr;
	}
	$x = strpos($file,$folder);
	if ($x >0) {
			 $path = substr($file,$x+$folder_len);
		 }
		 else{
			$path = $file;
		}
		$size = filesize($file);
		$short_file = basename($file);
		$ts = formatBytes($size,2);
		echo "Upload $short_file to dropbox $folder/$path ($ts)".cr;
		if ($path[0] !== '/') {
			$upload_path ="/$folder$path";
		}
		else{
		$upload_path = "$folder/$path";
	}
		if ($size >= 157286400) {
			db_upload_large_file($file,'/tmp/dbtmp');
		}
		else {
			// regular upload
			unset($headr);
			if(debug){
				echo "old upload path $upload_path".cr;
			}
			$upload_path= config_upload_path($file);
			if(debug) {
				echo "new upload path $upload_path".cr;
			}
			$headr[] = 'Authorization: Bearer '.$data['access_token'];
			$headr[] ='Content-Type: application/octet-stream';
			$headr[] = 'Dropbox-API-Arg: {"path":"'.$upload_path.'", "mode":"overwrite"}';
			$fp = fopen($path, 'rb');
			$size = filesize($file);
			$ch = curl_init(API_UPLOAD_URL);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
			curl_setopt($ch, CURLOPT_PUT, true);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
			curl_setopt($ch, CURLOPT_INFILE, $fp);
			curl_setopt($ch, CURLOPT_INFILESIZE, $size);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$response = curl_exec($ch);
			$response = json_decode($response,true);
			if (isset($response['name'])) {
				echo "$file uploaded to Dropbox".cr;
			}
			curl_close($ch);
			fclose($fp);

			
		}
}

function db_download($data,$db_file,$local_file) {
	/* download
	 * $data = token
	 * $db_file = remote file
	 * $local_file = where to write to
	*/
	
}	
function db_setup() {
	// setup
	$header = "   DropBox Settings version 2.0";
	$cc= new Color();
	$app['version'] = '2.0';
	echo "This is the first time you have ran this script, please follow these instructions:".cr;
	//echo "\t(note: Dropropbox will change their API from 30.9.2021 this version is ready for use )".cr;
	echo "\t1) Open the following URL in your Browser, and log in using your DropBox account:  ".APP_CREATE_URL.cr;
	echo "\t2) Click on \"Create App\", then select \"Choose an API: Scoped Access\"".cr;
	echo "\t3) \"Choose the type of access you need: App folder\"".cr;
    echo "\t4) Enter the \"App Name\" that you prefer (e.g. MyUploader), this must be unique".cr;
    echo "\t5) click on the \"Create App\" button.".cr;
    echo "\t6) the new configuration is opened, switch to tab \"permissions\" and check \"files.metadata.read/write\" and \"files.content.read/write\"".cr;
    echo "\t7) click on the \"Submit\" button.".cr;
    echo "\t8) click tab \"settings\" and enter the following information:".cr;
    questions:
	$app['OAUTH_APP_KEY' ] = trim(ask_question("        App Key ",'','',true));	 
    $app['OAUTH_APP_SECRET']  = trim(ask_question("        App Secret ",'','',true));	
	 //echo print_r($app,true).cr;
	 //https://www.dropbox.com/oauth2/authorize?client_id=orweskt13n9fe7r&token_access_type=offline&response_type=code
	 $url=API_OAUTH_AUTHORIZE.'?client_id='.$app['OAUTH_APP_KEY']."&token_access_type=offline&response_type=code";
	 echo "paste the following url into your browser\n\n $url \n\nAnswer the questions and ";
	 $app['OAUTH_REFRESH_TOKEN'] = trim(ask_question('paste the response here ','','',true));
	 db_write_settings($app,'ts.php',$header,'settings');
}

function db_write_settings ($ini_array,$file,$header,$name)
	{
		/* write settings 
		 * $settings  Type array  - data to write 
		 * $file Type string - file to write to
		 * $header Type string - file identifier
		 * $name Type string - array key name
		 * if $name is blank the key wil be ini
		 * example writeini ($settings,"main.ini","main ini file version v1","ini"); 
		 */  
		 
		if(!isset($name)) {$name ="ini";} 
		$writevar ="<?php
/*********************************\ 
". $header."
\*********************************/\n";
	foreach ($ini_array as $key => $val) {
      $writevar .=  '$'.$name."['" . $key . "'] = ".'"'.trim($val).'";'.cr;
    }
    	echo "file is $file".cr;
		print_r($writevar);
    	file_put_contents ($file , $writevar,LOCK_EX);
	    clearstatcache();
}


/*
 * 
 * name: db_upload_large
 * 
 * @return true or false
 * 
 */
 function db_upload_large_file($file,$targetpath) {
	//upload chunks
	//global $debug;
	
	$cd = getcwd();
	if($file[0] !== '/') {
		if (debug == true) {
			echo "\tdb_upload_large_file: $file is not a full path assuming relative to $cd".cr;
		}
		$abs_file = "$cd/$file";
	}
	else {
		$abs_file = $file;
	}
	if(!is_file($abs_file)) {
		echo "\tCould Not Find $abs_file".cr;
		return false;
	}
	
	if (!file_exists($targetpath)) {
		mkdir($targetpath, 0777, true);
	}
	else {
		// clean up if required function crashed last time around ?
		$tmp_files = array_values(array_diff(scandir($targetpath), array('..', '.')));
		if( count($tmp_files)){
			if (debug == true) { 
				echo "\tdb_upload_large_file: Cleaning up tempory folder $targetpath".cr;
			}
				foreach ($tmp_files as $erase) {
					unlink($targetpath.'/'.$erase);
					if (debug == true) { 
						echo  "\tdb_upload_large_file: Removing $erase".cr;
					}
				}
		}
	
}
		echo "\tPreparing large file, $abs_file for upload".cr; 
		$targetpath .= '/';
		$abs_path_parts = pathinfo($abs_file);
		$size = filesize($abs_file);
		$hsize = formatBytes($size,2);
		if (debug == true) { 
			echo  "\tdb_upload_large_file: current working directory $cd".cr;
			echo  "\tdb_upload_large_file: chunk target path $targetpath".cr;
			echo "\tdb_upload_large_file: File breakdown ".cr;
			foreach ($abs_path_parts as $k=> $v) {
				echo "\t".$k.' => '.$v.cr;
			}
			echo "\twriting chunks from $abs_file".cr;
		}
	   if (SETTINGS['CHUNK_SIZE'] >= 150) {
			echo 'can not split '.$abs_path_parts['basename'].' !'.cr;
			echo 'chunk size is too large, correct your settings'.cr;
			die('closing down'.cr);
		}
		 exec('split -b '.SETTINGS['CHUNK_SIZE'].'M '.$file.' "'.$targetpath.$abs_path_parts['basename'].'.part"',$response,$ret_val);  //chunk file
		 $chunks = array_values(array_diff(scandir($targetpath), array('..', '.')));
		 if (debug == true){
			 $chunk_total = count($chunks);
			echo "\tdb_upload_large_file: $chunk_total chunks to upload".cr;
		}
		$offset = 0;
		$chunk_num= 0;
		$chunk_total = count($chunks);
		$data= db_check_token(); 
		unset($headr);
		$headr[] = 'Authorization: Bearer '.$data['access_token'];
		$headr[] ='Content-Type: application/octet-stream';
		$headr[] = 'Dropbox-API-Arg: {"close": false}';
		$ch = curl_init(API_CHUNKED_UPLOAD_START_URL);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		$ids = json_decode(curl_exec($ch),true);
		curl_close($ch);
		$session_id = $ids['session_id'];
		foreach ($chunks as  $chunk) {
				$data= db_check_token(); 
				unset ($headr);
				$chunk_num++;
				$fp = fopen("$targetpath/$chunk", 'rb');
				$csize = filesize("$targetpath/$chunk");
				$dsize = formatBytes($csize,2);
				$chunk_base = pathinfo($chunk,PATHINFO_FILENAME);
				echo "\tUploading $chunk_base ";
				if (debug == true) {
					echo "Upload Size $dsize ";
				}
				echo "($chunk_num/$chunk_total)";
				$headr[] = 'Authorization: Bearer '.$data['access_token'];
				$headr[] ='Content-Type: application/octet-stream';
				$headr[] = "Dropbox-API-Arg: {\"cursor\": {\"session_id\": \"$session_id\",\"offset\": $offset},\"close\": false}";
				$ch = curl_init(API_CHUNKED_UPLOAD_APPEND_URL);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
				curl_setopt($ch, CURLOPT_PUT, true);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_INFILE, $fp);
				curl_setopt($ch, CURLOPT_INFILESIZE, $csize);
				$response = json_decode(curl_exec($ch),true);
				if (!empty($response)  ) {
					echo "\tresponse ".print_r($response,true).cr;
				}
				fclose($fp);
				curl_close($ch);
				$offset = $offset+$csize;
				$hoffset = formatBytes($offset,2);
				echo " $hoffset of $hsize uploaded".cr;
				if(debug == true) {
					
					echo "\t$chunk Headers".cr;
					foreach ($headr as $header){
					echo "\t$header".cr;
					}
				}
				sleep(1);
			}
		unset ($headr);
				
				if (isset(options['u'])) {
					if (debug) {
						echo "sending config_upload_path : $file".cr;
					}
					$upload_path= config_upload_path($file).'/'.$abs_path_parts['basename'];
					}
				$headr[] = 'Authorization: Bearer '.$data['access_token'];
				$headr[] ='Content-Type: application/octet-stream';
				$headr[] = "Dropbox-API-Arg: {\"cursor\": {\"session_id\": \"$session_id\",\"offset\": $offset},\"commit\": {\"path\": \"$upload_path\",\"mode\": \"overwrite\",\"autorename\": true,\"mute\": false}}";
			
				$ch = curl_init(API_CHUNKED_UPLOAD_FINISH_URL);
				curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
				curl_setopt($ch, CURLOPT_PUT, true);
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$response = json_decode(curl_exec($ch),true);
				if(debug == true){
					echo "\tfinalising $upload_path".cr;
					echo "\toffset is set to $offset overall file set to $size";
					if ($offset == $size) {
						echo ' File size matches'.cr;
					}
					else {
						echo ' File has not uploaded correctly';
					}
					echo "\tHeaders Sent".cr;
					foreach ($headr as $header){
					echo "\t$header".cr;
					}
					echo "\tFinal Response ".cr ; //do something with this
						foreach ($response as $k => $v) {
							echo "\t".$k.' => '.$v.cr;
						}
					// do we hold on to the split file ? 	 
				}
				rrmdir($targetpath); // clean up		
}

function config_upload_path($file='') {
	global $options;
	$folder= '';
	//$input_file = 
	if (debug) {
		echo "entering config_upload_path".cr;
		print_r(options);
		//print_r($input);
	}
	if (options['a']!=='t') {
	if (isset($options['f'])) {
		switch ($options['f']) {
			case 'local':
				$folder = "/".gethostname(); // set folder to computer host name
				$tfolder = $folder;
				break;
			default:	
				echo 'hit default'.cr;
				if ($options['f'][0] == '/') {
					$folder = $options['f'];
				} 
				else {
					$folder = "/".$options['f'];
				}
		}
	}
	if (isset($options['p'])) {
		$folder .= "/".$options['p'];
	}
	$file_parts = pathinfo($options['u']);
	$dir_parts = pathinfo($file_parts['dirname']);
	if (debug) {
	echo "config_upload_path: real path ".realpath(dirname($options['u'])).cr;
	echo "config_upload_path: dir name ".dirname($options['u']).cr;
	echo "config_upload_path: file ".print_r($file_parts,true).cr;
	echo "config_upload_path: directory ".print_r($dir_parts,true).cr;
	}
		
		if ($dir_parts['basename'] !=='.'){ 
			//echo "if $folder".cr;
			$folder .="/".$dir_parts['basename']."/".$file_parts['basename'];
			//echo "new if $folder".cr;
		}
		else {
			$folder .= "/".$file_parts['basename'];
			if (debug){
					echo "new else $folder".cr;
			}
		} 
	//echo $folder.cr;
	//die();
}
	if (options['a'] =='t') {
		// timed folder
		echo "timed to $folder".cr;
		echo " file =  $file".cr;
		echo 'Timed Folder'.cr;
		if (strpos($file,$folder) == true) {
			return $file;
		}
		else {
			return "$folder/$file";
		}
	}
	if (debug) {
		echo "config_upload_path: input value $file".cr;
		echo "config_upload_path: return value $folder".cr;
	}
	return $folder;
}

function db_upload () {
	//wrapper for uploader
	// choose betwwen a file <150mb or bigger than
}
function db_timed_upload() {
	// timed upload
	//global $options;
	if (debug) {
		echo 'started db_timed_upload'.cr;
		echo 'format is set to '.SETTINGS['DATE_FORMAT'].cr;
		print_r(SETTINGS);
	}
	
	$token = db_check_token();
	if (isset(options['f'])) {
		if(options['f'] == 'local') {
			$folder = '/'.gethostname(); // set folder to computer host name
		}
		else {
			$folder = options['f'];
		}
	}
	else {
		$folder ='/';
	}
	if (isset(options['p'])){
		$folder .='/'.options['p'];
	}
	$file = date(SETTINGS['DATE_FORMAT']);
	//echo "file set to $file".cr;
				 // set up file to be an incremental backup
				if (is_dir($file)) {
					if (debug) {
						echo 'object is a directory'.cr;
						echo "\$folder = $folder \$file = $file".cr;	
						
					}
					//$file = "$folder/$file";
					db_upload_directory($token,$folder,$file);
					exit;
				}
				elseif(is_file($file)) {
					if (debug) {
						echo 'object is a file'.cr;
					}
					db_upload_file($token,$folder,$file);
				}
				else {
					//echo "\$folder = $folder \$file = $file".cr;	
					die( "Can not find $file".cr);
				}
}