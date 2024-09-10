#!/usr/bin/php -d memory_limit=2048M
<?php
/*
 * dropbox.php
 * 
 * Copyright 2024 Jim Richardson <jim@phporyx.co.uk>
 * 
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston,
 * MA 02110-1301, USA.
 * 
 * 
 */

include 'inc/master.inc.php';
//print_r (get_defined_vars());
//print_r($all);
//exit;
//print_r($argv);
$build = filemtime($argv[0]);
$runfile = basename($argv[0]);
$db_version = "2.0.1";
$error = false;
define("token",check_token());
if(empty($backup_path)) {$backup_path = gethostname(); } // use this if every thing else is empty
if(!empty($folder)) {$backup_path .="/$folder";}
if($version){ echo "$runfile $db_version - $build\n";}
if($help){
	help: 
	$table = new Table(CONSOLE_TABLE_ALIGN_LEFT, borders, 1, null, true);
	$table->setHeaders(array('Short', 'Long','Type','Remarks'));
	//$table->setAlign(3, CONSOLE_TABLE_ALIGN_CENTER);
	$table->setAlign(2, CONSOLE_TABLE_ALIGN_RIGHT);
	//print_r($all);
	foreach ($all as $tmp){
		//print_r($tmp);
		if(!isset($tmp['char'])){continue;}
		$table->addRow(array("-{$tmp['char']}","--{$tmp['word']}" ,$tmp['type'],$tmp['help']));
	}
	echo $table->getTable();
	if($error){exit;}
}

if($upload) {
	//echo "upload set\n";
	if(empty($path)) {
		echo "no path supplied\n";
		$error = true;
		goto help;
	}
	if(empty($backup_path)) {
		echo "no dropbox path supplied\n";
		$error = true;
		goto help;
	}
		
	if (timed) { 
		$path .= "/".date(settings['DATE_FORMAT'],time());
		if(debug){log_to(LOG, "detected today from date $path");}
	} 
	echo "Uploading to $backup_path from $path\n"; 
	if(is_dir($path)) { 
		if(debug){echo "we are going to upload from $path to dropbox $backup_path\n";}
		upload_directory($backup_path,$path);
	}
	elseif(is_file($path)) {
		if (debug){echo  "uploading file, $path to $backup_path\n";}
		upload_file($backup_path,$path);
	}
	else {
		echo "file or directory $path not found Terminating\n";
		exit;
	} 
	if(debug){echo "upload selected ($backup_path)\n";}
	if(timed) { 
		//echo "timer set\n";
		file_delete($backup_path,false);
	}
	exit;
}
if($erase) {file_delete($backup_path,$all);}
if($get) { download($get);}
if ($list) {
	if(!empty($path)){$backup_path .= "/$path";}
	list_files("$backup_path",true);
	if (isset($info)) {info(true);}
}
if($info) {info(true);}
if($space) {space(true);}
exit;	

function upload_directory($folder,$directory) {
	/* upload complete folder 
	 * $data is the access token
	 * $folder is the dropbox folder
	 * $directory is the local directory
	 */
	$total_size = 0;
	 $start_time = time(); // get current timestamp 
	 $content = dirToArray($directory); // get file list
	 asort($content);
	 $total_files = count($content);
	 $file_count = 0;
	 echo "$total_files  file(s) to upload".cr;
	//if(TERM){echo  "\033[?25l"; }// turn cursor off
	 foreach ($content as $file){
		$file_name=$file[0];
		$file_size = filesize($file_name);
		upload_file($folder,$file_name);
	 }
	
}	
function upload_file($folder,$file) {
	if (debug) {	log_to(LOG, "folder = $folder -file = $file");}
	$file_info = correct_file($file,$folder);
	//print_r($file_info);
	$file= $file_info['file'];
	$short_file = basename($file);
	$upload_path = $file_info['upload_path'];
	//die("{$file_info['upload_path']}\n");
	if(debug){echo "upload_path now = $upload_path\n";}
	if ($file_info['size'] >= 157286400) {upload_large_file($file,'/tmp/dbtmp',$upload_path);}
	else {
		// regular upload
		unset($headr);
		if(debug){log_to(LOG, "old upload path ".$upload_path);}
		$headr[] = 'Authorization: Bearer '.token['access_token'];
		$headr[] ='Content-Type: application/octet-stream';
		$headr[] = 'Dropbox-API-Arg: {"path":"'.$upload_path.'", "mode":"overwrite"}';
		$fp = fopen("/$file", 'rb');
		$size = filesize("/$file");
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
			//$bytes = trim(
			if(TERM) {
				$short_file = cc->convert("%y$short_file%n"); 
				$file_info['upload_path'] = cc->convert("%g{$file_info['upload_path']}%n");
			}
			echo "$short_file uploaded to {$file_info['upload_path']} ({$file_info['bytes']})\n";
		}
		else{print_r($response);}
		curl_close($ch);
		fclose($fp);
	}
}
function check_token() {
	$postData = array(
  'grant_type' => 'refresh_token',
  'refresh_token'  => settings['OAUTH_REFRESH_TOKEN'] 
  );
	$userpwd = settings['OAUTH_APP_KEY' ].':'.settings['OAUTH_APP_SECRET'];
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

function dirToArray($dir) {
  if (debug) {log_to(LOG, 'dirToArray: start'); }
  $fileSPLObjects =  new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir),RecursiveIteratorIterator::CHILD_FIRST);
	try {
		foreach( $fileSPLObjects as $fullFileName => $fileSPLObject ) {
			if(is_file($fullFileName)){
				$result[] = $fullFileName;
				$tmp[] = explode("/",substr($fullFileName,1));
				$key =array_key_last($tmp);
				array_unshift($tmp[$key],$fullFileName);
			}
		}
	}
	catch (UnexpectedValueException $e) {printf("Directory [%s] contained a directory we can not recurse into", $directory);}
	foreach ($result as $k => $v) {if (is_dir($v))  {unset( $result[$k]);}}
	$result= array_values($result);
	return $tmp;
}

 function upload_large_file($file,$targetpath,$write_to) {
	//upload chunks
	if(debug){echo "entering large file with $file going to $targetpath and writing to $write_to\n";}
	$cd = getcwd();
	if (!file_exists($targetpath)) {mkdir($targetpath, 0777, true);}
	else {clean_up($targetpath);}
	//echo "\tPreparing large file, $file for upload\n"; 
	$targetpath .= '/';
	$abs_path_parts = pathinfo($file);
	$size = filesize($file);
	$hsize = formatBytes($size,2);
	if (debug == true) { 
		echo  "\tdb_upload_large_file: current working directory $cd\n";
		echo  "\tdb_upload_large_file: chunk target path $targetpath\n";
		echo "\tdb_upload_large_file: File breakdown \n";
		foreach ($abs_path_parts as $k=> $v) {echo "\t".$k.' => '.$v.cr;}
		echo "\twriting chunks from $file\n";
	}
	if (settings['CHUNK_SIZE'] >= 150) {
		echo "can not split {$abs_path_parts['basename']}!\n";
		echo "chunk size is too large, correct your settings\n";
		die("closing down\n");
	}
	 exec('split -b '.settings['CHUNK_SIZE'].'M '.$file.' "'.$targetpath.$abs_path_parts['basename'].'.part"',$response,$ret_val);  //chunk file
	 $chunks = array_values(array_diff(scandir($targetpath), array('..', '.')));
	 if (debug == true){
		 $chunk_total = count($chunks);
		echo "\tdb_upload_large_file: $chunk_total chunks to upload".cr;
		//print_r($chunks);
		//die();
	}
	$offset = 0;
	$chunk_num= 0;
	$chunk_total = count($chunks);
	unset($headr);
	$headr[] = 'Authorization: Bearer '.token['access_token'];
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
		unset ($headr);
		$chunk_num++;
		$fp = fopen("$targetpath/$chunk", 'rb');
		$csize = filesize("$targetpath/$chunk");
		$dsize = formatBytes($csize,2);
		$chunk_base = pathinfo($chunk,PATHINFO_FILENAME);
		echo "Uploading $chunk_base ";
		if (debug == true) {echo "Upload Size $dsize ";}
		echo "($chunk_num/$chunk_total)";
		$headr[] = 'Authorization: Bearer '.token['access_token'];
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
		if (!empty($response)) {echo "\tresponse ".print_r($response,true).cr;}
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
	$headr[] = 'Authorization: Bearer '.token['access_token'];
	$headr[] ='Content-Type: application/octet-stream';
	$headr[] = "Dropbox-API-Arg: {\"cursor\": {\"session_id\": \"$session_id\",\"offset\": $offset},\"commit\": {\"path\": \"$write_to\",\"mode\": \"overwrite\",\"autorename\": true,\"mute\": false}}";
	$ch = curl_init(API_CHUNKED_UPLOAD_FINISH_URL);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
	curl_setopt($ch, CURLOPT_PUT, true);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$response = json_decode(curl_exec($ch),true);
	if(debug == true){
		echo "\tfinalising $write_to".cr;
		echo "\toffset is set to $offset overall file set to $size";
		if ($offset == $size) {echo ' File size matches'.cr;}
		else {echo ' File has not uploaded correctly';}
	}
	rrmdir($targetpath); // clean up
	echo "$chunk_base succesfully uploaded\n" ; //do something with this		
}

function rrmdir($src) {
    $dir = opendir($src);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = "$src /$file";
            if ( is_dir($full) ) {rrmdir($full);}
            else {unlink($full);}
        }
    }
    closedir($dir);
    rmdir($src);
}

function clean_up($target){
	// clean up the temp directory
	$tmp_files = array_values(array_diff(scandir($target), array('..', '.')));
	if( count($tmp_files)){
		if (debug == true) { echo "\tdb_upload_large_file: Cleaning up tempory folder $target".cr;	}
		foreach ($tmp_files as $erase) {
			unlink($target.'/'.$erase);
			if (debug == true) { echo  "\tdb_upload_large_file: Removing $erase".cr;}
		}
	}
}

function isdate($value) {
	//die("checking this date $value/n");
	if(strtotime($value)){
		$new = strtotime("$value midnight"); 
		if(debug){log_to(LOG, "$new is a date that we want");}
	}
	$test =date_parse_from_format(settings['DATE_FORMAT'], trim($value));
	//log_to(LOG,print_r($test,true));
	$ct = new DateTime($test['year'].'-'.$test['month'].'-'.$test['day']);
	$ct->setTime(0,0,0);
	return $ct->getTimestamp();
	
    return false;
}
function file_delete($folder,$options) {
	// delete stuff
	$error = "Error";
	if (TERM){$error = cc->convert("%R$error%n");}
	$timed = false;
	if($folder[0] <> "/") { $folder="/$folder";}
	if(timed){$timed = true;}
	//echo "folder is $folder\n";
	//exit;
	if($timed) {
		$now = time();
		$today = strtotime('00:00:00', $now); //set to midnight
		$today_d = date("d-m-y H:i:s",$today);
		log_to(LOG,"Today = $today and this is $today_d"); 
		if(retain) {$remove = strtotime('-'.retain.' day', $today-82800);}
		else {$remove = strtotime('-'.settings['FILE_RETAIN'].' day', $today-82800);}
		$remove_date = date("d-m-y",$remove);
		echo "Checking for folders older than $remove_date in folder $folder\n";
		if(empty($folder)) {echo "no folder set\n";}
		$fl = list_files($folder,false);
		//print_r($fl);
		$dtotal =0;
		foreach ($fl as $file) {
			$check_date=0;
			if ($file['.tag'] == 'file') {continue;}
			$today_n = strtotime($file['name']);
			if(!$today_n){continue;}
			$check_date = isdate($file['name']);
			$dsize=0;
			if($check_date >0){
				//echo "this one has to go\n";
				if ($remove > $check_date) {
					//echo "this line $folder/{$file['name']}\n";
					$rf = list_files("$folder/{$file['name']}",false);
					//print_r($rf);
					foreach ($rf as $tmp) {
						if ($tmp['.tag'] == 'file' ) {
							$dsize = $dsize+$tmp['size'];
						}
					}
					$erase =  "$folder/{$file['name']}";
					echo "deleting $erase (".formatBytes($dsize,2).") from Dropbox\n";
					unset($headr);
					$postData = array();
					$headr[] = 'Authorization: Bearer '.token['access_token'];
					$headr[] =  "Content-Type: application/json";
					$postData['path'] = $erase;
					$postData = json_encode($postData);
					$ch = curl_init(API_DELETE_URL);
					curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
					curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
					curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
					curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
					$response = curl_exec($ch);
					curl_close($ch);
					$dtotal =$dtotal+$dsize;
				}
				else {echo "{$file['name']} is still a current folder on Dropbox\n";}
			}
		}
		$space = space();
		echo 'Total Deleted  From Dropbox '.formatBytes($dtotal,2)." Space remaining on Dropbox {$space['free']}\n";
		return; 
	}
	//echo "folder is /$folder (no Time)\n";
	echo "Deleting $folder\n";
	$headr[] = 'Authorization: Bearer '.token['access_token'];
	$headr[] =  "Content-Type: application/json";
	$postData['path'] = $folder;
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
		if (strpos($tmp['error_summary'],'not_found')){echo "$error $folder was not found\n";}
	}
	else {echo "Sucess Deleted $folder\n";} 
	
}
function list_files($path='',$display=false ) {
	//list data
	//echo "path =$path\n";
	
	if(empty($path) || debug) {echo 'path set to root'.cr;}
	else {if($path[0] <> '/') {$path ='/'.$path;}}
	if($path == "/") {$path="";}
	//die("$path\n");
	$headr[] = "Authorization: Bearer ".token['access_token'];
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
	//print_r($y); 
	if (empty($path) ) { $path='dDropBox';}
	if(isset($y['entries'])){
		$blank = array("","","");	
		$lines = count($y['entries']);
		
		$total_size=0;
		if(!$display){return $y['entries'];}
		$table = new Table(CONSOLE_TABLE_ALIGN_LEFT, borders, 1, null, true);
		$table->setHeaders(array('Name','Size','Modified'));
		//$table->setAlign(2, CONSOLE_TABLE_ALIGN_CENTER);
		//$table->setAlign(1, CONSOLE_TABLE_ALIGN_RIGHT);
		foreach ($y['entries'] as $entry) {
			if ($entry['.tag'] == 'file') {
				$path_parts = pathinfo($entry['path_display']);
				$basename = $path_parts['basename'];
				$basename = cc->convert("%g$basename%n");
				$date = date('d-m-Y  H:i:s',strtotime($entry['server_modified']));
				$size = trim(formatBytes($entry['size'],2));
				$total_size  +=$entry['size'];
				$table->addRow(array($basename,$size,$date));
			}
			else {
				$path_parts = pathinfo($entry['path_display']);
				$basename = $path_parts['basename'];
				$basename = cc->convert("%b$basename%n");
				$table->addRow(array( $basename,'Folder','N/A'));
			}
		}
		if ($total_size >0 || !isset($total_size)) {
			$total_size = formatBytes($total_size,2);
			$total = cc->convert("%YTotal%n");
			$table->addRow(array($total,cc->convert("%Y$total_size%n"),''));
		}
		if($lines==1){$table->addRow($blank);}
		echo $table->getTable();
	}
	else {
		if(TERM) {echo 'Could not find '.cc->convert("%y".substr($path,1)."%n").cr;}
		else {echo 'Could not find '.substr($path,1).cr;}
	}	
}
function correct_file($file,$folder){
	// make sure we have the full path
	$file_details = pathinfo($file);
	//print_r($file_details);
	//die();
	//die("$folder\n");
	if (keep){
		if(!defined("tld")) {define("tld",basename($file_details['dirname']));}
		$folder="$folder/".tld;
		//echo "folder now = $folder\n";
		$return['upload_path'] =  "/$folder/".$file_details['filename'];
	}
	else {
		$return['upload_path'] =  "/$folder/{$file_details['basename']}";
		if(!defined("tld")) {define("tld","");} 
	}
	if($file_details['dirname'][0] <> "/") { 
		$this_path = getcwd();
		$file_details['dirname'] ="$this_path/{$file_details['dirname']}";
		$file = "$this_path/$file";
	}
	$return['size'] = filesize($file);
	$return['bytes'] = trim(formatBytes($return['size'],2));
	$tld = basename($file_details['dirname']); // use this as a feature 
	if(!defined("tld")) {define("tld",$tld);}  // get the top level dir
	$tld_find = strpos($file,tld);
	$return['file'] = $file;
	if(debug){echo "tld = $tld file is $file\n";}
	//echo "constant = ".tld.cr;
	if($tld_find>0){$return['upload_path'] =  "/$folder/".substr($file, $tld_find+(strlen(tld)+1));} // correct the path to dropbox path + the shortened file path
	//else { $return['upload_path'] = "/$folder/.
	$return['upload_path'] = str_replace("//","/",$return['upload_path']);
	//print_r($return);
	//die();
	return $return;
}	
function info($print = false) {
	// get user data
	
	$headr[] = 'Authorization: Bearer '.token['access_token']; 
  
	$ch = curl_init(API_ACCOUNT_INFO_URL);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_HTTPHEADER,$headr);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
	$x = curl_exec($ch);
	curl_close($ch);
	$return =json_decode($x,true);
	$tmp = space();
	$return = array_merge($return,$tmp);
	//print_r($return);
	if($print){
		$table = new Table(CONSOLE_TABLE_ALIGN_RIGHT, borders, 1, null, true);
		echo "Dropbox Details\n";
		if ($return['email_verified']) {$return['email_verified'] = "Yes";}
		else {$return['email_verified'] = "No";}
		$table->setHeaders(array('Item', 'Value'));
		$table->addRow(array(cc->convert("%YUser%n"),$return['name']['display_name']));
		$table->addRow(array(cc->convert("%YEmail Address%n"),$return['email']));
		$table->addRow(array(cc->convert("%YVerified%n"),$return['email_verified']));
		$table->addRow(array(cc->convert("%YCountry%n"),$return['country']));
		$table->addRow(array(cc->convert("%YAccount Type%n"),$return['account_type']['.tag']));
		$table->addRow(array(cc->convert("%YQuota%n"),$return['quota']));
		$table->addRow(array(cc->convert("%YUsed%n"),$return['used']));
		$table->addRow(array(cc->convert("%YFree%n"),$return['free']));
		echo $table->getTable();
	}
	return $return; 
}
function space($print = false) {
	//display space
	$headr[] = 'Authorization: Bearer '.token['access_token'];
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
function download ($path) {
	$list = check_dir($path);
	$headr[] = 'Authorization: Bearer '.token['access_token'];
	$headr[] = 'Content-Type:';
	foreach($list as  $download){
		$cdir = pathinfo(working_dir.$download['path_display']);
		if (!is_dir($cdir['dirname'])) {mkdir($cdir['dirname'], 0755, true);}
		echo "Downloading {$download['path_display']}\n\n";
		ob_start();
		$headr[] = 'Dropbox-API-Arg: {"path":"' . $download['path_display'] . '"}';
		$ch = curl_init(API_DOWNLOAD_URL);
		$fp = fopen (working_dir.$download['path_display'], 'w+'); 
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $headr);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'progress');
		curl_setopt($ch, CURLOPT_NOPROGRESS, false); // needed to make progress function work 
		$metadata = null;
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($ch, $header) use (&$metadata){
			$prefix = 'dropbox-api-result:';
			if (strtolower(substr($header, 0, strlen($prefix))) === $prefix){$metadata = json_decode(substr($header, strlen($prefix)), true);}
			return strlen($header);
		}
		);
		$response = curl_exec($ch);
		curl_close($ch);
		fclose($fp);
		ob_end_clean();
		$x =json_decode($response,true);
		if($metadata['content_hash'] == $download['content_hash']){	echo "Successfully Downloaded {$download['path_display']}\n";}
		else {echo "Download of {$download['path_display']} failed\n\n";}
	}
}
function check_dir($path) {
	// are we downloading a file
	$paths = pathinfo($path);
	if(!isset($paths['extension'])){$search_path = $path; $isdir = true;}
	else {$search_path = $paths['dirname'] ; $isdir = false;}
	$filelist = list_files($search_path);
	foreach ($filelist as $file) {
		if ($file['.tag'] == "folder") {$return[$file['path_display']] = check_dir($file['path_display']);}
		else {$return[] = $file;	}
	}
	foreach($return as $k=>$download){
		if(count($download) >11){ 
			foreach($download as $folder_file){
				$return[] = $folder_file;
			}
			unset($return[$k]);
		}
	}
	return $return;
}
function progress($resource,$download_size, $downloaded, $upload_size, $uploaded){
    if($download_size > 0){
		$dd = formatBytes($downloaded ,2);
		$strlen = strlen($dd);
		$dd = str_pad($dd, 9-$strlen); 
		$ds = formatBytes($download_size,2);
        // echo $downloaded / $download_size  * 100;
        //echo "\033[K";
         echo "\033[1A"; 
         echo "\033[K";
         //echo "Downloaded $dd of $ds\n";
         printf("Downloaded %-8s of %-1s\n",$dd,$ds);
		ob_flush();
		flush();
		//sleep(1); // just to see effect
	}
//echo "Done";
ob_flush();
flush();
}	