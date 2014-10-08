<?php
//SQLite database creation
	$dbname = 'base';
	$tableConfigHtaccess = "config_htaccess";

	if(!class_exists('SQLite3'))
	  die("SQLite 3 NOT supported.");

	$base=new SQLite3($dbname, 0666); 

	$query = "CREATE TABLE IF NOT EXISTS $tableConfigHtaccess (
				ID int NOT NULL PRIMARY KEY,
				site_path VARCHAR(127),            
				cache_path VARCHAR(127),
				fully_disabled_cache boolean,
				controller_disabled VARCHAR(63),
				method_disabled VARCHAR(127)            
				)";
				
	$results = $base->exec($query);
	$query = "SELECT count(*) FROM $tableConfigHtaccess WHERE ID=1";
	$base->exec($query);
	$query = "INSERT INTO $tableConfigHtaccess VALUES (1, 'scripts/CodeIgniter', 'static', 0, '', '')";
	$base->exec($query);


	//Définition des constantes
	define('CACHE_DELIMITER_START','#Beginning of cache. Don\'t erase this line');
	define('CACHE_DELIMITER_END','#End of cache. Don\'t erase this line');	
	
	
	if (isset($_POST['generate_htaccess'])) {
	   modifyHtaccess();
    } 
	
	//If button "change site path" was pressed
    if (isset($_POST['site_path'])) {
	   updateDataBase('site_path', $_POST['site_path']);
	   modifyHtaccess();
    } 
	
	//If button "change cache path" was pressed
	if (isset($_POST['cache_path'])) {
	   updateDataBase('cache_path', $_POST['cache_path']);
	   modifyHtaccess();
    } 
	
	//If button "fully disable cache" was pressed
	if (isset($_POST['fully_disable_cache'])) {	   
	   updateDataBase('fully_disabled_cache', true);
	   updateDataBase('controller_disabled', '');
	   updateDataBase('method_disabled', '');
	   modifyHtaccess();
    } 
	
	//If button "enable cache" was pressed
	if (isset($_POST['enable_cache'])) {
	   updateDataBase('fully_disabled_cache', false);
	   updateDataBase('controller_disabled', '');
	   updateDataBase('method_disabled', '');
	   modifyHtaccess();	   
    } 
	
	if (isset($_POST['disable_cache_controller'])) {
	    updateDataBase('fully_disabled_cache', false);
		updateDataBase('controller_disabled', $_POST['disable_cache_controller']);
		updateDataBase('method_disabled', $_POST['disable_cache_method']);
		modifyHtaccess();
    } 	
	

	
	/**
	 * Creates the cache part of the htaccess surrounded with delimiters. Uses datas contained in 
	 * table config_htaccess to fill text. 
	 *
	 */
	function makeCacheHtaccessInstruction(){
	
		//variables initialization
		$sSitePath = getValue('site_path');
		$sCachePath = getValue('cache_path');
		$bFullyDisable = getValue('fully_disabled_cache');
		$sControllerDisable = getValue('controller_disabled');
		$sMethodDisable = getValue('method_disabled');
		$cDisableAll = '';
		$cDisablePart = '#';
		
		
		if ($sMethodDisable != '') {
			$sMethodDisable = '/'.$sMethodDisable;
		}
		
		$sPathToDisable = $sControllerDisable . $sMethodDisable;
		
		if ($bFullyDisable == true) {
			$cDisableAll = '#';
		} 
		
		if ($sControllerDisable != '') {
			$cDisablePart = '';
		}		
	    
		$sHtaccessContent = "\n" 
							. CACHE_DELIMITER_START
							. ' 
# Active le module de réécriture d\'URL.
RewriteEngine on

#Pour désactiver le système de cache pour un contrôleur ou pour une vue décommenter l\'instruction suivante
#Remplacer controller_name par le nom du contrôleur et ajouter éventuellement le nom de la méthode et ses paramètres'
."\n".$cDisablePart.'RewriteCond $1 !^('.$sPathToDisable.')

#Pour désactiver totalement le système de cache, placer les deux instructions qui suivent en commentaire

#Si le fichier est en cache (l\'option -f veut dire est un fichier)'
."\n".$cDisableAll.'RewriteCond %{DOCUMENT_ROOT}/'.$sSitePath.'/'.$sCachePath.'/$1 -f
#alors on réécrit l\'url en ajoutant le dossier dans lequel sont stockées les pages statiques'
."\n".$cDisableAll.'RewriteRule ^(.*)$ '.$sCachePath.'/$1 [L]


#Sinon si le fichier n\'est pas en cache 
#RewriteCond %{DOCUMENT_ROOT}/'.$sSitePath.'/'.$sCachePath.'/$1 !-f
#Et que l\'on ne tente pas déjà d\'accéder à l\'application CodeIgniter, au dossier d\'images, à robot.txt ou aux pages statiques
RewriteCond $1 !^('.$sCachePath.'|index\.php|images|robots\.txt|editeur_htaccess.php)
#Alors on redirige vers l\'application CodeIgniter en ajoutant index.php/ dans l\'url
RewriteRule ^(.*)$ index.php/$1 [L]
'.CACHE_DELIMITER_END;
		return $sHtaccessContent;
	}
	
	
	/**
	 *  Replaces just the part concerning cache in htaccess file without modifying the rest
	 *
	 * @param String $sWholeHtaccess Content of the whole htaccess file
	 * @param String $sNewHtaccessCache Htaccess instructions concerning cache that will replace the old ones
	 */
	function replaceCacheInHtaccess($sWholeHtaccess, $sNewHtaccessCache) {
		//searches position of cache start delimiter
		$iPosCacheStartDelimiter = strrpos($sWholeHtaccess, CACHE_DELIMITER_START);
		//searches position of cache end delimiter
		$iPosCacheEndDelimiter = strrpos($sWholeHtaccess, CACHE_DELIMITER_END);
		//calculates size of former htaccess part 
		$iSizeOldHtaccesseCache = $iPosCacheEndDelimiter - $iPosCacheStartDelimiter + strlen(CACHE_DELIMITER_END);
		//replaces old htaccess part by new htaccess part
		$newHtaccess = substr_replace($sWholeHtaccess, $sNewHtaccessCache, $iPosCacheStartDelimiter, $iSizeOldHtaccesseCache);
		return $newHtaccess;
	}
	
	
		
	/**
	 * Writes content in file called .htaccess. Overwrites if already exists
	 *
	 * @param String $sContent Content to write in file
	 */
	function writeInHtaccessFile($sContent) {
		$handle = fopen('.htaccess', 'w');
		fwrite($handle, $sContent);
		fclose($handle);
	}	
	
	
	/**
	 * Changes cache path in TotalCache library by modifying constant named BASE-PATH 
	 *
	 * @param String $sNewCachePath Cache path replacing old one
	 */
	function changeCachePathInLibrarie ($sNewCachePath) {		
		
		$totalCacheContent = file_get_contents('application/libraries/TotalCache.php');
		$pattern = '/define\(\'BASE_PATH.*\;/';
		$replacement = 'define(\'BASE_PATH\',\''.$sNewCachePath.'/\');';
		$newCacheLibrarie = preg_replace($pattern, $replacement, $totalCacheContent);
		$handle = fopen('application/libraries/TotalCache.php', 'w');
		fwrite($handle, $newCacheLibrarie);
		fclose($handle);
	}
	
	
	
	/**
	 * modifies htaccess by making new instructions and overwriting old ones 
	 *
	 */
	function modifyHtaccess() {
	    //makes a new htaccess instruction string
		$sNewHtaccessCache = makeCacheHtaccessInstruction();
		//changes only htaccess instructions concerning cache 
		if (file_exists('.htaccess')) {		
			$sNewHtaccess = replaceCacheInHtaccess(file_get_contents('.htaccess'), $sNewHtaccessCache);
			writeInHtaccessFile($sNewHtaccess);
		} else {
		    writeInHtaccessFile($sNewHtaccessCache);
		}
	}
	
	
	/**
	 * Assigns $newValue to field $field in table config_htacces  
	 *
	 * @param String $sField Database field to update
	 * @param mixed $newValue New value of field $field
	 */
	function updateDataBase ($sField, $newValue){
		$query = 'UPDATE '.$GLOBALS['tableConfigHtaccess']." SET $sField = '$newValue' WHERE ID = 1";
		$results = $GLOBALS['base']->exec($query);
	}
	
	
	/**
	 * Returns value of field $field in table config_htaccess where ID=1
	 *
	 * @param String $sField Database field which value is returned
	 */
	function getValue($sField) {
	
		$query = "SELECT $sField FROM " . $GLOBALS['tableConfigHtaccess'] . " WHERE ID=1";
		$results = $GLOBALS['base']->query($query);
		$row = $results->fetchArray();
		if(count($row)>0)
		{
		   return $row["$sField"];
		}		
	}
	
	function displayTable () {
	$query = "SELECT * FROM config_htaccess";
	$results = $GLOBALS['base']->query($query);
	$row = $results->fetchArray();

	if(count($row)>0)
	{
	   echo $row['ID'];
	   echo $row['site_path']; 
	   echo $row['cache_path'];	
	}   
	}
?>




<!------------------------------------------------ Vue--------------------------------------------------->	
<html lang="fr">
<head>
	<link href="bootstrap/css/bootstrap.css" type="text/css" rel="stylesheet">
	<link href="bootstrap/css/my-style.css" type="text/css" rel="stylesheet">
	<meta charset="UTF-8">
</head>

<h1>Htaccess editor</h1>
<!--Button to generate htaccess-->
<form method="post" action="editeur_htaccess.php">
	<input type="hidden" value="" name="generate_htaccess"> 
	
	<div class="col-xs-push-8"><input type="submit" value="Generate default Htaccess file"></div>
</form> 


<!-- form to modify site path-->
<form method="post" action="editeur_htaccess.php">
    <div class="row">
        <div class="col-md-4"><label for="site_path">Enter site path:</label></div>
        <div class="col-md-4"><input type="text" name="site_path" id="" placeholder="Ex : scripts/monsite" size="30" /></div>
		<div class="col-md-4"><input type="submit" value="Update site path" /></div>
    </div>
</form>

<!-- form to modify cache path-->
<form method="post" action="editeur_htaccess.php">
    <div class="row">
        <div class="col-md-4"><label for="site_path">Enter cache path:</label></div>
        <div class="col-md-4"><input type="text" name="cache_path" id="" placeholder="Ex : scripts/monsite" size="30" /></div>
		<div class="col-md-4"><input type="submit" value="Update cache path" /></div>
    </div>
</form>

<!-- form to disable cache partially -->
<form method="post" action="editeur_htaccess.php">
    <div class="row">
		<div class="col-md-4"><label>Désactivation partielle du cache <label/></div>
        <div class="col-md-4">
			<label for="disable_cache_controller">Pour le contôleur :</label>
			<input type="text" name="disable_cache_controller" id="" placeholder="Ex : controller" size="30" />
			<label for="disable_cache_method">Pour la méthode :</label>
			<input type="text" name="disable_cache_method" id="" placeholder="Ex : method" size="30" />
		</div>
		<div class="col-md-4"><input type="button" value="Partially disable cache"/></div>
    </div>
</form>

<!-- form to disable cache fully-->	
<form method="post" action="editeur_htaccess.php">
<div class="row">
	<input type="hidden" value="" name="fully_disable_cache"> 
	<input type="submit" value="Fully disable cache">
</div>
</form> 

<!-- button to enable cache-->
<form method="post" action="editeur_htaccess.php">
<div class="row">
	<input type="hidden" value="" name="enable_cache"> 
	<input type="submit" value="Enable cache">
</div>
</form> 


<!--Summary of htaccess config-->
<div id="content" class="col-md-4">
	<span class="titre">Htaccess configuration summary</span>
	<label> Your site path: </label> <?php echo getValue('site_path')?> <br/>
	<label>Cache path: </label> <?php echo getValue('cache_path')?><br/>
	<label>Cache fully disabled: </label> <?php if (getValue('fully_disabled_cache') == true) echo 'YES'; else echo 'NO';?> <br/>
	<label>Cache partially disabled<br/>
	-for controller: </label> <?php echo getValue('controller_disabled')?><br/>
	<label>-for method: </label> <?php echo getValue('method_disabled')?>
</div>

</html>