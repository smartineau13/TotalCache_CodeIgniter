<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');


/**
 * TotalCache class allows generating HTML CodeIgniter views ans saves them as cache file.
 * They can be served without having to generate them again next time they are called.
 *
 * @project TotalCache for CodeIgniter
 * @author Sebastien Martineau
 * @date 15/09/2014
 */  
class TotalCache {

private $CI = null;// CodeIgniter super Object

private $sErrorMessageCouldnotCreateCachingFile = 'Couldn\'t create caching file';
    
public function __construct() {    
	//Creation of a CodeIgniter instance so that we can access its native functionalities
    $this->CI = & get_instance();
	define('BASE_PATH','dossier_static/');		
}


/**
 * Generates an HTML file saved at the address defined by sBasePath variable.  
 * Then view is displayed
 *
 * @param String $viewFileNameWithoutExtension Name of the view to be cached and displayed
 * @param array $data Array containing data to be transmitted to the view. It's the same 
 * as the one passed to method view() of object load of CodeIgniter.
 * See documentation at https://ellislab.com/codeigniter/user-guide/general/views.html 
 */
public function viewAndCache($viewFileNameWithoutExtension, $data)
{ 
   	
	// DECLARATIONS

	//CACHE FILE
	$iFileMode = 0777;
	$bIsRecursive = true;
	$sHTML = ''; //HTML generated by application
	

	/*DIRECTORY PATH
	Creation of directory in which will be saved cache file
    uri_string return uri. Example for site.com/index.php/news/local/345 uri_string returns news/local/345
	For this example this function would create a directory news/local containing file 345.html*/
	//Truncates uri from last slash to obtain directory tree
	$sURI = $this->CI->uri->uri_string();
	$iLastSlashPosition = strrpos($sURI,'/');
	//Defines directory path
	$sDirCachePath = BASE_PATH . substr($sURI,0,$iLastSlashPosition);

	//CACHE FILE NAME AND PATH
	$sCacheFileExtension = '.html';
    $sCachePath = BASE_PATH . $sURI . $sCacheFileExtension;	

	/* PREVENT TECHNICAL ERRRORS
	*/
	//Creates directory tree if doesn't already exist
    if (!is_dir($sDirCachePath)) { 
        (mkdir($sDirCachePath,$iFileMode,$bIsRecursive));
    }        
    
	/* REAL CODE!
	*/
	$fp = fopen($sCachePath,'w');
	
     if ($fp === false) {
        //If file creation fails an error message is shown
        show_error($this->sErrorMessageCouldnotCreateCachingFile) ;
    } else {
        /*
		1 - sends the view to static cache (generate html file on disk)
		2 - sends HTML to browser
		*/
        $sHTML = $this->CI->load->view($viewFileNameWithoutExtension, $data, true);
        fwrite($fp, 'total***'.$sHTML);
        fclose($fp);
        
        echo $sHTML;
    }//end of else    
               
   }//end of method

}//end of class


?>