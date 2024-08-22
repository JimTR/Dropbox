<?php
   
    if (!defined('DOC_ROOT')) {define('DOC_ROOT', realpath(dirname(__FILE__) . '/../'));}
    require DOC_ROOT . '/inc/functions.inc.php';  // spl_autoload_register() is contained in this file
    include DOC_ROOT. '/inc/settings.php'; // get settings
   	$build = "2115-1732187071";
	$time_format = "h:i:s A";  // force time display
	if(isset($settings['TIME_ZONE']) and !empty($settings['TIME_ZONE'])) { date_default_timezone_set("{$settings['TIME_ZONE']}");} //important set the correct time zone
	define ('settings',$settings); //globalize settings
	define('cr',PHP_EOL);
	define ('API_OAUTH_TOKEN',"https://api.dropbox.com/oauth2/token");
	define('API_OAUTH_AUTHORIZE',"https://www.dropbox.com/oauth2/authorize");
	define('API_LONGPOLL_FOLDER',"https://notify.dropboxapi.com/2/files/list_folder/longpoll");
	define('API_CHUNKED_UPLOAD_START_URL',"https://content.dropboxapi.com/2/files/upload_session/start");
	define('API_CHUNKED_UPLOAD_FINISH_URL',"https://content.dropboxapi.com/2/files/upload_session/finish");
	define('API_CHUNKED_UPLOAD_APPEND_URL',"https://content.dropboxapi.com/2/files/upload_session/append_v2");
	define('API_UPLOAD_URL',"https://content.dropboxapi.com/2/files/upload");
	define('API_DOWNLOAD_URL',"https://content.dropboxapi.com/2/files/download");
	define('API_DELETE_URL',"https://api.dropboxapi.com/2/files/delete");
	define('API_MOVE_URL',"https://api.dropboxapi.com/2/files/move");
	define('API_COPY_URL',"https://api.dropboxapi.com/2/files/copy");
	define('API_METADATA_URL',"https://api.dropboxapi.com/2/files/get_metadata");
	define('API_LIST_FOLDER_URL',"https://api.dropboxapi.com/2/files/list_folder");
	define('API_LIST_FOLDER_CONTINUE_URL',"https://api.dropboxapi.com/2/files/list_folder/continue");
	define('API_ACCOUNT_INFO_URL',"https://api.dropboxapi.com/2/users/get_current_account");
	define('API_ACCOUNT_SPACE_URL',"https://api.dropboxapi.com/2/users/get_space_usage");
	define('API_MKDIR_URL',"https://api.dropboxapi.com/2/files/create_folder");
	define('API_SHARE_URL',"https://api.dropboxapi.com/2/sharing/create_shared_link_with_settings");
	define('API_SHARE_LIST',"https://api.dropboxapi.com/2/sharing/list_shared_links");
	define('API_SAVEURL_URL',"https://api.dropboxapi.com/2/files/save_url");
	define('API_SAVEURL_JOBSTATUS_URL',"https://api.dropboxapi.com/2/files/save_url/check_job_status");
	define('API_SEARCH_URL',"https://api.dropboxapi.com/2/files/search");
	define('APP_CREATE_URL',"https://www.dropbox.com/developers/apps");
	define("cc", new Color());
	if(!empty(getenv("TERM"))) {define('TERM',true);} // we have a terminal
	else{define('TERM',false);} // likley to be a cron job
	// old command line switches
	$shortopts ="a:f:b:d:c:u:p:o:l:t:x:";
	$longopts[]="debug::";
	$longopts[]="help::";
	$options = getopt($shortopts,$longopts);
	define ('options',$options);
	//end old command line switches
arg("
			-d    --debug    bool  debug the code
			-a  --action    str     what to do
			-au   --upload    bool     upload a fle or folder
			-ad   --delete    bool     delete a file or folder
			-b  --backup-path    str     backup path
            -f  --folder    str     folder to work with
			-k  --keep  bool     add the file directory to the dropbox path
			-h  --help      bool  help (this screen)
			-m  --mkdir   str     make a directory
			-p  --path  str  file set to work with
			-t  --timed  bool  create a directory based on current date 
			-r  --retain  int  retain old backups for X days
			-V  --version   bool    show version and exit
			  
	");
	$all = arg(); 
	ksort($all);
	$action = arg("action");
	$backup_path = arg("backup-path");
	$path = arg("path");
	$folder = arg("folder");
	$delete = arg("delete");
	$version = arg("version");
	$verbose = arg("verbose");
	$resovle = arg("resolve");
    $summary = arg("summary");
    $debug = arg("debug");
    $upload = arg("upload");
    $help = arg("help");
    define('debug',$debug);
    $timed = arg("timed");
    define ("timed",$timed);
    define ("retain",arg("retain"));
    define("keep",arg("keep"));
    define('LOG',"dropbox.log");
    define ('borders',array('horizontal' => '─', 'vertical' => '│', 'intersection' => '┼','left' =>'├','right' => '┤','left_top' => '┌','right_top'=>'┐','left_bottom'=>'└','right_bottom'=>'┘','top_intersection'=>'┬'));
	