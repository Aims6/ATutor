<?php
/************************************************************************/
/* ATutor                                                               */
/************************************************************************/
/* Copyright (c) 2002-2008 by Greg Gay, Joel Kronenberg & Heidi Hazelton*/
/* Adaptive Technology Resource Centre / University of Toronto          */
/* http://atutor.ca                                                     */
/*                                                                      */
/* This program is free software. You can redistribute it and/or        */
/* modify it under the terms of the GNU General Public License          */
/* as published by the Free Software Foundation.                        */
/************************************************************************/
// $Id: 

define('AT_INCLUDE_PATH', '../../include/');
require (AT_INCLUDE_PATH.'vitals.inc.php');
admin_authenticate(AT_ADMIN_PRIV_ADMIN);
require(AT_INCLUDE_PATH.'classes/Module/ModuleListParser.class.php');
require(AT_INCLUDE_PATH.'lib/filemanager.inc.php');

// delete all folders and files in $dir
function clear_dir($dir)
{
	if ($dh = opendir($dir)) 
	{
		while (($file = readdir($dh)) !== false)
		{
			if (($file == '.') || ($file == '..'))
				continue;

			if (is_dir($dir.$file)) 
				clr_dir($dir.$file);
			else 
				unlink($dir.$file);
		}
		
		closedir($dh);
	}
}

set_time_limit(0);

// check the connection to server update.atutor.ca
$update_server = "http://update.atutor.ca"; 
$connection_test_file = $update_server . '/index.php';
$connection = @file_get_contents($connection_test_file);

if (!$connection) 
{
	$infos = array('CANNOT_CONNECT_SERVER', $update_server);
	$msg->addError($infos);
	
	require(AT_INCLUDE_PATH.'header.inc.php');
  $msg->printAll();
	require(AT_INCLUDE_PATH.'footer.inc.php');
	exit;
}

// get module list
$module_folder = $update_server . '/modules/';

$module_list_xml = @file_get_contents($module_folder . 'module_list.xml');

if ($module_list_xml) 
{
	$moduleListParser =& new ModuleListParser();
	$moduleListParser->parse($module_list_xml);
	$module_list_array = $moduleListParser->getParsedArray();
}
// end of get module list

$module_content_folder = AT_CONTENT_DIR . "module/";
		
// Installation process
if ((isset($_POST['install']) || isset($_POST["download"]) || isset($_POST["version_history"])) && !isset($_POST["id"]))
{
	$msg->addError('NO_ITEM_SELECTED');
}
else if (isset($_POST['install']) || isset($_POST["download"]) || isset($_POST["version_history"]))
{
	if ($_POST['version_history'])
	{
		header('Location: '.AT_BASE_HREF.'admin/modules/version_history.php?id='.$_POST["id"]);
		exit;
	}

	// install and download
	$module_zip_file = $module_folder . $module_list_array[$_POST["id"]]['history'][0]['location'].$module_list_array[$_POST["id"]]['history'][0]['filename'];
	$file_content = file_get_contents($module_zip_file);

	if (!$file_content & ($_POST['install'] || $_POST['download']))
	{
		$msg->addError('FILE_NOT_EXIST');
	}
	else
	{
		if ($_POST['install'])
		{
			clear_dir($module_content_folder);
			
			// download zip file from update.atutor.ca and write into module content folder
			$local_module_zip_file = $module_content_folder. $module_list_array[$_POST["id"]]['history'][0]['filename'];
			$fp = fopen($local_module_zip_file, "w");
			fwrite($fp, $file_content);
			fclose($fp);
			
			// unzip uploaded file to module's content directory
			include_once(AT_INCLUDE_PATH . '/classes/pclzip.lib.php');
			
			$archive = new PclZip($local_module_zip_file);
		
			if ($archive->extract(PCLZIP_OPT_PATH, $module_content_folder) == 0)
			{
		    clear_dir($module_content_folder);
		    $msg->addError('CANNOT_UNZIP');
		  }
		
		  if (!$msg->containsErrors())
		  {
				// find unzip module folder name
				clearstatcache();
				
				if ($dh = opendir($module_content_folder)) 
				{
					while (($module_folder = readdir($dh)) !== false)
					{
						if ($module_folder <> "." && $module_folder <> ".." && is_dir($module_content_folder.$module_folder)) break;
					}
					
					closedir($dh);
				}

				if ($module_folder == "." || $module_folder == ".." || !isset($module_folder))
					$msg->addError('EMPTY_ZIP_FILE');
			}
		
		  // check if the same module exists in "mods" folder. If exists, it has been installed
		  if (!$msg->containsErrors())
		  {
		  	if (is_dir("../../mods/". $module_folder))
		  		$msg->addError('ALREADY_INSTALLED');
		  }

		  if (!$msg->containsErrors())
		  {
				header('Location: module_install_step_1.php?mod='.urlencode($module_folder).SEP.'new=1');
				exit;
			}
		}
		
		if ($_POST['download'])
		{
			$id = intval($_POST['id']);
		
			header('Content-Type: application/x-zip');
			header('Content-transfer-encoding: binary'); 
			header('Content-Disposition: attachment; filename="'.htmlspecialchars($module_list_array[$id]['history'][0]['filename']).'"');
			header('Expires: 0');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header('Pragma: public');
			header('Content-Length: '.strlen($file_content));
		
			echo $file_content;
			exit;
		}
	}
}

require (AT_INCLUDE_PATH.'header.inc.php');

$msg->printErrors();

?>

<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" name="form">
<?php 
?>
<table class="data" summary="" rules="cols">
<thead>
	<tr>
		<th scope="col">&nbsp;</th>
		<th scope="col"><?php echo _AT('module_name');?></th>
		<th scope="col"><?php echo _AT('description');?></th>
		<th scope="col"><?php echo _AT('atutor_version_to_work_with');?></th>
		<th scope="col"><?php echo _AT('atutor_version_tested_with');?></th>
	</tr>
</thead>
	
<tfoot>
<tr>
	<td colspan="5">
		<input type="submit" name="install" value="<?php echo _AT('install'); ?>" />
		<input type="submit" name="download" value="<?php echo _AT('download'); ?>" />
		<input type="submit" name="version_history" value="<?php echo _AT('version_history'); ?>" />
	</td>
</tr>
</tfoot>

<tbody>
<?php 
$num_of_modules = count($module_list_array);

if ($num_of_modules == 0)
{
?>

<tr>
	<td colspan="7"><?php echo _AT('none_found'); ?></td>
</tr>

<?php 
}
else
{
	// display modules
	if(is_array($module_list_array))
	{
		for ($i=0; $i < $num_of_modules; $i++)
		{
?>
	<tr onmousedown="document.form['m<?php echo $i; ?>'].checked = true; rowselect(this);"  id="r_<?php echo $i; ?>">
		<td><input type="radio" name="id" value="<?php echo $i; ?>" id="m<?php echo $i; ?>" /></td>
		<td><label for="m<?php echo $i; ?>"><?php echo $module_list_array[$i]["name"]; ?></label></td>
		<td><?php echo $module_list_array[$i]["description"]; ?></td>
		<td><?php echo $module_list_array[$i]["atutor_version_to_work_with"]; ?></td>
		<td><?php echo $module_list_array[$i]["atutor_version_tested_with"]; ?></td>
	</tr>

<?php 
		}
	}

?>
</tbody>

<?php 
}
?>
</table>
</form>

<?php require (AT_INCLUDE_PATH.'footer.inc.php'); ?>
