<?php
   
    if (!defined('DOC_ROOT')) {
    	define('DOC_ROOT', realpath(dirname(__FILE__) . '/../'));
    }
    
    require DOC_ROOT . '/inc/functions.inc.php';  // spl_autoload_register() is contained in this file
    //require DOC_ROOT . '/includes/class.dbquick.php'; // DB quick class may replace dbobject.php... and has done 
    //require DOC_ROOT . '/includes/class.mobile_detect.php'; // device type class
    //require DOC_ROOT. '/includes/config.php'; // get config
    include DOC_ROOT. '/inc/settings.php'; // get settings
    include DOC_ROOT.'/inc/functions.php'; 
	$build = "2115-1732187071";
	$time_format = "h:i:s A";  // force time display
	define ('SETTINGS',$settings); //globalize settings
?>
