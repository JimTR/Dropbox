<?php
   
    if (!defined('DOC_ROOT')) {
    	define('DOC_ROOT', realpath(dirname(__FILE__) . '/../'));
    }
    
    require DOC_ROOT . '/inc/functions.inc.php';  // spl_autoload_register() is contained in this file
    include DOC_ROOT. '/inc/settings.php'; // get settings
    include DOC_ROOT.'/inc/functions.php'; 
	$build = "2115-1732187071";
	$time_format = "h:i:s A";  // force time display
	define ('SETTINGS',$settings); //globalize settings
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
	
	if(!empty(getenv("TERM"))) {
	 // run from console
		define('TERM',true);
	}
	else{
	 // run as cron
		define('TERM',false);
	}
	$shortopts ="a:f:b:d:c:u:p:o:l:t:x:";
	$longopts[]="debug::";
	$longopts[]="help::";
	$options = getopt($shortopts,$longopts);
	define ('options',$options);
?>
