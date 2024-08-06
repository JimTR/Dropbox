<?php
/*
 * dropbox2.php
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
define("token",check_token());
if(empty($backup_path)) {$backup_path = gethostname(); }
else{
	//echo "$backup_path\n";
	$path_parts = pathinfo($backup_path);
	if(strtolower($path_parts['dirname']) =="local") {$path_parts['dirname'] =  gethostname(); }
	//print_r($path_parts);
}
if(!empty($folder)) {$backup_path .="/$folder";}
if($upload) {
	echo "upload set\n";
	if(empty($path)) {
		echo "no path supplied\n";
		exit;
	}
	if(empty($backup_path)) {
		echo "no dropbox path supplied\n";
		exit;
	}
	//define ("backup_path","/$backup_path");
	if (timed) { $path .= "/".date("d-m-y");}
	echo "Uploading to $backup_path\n"; 
	//die("$path\n");
	
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
	if(debug){echo "upload selected\n";}
	if(timed) { 
		echo "timer set\n";
		file_delete($backup_path,$options);
	}
	exit;
}
if($delete) {
	echo "$delete\n";
	file_delete($backup_path,$all);
	exit;
}
switch (strtolower($action)){
	case "d";
	case "delete":
	//echo "hit delete\n";
	//echo "backup_path = $backup_path\n";
	file_delete($backup_path,$all);
	break;
	case "l":
	case "list":
	list_files("","$backup_path",true);
	info(true);
	break;
	
	case "help":
	break;
	default:
	echo cc->convert("%RWarning%n"). ' no -a switch set'.cr;
} 

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
		if (isset($response['name'])) {echo "$short_file uploaded to dropbox {$file_info['upload_path']} ({$file_info['bytes']})\n";}
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
			//echo "file name = $fullFileName\n";
			if(is_file($fullFileName)){
				$result[] = $fullFileName;
				//if (debug) {
					log_to(LOG, "found $fullFileName");
					$tmp[] = explode("/",substr($fullFileName,1));
					$key =array_key_last($tmp);
					array_unshift($tmp[$key],$fullFileName);
				//}
			}
		}
	}
	catch (UnexpectedValueException $e) {printf("Directory [%s] contained a directory we can not recurse into", $directory);}
	foreach ($result as $k => $v) {if (is_dir($v))  {unset( $result[$k]);}}
	$result= array_values($result);
	//print_r($result);
	//print_r($tmp);
	//die();
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
		foreach ($abs_path_parts as $k=> $v) {
			echo "\t".$k.' => '.$v.cr;
		}
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
		//$data= db_check_token(); 
		unset ($headr);
		$chunk_num++;
		$fp = fopen("$targetpath/$chunk", 'rb');
		$csize = filesize("$targetpath/$chunk");
		$dsize = formatBytes($csize,2);
		$chunk_base = pathinfo($chunk,PATHINFO_FILENAME);
		echo "\tUploading $chunk_base ";
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
		/*echo "\tHeaders Sent".cr;
		foreach ($headr as $header){
			echo "\t$header".cr;
		}*/
		
		/*foreach ($response as $k => $v) {
			echo "\t".$k.' => '.$v.cr;
		}*/
		// do we hold on to the split file ? 	 
	}
	rrmdir($targetpath); // clean up
	echo "$chunk_base succesfully uploaded\n" ; //do something with this		
}

function rrmdir($src) {
    $dir = opendir($src);
    while(false !== ( $file = readdir($dir)) ) {
        if (( $file != '.' ) && ( $file != '..' )) {
            $full = $src . '/' . $file;
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
		//echo "$new is a date\n";
	}
	$test =date_parse_from_format(settings['DATE_FORMAT'], trim($value));
	//if ($test['error_count'] == 0) {
		$ct = new DateTime($test['year'].'-'.$test['month'].'-'.$test['day']);
		$ct->setTime(0,0,0);
		return $ct->getTimestamp();
	//}
    return false;
}
function file_delete($folder,$options) {
	// delete stuff
	$error = cc->convert("%RError%n");
	$timed = false;
	if($folder[0] <> "/") { $folder="/$folder";}
	if(timed){$timed = true;}
	//echo "folder is $folder\n";
	//exit;
	if($timed) {
		$now = time();
		$today = strtotime('00:00:00', $now); //set to midnight
		$remove = strtotime('-'.settings['FILE_RETAIN'].' day', $today-82800);
		$remove_date = date("d-m-y",$remove);
		echo "Checking for  folders older than $remove_date in folder $folder\n";
		
		if(empty($folder)) {echo "no folder set\n";}
		$fl = list_files($timed,$folder);
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
					$rf = list_files($timed,$folder.'/'.$file['name']);
					//print_r($rf);
					foreach ($rf as $tmp) {
						if ($tmp['.tag'] == 'file' ) {
							$dsize = $dsize+$tmp['size'];
						}
					}
					$erase =  "$folder{$file['name']}";
					//echo "the folder is $folder\n";
					echo "deleting $erase (".formatBytes($dsize,2).")\n";
					//die("this is what we are doing $erase\n");
					//exit;
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
					//echo $response.cr;
					curl_close($ch);
					$dtotal =$dtotal+$dsize;
				}
				else {echo "{$file['name']} is still a current folder\n";}
			}
		}
		echo 'Total Deleted '.formatBytes($dtotal,2).cr;
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
function list_files($data,$path='',$display=false ) {
	//list data
	$table = new Table(CONSOLE_TABLE_ALIGN_LEFT, borders, 1, null, true);
	$table->setHeaders(array('Type', 'Name','Size','Modified'));
	$table->setAlign(3, CONSOLE_TABLE_ALIGN_CENTER);
	$table->setAlign(2, CONSOLE_TABLE_ALIGN_RIGHT);
		if(empty($path)) {
		//echo 'path set to root'.cr;
	}
	else {
		// check
		if($path[0] <> '/') {$path ='/'.$path;}
	}
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
		$blank = array("","","","");	
		$lines = count($y['entries']);
		if($lines==1){$table->addRow($blank);}
		$total_size=0;
		foreach ($y['entries'] as $entry) {
			//echo print_r($entry,true).cr;
			if ($entry['.tag'] == 'file') {
				$path_parts = pathinfo($entry['path_display']);
				//echo 'path parts'.cr.print_r($path_parts,true).cr;
				$basename = $path_parts['basename'];
				//echo $entry['.tag'].' '.$path_parts['basename'].' '. formatBytes($entry['size'],2).cr;
				$total_size  +=$entry['size'];
				$table->addRow(array($entry['.tag'], cc->convert("%b$basename%n"),trim(formatBytes($entry['size'],2)),date('d-m-Y  H:i:s',strtotime($entry['server_modified']))));
			}
			else {
				$path_parts = pathinfo($entry['path_display']);
				//echo 'path parts'.cr.print_r($path_parts,true).cr;
				$basename = $path_parts['basename'];
				$table->addRow(array($entry['.tag'], cc->convert("%g$basename%n"),'N/A','N/A'));
				//echo $entry['.tag'].' '.$path_parts['basename'].cr;
			}
		}
		//if($data===false){
			echo cr.'Contents of '.cc->convert("%y".substr($path,1)."%n").cr;
			if ($total_size >0 || !isset($total_size)) {
				$total_size = formatBytes($total_size,2);
				$table->addRow(array('Total','',cc->convert("%Y$total_size%n"),''));
			}
			//echo $table->getTable();
		//}
		//$table->addRow($blank);
		if ($display){echo $table->getTable();}
		return $y['entries'];
	}
	else {
		echo 'Could not find '.cc->convert("%y".substr($path,1)."%n").cr;
	}	
}
function correct_file($file,$folder){
	// make sure we have the full path
	$file_details = pathinfo($file);
	if (keep){
		if(!defined("tld")) {define("tld",basename($file_details['dirname']));}
		$folder="$folder/".tld;
		//echo "folder now = $folder\n";
		$return['upload_path'] =  "/$folder/".$file_details['filename'];
	}
	if($file_details['dirname'][0] <> "/") { 
		$this_path = getcwd();
		$file_details['dirname'] ="$this_path/{$file_details['dirname']}";
		$file = "$this_path/$file";
	}
	$return['size'] = filesize($file);
	$return['bytes'] = formatBytes($return['size'],2);
	$tld = basename($file_details['dirname']); // use this as a feature 
	if(!defined("tld")) {define("tld",$tld);}  // get the top level dir
	$tld_find = strpos($file,tld);
	$return['file'] = $file;
	if($tld_find>0){$return['upload_path'] =  "/$folder/".substr($file, $tld_find+(strlen($tld)+1));} // correct the path to dropbox path + the shortened file path
	$return['upload_path'] = str_replace("//","/",$return['upload_path']);
	//else {$return['upload_path'] =  "/$folder/".substr($file, $tld_find);} 
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