<?php
// ================================================
// SPAW PHP WYSIWYG editor control
// ================================================
// File library dialog
// ================================================
// Developed: Alan Mendelevich, alan@solmetra.lt
// Copyright: Solmetra (c)2003 All rights reserved.
// ------------------------------------------------
//                                www.solmetra.com
// ================================================
// $Revision: 1.3 $, $Date: 2008/07/30 08:32:21 $
// ================================================

// ================================================
// Changed: Alexander Kondratenko,      ak76@rbc.ru
// RBC Soft (c) 2005                 www.rbcsoft.ru
// ------------------------------------------------
// December 2005
// ================================================


// unset $spaw_imglib_include
//unset($spaw_imglib_include);

// include wysiwyg config
include '../config/spaw_control.config.php';
include $spaw_root.'class/util.class.php';
include $spaw_root.'class/lang.class.php';

$common_htdocs_server = params::$params["common_htdocs_server"]["value"];
$adm_htdocs_server = params::$params["adm_htdocs_server"]["value"];

$bgcolor = "#f8f8f8";

$theme = empty($HTTP_POST_VARS['theme'])?(empty($HTTP_GET_VARS['theme'])?$spaw_default_theme:$HTTP_GET_VARS['theme']):$HTTP_POST_VARS['theme'];
$theme_path = $spaw_dir.'lib/themes/'.$theme.'/';

$l = new SPAW_Lang(empty($HTTP_POST_VARS['lang'])?$HTTP_GET_VARS['lang']:$HTTP_POST_VARS['lang']);
$l->setBlock('file_insert');

$request_uri = urldecode(empty($HTTP_POST_VARS['request_uri'])?(empty($HTTP_GET_VARS['request_uri'])?'':$HTTP_GET_VARS['request_uri']):$HTTP_POST_VARS['request_uri']);
?>

<?php
$filelib = isset($HTTP_POST_VARS['flr']) ? $HTTP_POST_VARS['flr'] : '';
if (empty($filelib) && isset($HTTP_GET_VARS['flr'])) $filelib = $HTTP_GET_VARS['flr'];
elseif ($filelib == '') $filelib = $spaw_filelibs[0]['value'];

$filelib = preg_replace("/\/+/", "/", $filelib);
if (!preg_match("/\/$/i", $filelib)) $filelib .= "/";
if (preg_match("/\.+\/$/", $filelib)) $filelib = preg_replace("/[^\/]+\/\.\.\/$/", "", $filelib);

$file = isset($HTTP_POST_VARS['filelist']) ? $HTTP_POST_VARS['filelist'] : '';

$preview = '';

$errors = array();
if (isset($HTTP_POST_FILES['file_file']['size']) && $HTTP_POST_FILES['file_file']['size'] > 0)
	if ($file = uploadFile('file_file')) $preview = $spaw_base_url.$filelib.$file;

// delete
if ($spaw_file_delete_allowed && isset($HTTP_POST_VARS['lib_action']) && ($HTTP_POST_VARS['lib_action'] == 'delete') && !empty($file))
	deleteFile();
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
  <title><?php echo $l->m('title'); ?></title>
	<meta http-equiv="Pragma" content="no-cache">
  <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $l->getCharset(); ?>">
  <link rel="stylesheet" type="text/css" href="<?php echo $theme_path.'css/'; ?>dialog.css">
  <?php if (SPAW_Util::getBrowser() == 'Gecko') { ?>
  <script language="javascript" src="utils.gecko.js"></script>
  <?php } else { ?>
  <script language="javascript" src="utils.js"></script>
  <?php } ?>

  <script language="javascript">
  <!--
    function selectClick()
    {
	  if (document.getElementById('filelist').value == '')
	  {
        alert('<?php echo $l->m('error').': '.$l->m('error_no_file'); ?>');
	  }
	  else if (document.getElementById('urlname').value == '')
	  {
        alert('<?php echo $l->m('error').': '.$l->m('error_no_url'); ?>');
	  }
	  else if (document.getElementById('lib').value != '')
      {
	var linkProps = {};
	linkProps.href = '<?php echo $spaw_base_url; ?>' + document.getElementById('lib').value + document.getElementById('filelist').value;
	linkProps.title = document.getElementById('urlname').value;
        window.returnValue = linkProps;
        window.close();
        <?php
        if (!empty($_GET['callback']))
          echo "opener.".$_GET['callback']."('".$_GET['editor']."',this);\n";
        ?>
      }
    }

	function deleteClick()
	{
	  if (document.getElementById('filelist').value != '' && confirm('<?php echo $l->m('delete_confirm'); ?>') )
	  {
      document.getElementById('lib_action').value = 'delete';
      document.getElementById('libbrowser').submit();
	  }
	}

  function Init()
  {
  	resizeFL();
    //resizeDialogToContent();
  }
  function Resize() {
  	resizeFL();
  }
  
  function resizeFL() {
  	h = document.body.clientHeight - 200;
  	if (h>100)
  		document.getElementById('dvfl').style.height=h;
  }
//-->
function highlightIt(iID) {
	if (lastID != '') {
		lastIDRef = document.getElementById(lastID).style;
		lastIDRef.backgroundColor = "#ffffff";
	}
	iIDRef = document.getElementById(iID).style;
	iIDRef.backgroundColor = "<?php echo $bgcolor; ?>";
	lastID = iID;
}
  </script>
</head>

<body dir="<?php echo $l->getDir(); ?>" topmargin="0" bottommargin="0" marginheight="0" onLoad="Init()" onResize="Resize()">
  <script language="javascript">
  <!--
    window.name = 'filelibrary';
  //-->
  </script>
<table cellpadding=5 cellspacing=0 border="0">
<form name="libbrowser" id="libbrowser" method="post" action="" enctype="multipart/form-data" target="filelibrary">
<input type="hidden" name="theme" id="theme" value="<?php echo $theme; ?>">
<input type="hidden" name="request_uri" id="request_uri" value="<?php echo urlencode($request_uri); ?>">
<input type="hidden" name="lang" id="lang" value="<?php echo $l->lang; ?>">
<input type="hidden" name="lib_action" id="lib_action" value="">
<input type="hidden" name="flr" id="lib" value="<?php echo $filelib; ?>">
<input type="hidden" name="filelist" id="filelist" value="<?php echo $filelist; ?>">
	<tr>
		<td valign="top">
<div style="border: 1px solid Black; padding: 5 5 5 5;">
<table cellpadding="2" cellspacing="0">
<tr>
  <td valign="top" align="left"><b><?php echo $l->m('files'); ?>:</b></td>
</tr>
<tr>
  <td width="100%" valign="top" align="left">
  <?php
    if (!ereg('/$', $common_htdocs_server))
      $_root = $common_htdocs_server.'/';
    else
      $_root = $common_htdocs_server;

    $d = @dir($_root.$filelib);
  ?>
<div style='overflow: auto; height:250px; width: 400px; background-color: #ffffff;' id="dvfl">
<table width="95%" bgcolor="#ffffff" border="0" cellspacing="0" cellpadding="0">
<tr><td colspan="3"><img src="image/spacer.gif" width="200" height="1"></td></tr>
<?php
	if ($d) {
		$fileList = array();

		while (false !== ($entry = $d->read())) {
			if (is_dir($_root.$filelib.$entry)) {
				if ($entry != "." && $entry != "..") $fileList[] = array('name' => $entry, 'is_dir' => 1);
			}
			elseif (is_file($_root.$filelib.$entry)) {
				$fileList[] = array('name' => $entry, 'is_dir' => 0);
			}
		}
		$d->close();

		if (count($fileList) > 0) { ?>
<tr><td colspan="3"><img src="image/spacer.gif" width="250" height="1"></td></tr>
<?php			// функция сортировки списка содержимого директории
			function sortList($a, $b) {
				if ($a['is_dir'] == $b['is_dir'])
					return ($a['name'] < $b['name']) ? -1 : 1;
				return ($a['is_dir'] > $b['is_dir']) ? -1 : 1;
			}

			usort($fileList, 'sortList');

			if (strcmp($filelib, $spaw_filelibs[0]['value']) > 0) array_unshift($fileList, array('name' => '..', 'is_dir' => 1));

			$cnt = 1;
			foreach ($fileList as $entry) {
				$ext = strtolower(substr(strrchr($entry['name'], '.'), 1));
?>
<tr id="i<?php echo $cnt; if ($file == $entry['name']) { echo '" bgcolor="'.$bgcolor; $lastID = 'i'.$cnt; } ?>">
<td width="18"><a href="#" onClick="<?php
if ($entry['is_dir']) {
?>document.getElementById('lib').value = document.getElementById('lib').value + '<?php echo $entry['name']; ?>/'; document.getElementById('libbrowser').submit();<?php
}
else { ?>document.getElementById('filelist').value = '<?php echo $entry['name']; ?>'; highlightIt('i<?php echo $cnt; ?>');<?php } ?>"><img src="<?php echo $spaw_base_url ?>adm/img/fm/mime/<?php echo ($entry['is_dir'] ? ($entry['name'] == '..' ? 'o' :  'c').'f' : (file_exists($common_htdocs_server."/adm/img/fm/mime/".$ext.".gif") == true ? $ext : 'file')); ?>.gif" border="0" vspace="1"></a></td>
<td width="1">&nbsp;</td>
<td width="100%"><a href="#" onClick="<?php
if ($entry['is_dir']) {
?>document.getElementById('lib').value = document.getElementById('lib').value + '<?php echo $entry['name']; ?>/'; document.getElementById('libbrowser').submit();<?php
}
else { ?>document.getElementById('filelist').value = '<?php echo $entry['name']; ?>'; highlightIt('i<?php echo $cnt; ?>');<?php } ?>"><?php echo $entry['name']; ?></a></td>
</tr>
<?php
				$cnt++;
			}
		}
//		else{ //if ($filelib == '') {
//			print $filelib."Папка пуста.";
//		}
	}
	else {
		$errors[] = $l->m('error_no_dir');
	}
?>
</table>
</div>
  </td>
</tr>
<tr>
  <td valign="top" align="left" height="100%">
  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 0 0;">
  <tr>
    <td align="left" valign="middle" width="70%">
	  <?php if ($spaw_file_delete_allowed) { ?>
      <input type="button" value="<?php echo $l->m('delete'); ?>" class="bt" onclick="deleteClick();">
	  <?php } ?>
	</td>
	<td align="right" valign="middle" width="30%">
	  <input type="button" value="<?php echo $l->m('cancel'); ?>" class="bt" onclick="window.close();">
	</td>
  </tr>
  </table>
  </td>
</tr>
</table>
</div>

<div style="border: 1px solid Black; padding: 5 5 5 5;">
<table border="0" cellpadding="2" cellspacing="0">
<tr>
  <td valign="top" align="left">
  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 0 0;">
  <tr>
    <td align="left" valign="middle"> <?php echo $l->m('urlname'); ?>: <input type="text" name="urlname" id="urlname" class="input"></td>
    <td>&nbsp;&nbsp;&nbsp;</td>
    <td><input type="button" value="<?php echo $l->m('select'); ?>" class="bt" onclick="selectClick();"></td>
  </tr>
  </table>
  </td>
</tr>
</table>
</div>
</td></tr>
<script language="javascript">
<!--
var lastID = '<?php echo $lastID; ?>';
//-->
</script>
<tr><td valign="bottom">
<?php  if ($spaw_upload_allowed) { ?>
<div style="border: 1px solid Black; padding: 5 5 5 5;">
<table border="0" cellpadding="2" cellspacing="0">
<tr>
  <td valign="top" align="left">
    <?php
    if (!empty($errors))
    {
      echo '<span class="error">';
      foreach ($errors as $err)
      {
        echo $err.'<br>';
      }
      echo '</span>';
    }
    ?>

  <?php
  if ($d) {
  ?>
    <b><?php echo $l->m('upload'); ?>:</b> <input type="file" name="file_file" class="input"><br>
    <input type="submit" name="btnupload" id="btnupload" class="bt" value="<?php echo $l->m('upload_button'); ?>">
  <?php
  }
  ?>
  </td>
</tr>
</table>
</div>
<?php  } ?>
</form>
</td></tr></table>
</body>
</html>

<?php
function uploadFile($file) {

  global $HTTP_POST_FILES;
  global $HTTP_SERVER_VARS;
  global $spaw_valid_files;
  global $filelib;
  global $errors;
  global $l;
  global $spaw_upload_allowed;
  global $common_htdocs_server;

  if (!$spaw_upload_allowed) return false;

  if (!ereg('/$', $common_htdocs_server))
    $_root = $common_htdocs_server.'/';
  else
    $_root = $common_htdocs_server;

  $image_name = upload::upload_file( $HTTP_POST_FILES[$file], $_root.$filelib, false, true );

  return basename( $image_name );
}

function deleteFile()
{
  global $HTTP_SERVER_VARS;
  global $filelib;
  global $file;
  global $spaw_file_delete_allowed;
  global $errors;
  global $l;
  global $common_htdocs_server;

  if (!$spaw_file_delete_allowed) return false;

  if (!ereg('/$', $common_htdocs_server))
    $_root = $common_htdocs_server.'/';
  else
    $_root = $common_htdocs_server;

  $full_file_name = $_root.$filelib.$file;
  
  if ( !upload::is_valid_ext( $file ) )
	throw new Exception( metadata::$lang['lang_fm_ext_not_valid'] );
  
  if (@unlink($full_file_name)) {
  	return true;
  }
  else
  {
  	$errors[] = $l->m('error_cant_delete');
	return false;
  }
}
?>
