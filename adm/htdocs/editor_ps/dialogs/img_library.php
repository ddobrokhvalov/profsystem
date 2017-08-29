<?php 
// ================================================
// SPAW PHP WYSIWYG editor control
// ================================================
// Image library dialog
// ================================================
// Developed: Alan Mendelevich, alan@solmetra.lt
// Copyright: Solmetra (c)2003 All rights reserved.
// ------------------------------------------------
//                                www.solmetra.com
// ================================================
// $Revision: 1.2 $, $Date: 2008/07/30 08:32:22 $
// ================================================

// ================================================
// Changed: Alexander Kondratenko,      ak76@rbc.ru
// RBC Soft (c) 2005                 www.rbcsoft.ru
// ------------------------------------------------
// December 2005
// ================================================


// unset $spaw_imglib_include
unset($spaw_imglib_include);

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
$l->setBlock('image_insert');

$request_uri = urldecode(empty($HTTP_POST_VARS['request_uri'])?(empty($HTTP_GET_VARS['request_uri'])?'':$HTTP_GET_VARS['request_uri']):$HTTP_POST_VARS['request_uri']);

// if set include file specified in $spaw_imglib_include
if (!empty($spaw_imglib_include)) include $spaw_imglib_include;
?>

<?php 
$imglib = isset($HTTP_POST_VARS['flr']) ? $HTTP_POST_VARS['flr'] : '';
if (empty($imglib) && isset($HTTP_GET_VARS['flr'])) $imglib = $HTTP_GET_VARS['flr'];
elseif ($imglib == '') $imglib = $spaw_imglibs[0]['value'];

$imglib = preg_replace("/\/+/", "/", $imglib);
if (!preg_match("/\/$/i", $imglib)) $imglib .= "/";
if (preg_match("/\.+\/$/", $imglib)) $imglib = preg_replace("/[^\/]+\/\.\.\/$/", "", $imglib);

$img = isset($HTTP_POST_VARS['imglist']) ? $HTTP_POST_VARS['imglist'] : '';

$preview = '';

$errors = array();
if (isset($HTTP_POST_FILES['img_file']['size']) && $HTTP_POST_FILES['img_file']['size'] > 0)
	if ($img = uploadImg('img_file')) $preview = $spaw_base_url.$imglib.$img;

// delete
if ($spaw_img_delete_allowed && isset($HTTP_POST_VARS['lib_action']) && ($HTTP_POST_VARS['lib_action'] == 'delete') && !empty($img))
	deleteImg();
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
      if (document.getElementById('lib').value != '' && document.getElementById('imglist').value != '')
      {
  		<?php if (SPAW_Util::getBrowser() == 'Gecko') { ?>
  		var im = document.getElementById('imgpreview').contentDocument.images[0];	
  		<?php } else { ?>  			
      	var im = document.frames.imgpreview.document.body.childNodes.item(0);
  		<?php } ?>

        var iProps = {};                
        iProps.src = '<?php echo $spaw_base_url; ?>' + document.getElementById('lib').value + document.getElementById('imglist').value;
        iProps.width = (im.style.width)?im.style.width:im.width;
        iProps.height = (im.style.height)?im.style.height:im.height;
        iProps.border = 0;
        
        window.returnValue = iProps;
        window.close();
        <?php
        if (!empty($_GET['callback']))
          echo "opener.".$_GET['callback']."('".$_GET['editor']."',this);\n";
        ?>
      }
      else
      {
        alert('<?php echo $l->m('error').': '.$l->m('error_no_image'); ?>');
      }
    }

	function deleteClick()
	{
	  if (document.getElementById('imglist').value != '' && confirm('<?php echo $l->m('delete_confirm'); ?>') )
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
    window.name = 'imglibrary';
  //-->
  </script>

<table width=99% cellpadding=5 cellspacing=0 height=98% border="0">
<form name="libbrowser" id="libbrowser" method="post" action="" enctype="multipart/form-data" target="imglibrary">
<input type="hidden" name="theme" id="theme" value="<?php echo $theme; ?>">
<input type="hidden" name="request_uri" id="request_uri" value="<?php echo urlencode($request_uri); ?>">
<input type="hidden" name="lang" id="lang" value="<?php echo $l->lang; ?>">
<input type="hidden" name="lib_action" id="lib_action" value="">
<input type="hidden" name="flr" id="lib" value="<?php echo $imglib; ?>">
<input type="hidden" name="imglist" id="imglist" value="<?php echo $imglist; ?>">

	<tr>
		<td valign="top">

<div style="border: 1px solid Black; padding: 5 5 5 5;">
<table border="0" cellpadding="2" cellspacing="0">
<tr>
  <td valign="top" align="left"><b><?php echo $l->m('images'); ?>:</b></td>
  <td valign="top" align="left">&nbsp;</td>
  <td valign="top" align="left"><b><?php echo $l->m('preview'); ?>:</b></td>
</tr>
<tr>
  <td valign="top" align="left">
  <?php 
    if (!ereg('/$', $common_htdocs_server))
      $_root = $common_htdocs_server.'/';
    else
      $_root = $common_htdocs_server;
    
    $d = @dir($_root.$imglib);
  ?>
<div style='overflow: auto; height:250px; width: 250px; background-color: #ffffff;' id="dvfl">
<table border="0" cellspacing="0" cellpadding="0">
<tr><td colspan="3"><img src="image/spacer.gif" width="200" height="1"></td></tr>
<?php 
	if ($d) {
		$fileList = array();

		while (false !== ($entry = $d->read())) {
			if (is_dir($_root.$imglib.$entry)) {
				if ($entry != "." && $entry != "..") $fileList[] = array('name' => $entry, 'is_dir' => 1);
			}
			elseif (is_file($_root.$imglib.$entry)) {
				$fileList[] = array('name' => $entry, 'is_dir' => 0);
			}
		}
		$d->close();

		// функция сортировки списка содержимого директории
		function sortList($a, $b) {
			if ($a['is_dir'] == $b['is_dir'])
				return ($a['name'] < $b['name']) ? -1 : 1;
			return ($a['is_dir'] > $b['is_dir']) ? -1 : 1;
		}

		usort($fileList, 'sortList');

		if (strcmp($imglib, $spaw_imglibs[0]['value']) > 0) array_unshift($fileList, array('name' => '..', 'is_dir' => 1));

		$cnt = 1;
		foreach ($fileList as $entry) {
			$ext = strtolower(substr(strrchr($entry['name'], '.'), 1));
			if ((!$entry['is_dir'] && in_array($ext, $spaw_valid_imgs)) || $entry['is_dir']) {
?>
<tr id="i<?php echo $cnt; if ($img == $entry['name']) { echo '" bgcolor="'.$bgcolor; $lastID = 'i'.$cnt; } ?>">
<td width="18"><?php
if ($img == $entry['name']) {
	echo '<script language="javascript">document.getElementById("imglist").value = "' . $img . '"</script>';
} ?>
<a href="#" onClick="<?php
if ($entry['is_dir']) {
?>document.getElementById('lib').value = document.getElementById('lib').value + '<?php echo $entry['name']; ?>/'; document.getElementById('libbrowser').submit();<?php
}
else { ?>document.getElementById('imgpreview').src = '<?php echo $spaw_base_url.$imglib.$entry['name']; ?>'; document.getElementById('imglist').value = '<?php echo $entry['name']; ?>'; highlightIt('i<?php echo $cnt; ?>');<?php } ?>"><img src="<?php echo $spaw_base_url ?>adm/img/fm/mime/<?php echo ($entry['is_dir'] ? ($entry['name'] == '..' ? 'o' :  'c').'f' : (file_exists($common_htdocs_server."/adm/img/fm/mime/".$ext.".gif") == true ? $ext : 'file')); ?>.gif" border="0" vspace="1"></a></td>
<td width="1">&nbsp;</td>
<td width="100%"><a href="#" onClick="<?php
if ($entry['is_dir']) {
?>document.getElementById('lib').value = document.getElementById('lib').value + '<?php echo $entry['name']; ?>/'; document.getElementById('libbrowser').submit();<?php
}
else { ?>document.getElementById('imgpreview').src = '<?php echo $spaw_base_url.$imglib.$entry['name']; ?>'; document.getElementById('imglist').value = '<?php echo $entry['name']; ?>'; highlightIt('i<?php echo $cnt; ?>');<?php } ?>"><?php echo $entry['name']; ?></a></td>
</tr>
<?php
			}
			$cnt++;
		}
	}
	else {
		$errors[] = $l->m('error_no_dir');
	}
?>
</table>
</div>
  </td>
  <td valign="top" align="left">&nbsp;</td>
  <td valign="top" align="left" width="100%">
  <iframe name="imgpreview" id="imgpreview" src="<?php echo $preview; ?>" style="width: 100%; height: 100%;" scrolling="Auto" marginheight="0" marginwidth="0" frameborder="0"></iframe>
  </td>
</tr>
<tr>
  <td valign="top" align="left" colspan="3">
  <table border="0" cellpadding="0" cellspacing="0" width="100%" style="margin: 0 0 0 0;">
  <tr>
    <td align="left" valign="middle" width="70%">
      <input type="button" value="<?php echo $l->m('select'); ?>" class="bt" onclick="selectClick();">
	  <?php if ($spaw_img_delete_allowed) { ?>
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
    <b><?php echo $l->m('upload'); ?>:</b> <input type="file" name="img_file" class="input"><br>
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
function uploadImg($img) {

  global $HTTP_POST_FILES;
  global $HTTP_SERVER_VARS;
  global $spaw_valid_imgs;
  global $imglib;
  global $errors;
  global $l;
  global $spaw_upload_allowed;
  global $common_htdocs_server;
  
  if (!$spaw_upload_allowed) return false;

  if (!ereg('/$', $common_htdocs_server))
    $_root = $common_htdocs_server.'/';
  else
    $_root = $common_htdocs_server;

  $image_name = upload::upload_file( $HTTP_POST_FILES[$img], $_root.$imglib, false, true );

  return basename( $image_name );
}

function deleteImg()
{
  global $HTTP_SERVER_VARS;
  global $imglib;
  global $img;
  global $spaw_img_delete_allowed;
  global $errors;
  global $l;
  global $common_htdocs_server;
  
  if (!$spaw_img_delete_allowed) return false;

  if (!ereg('/$', $common_htdocs_server))
    $_root = $common_htdocs_server.'/';
  else
    $_root = $common_htdocs_server;
	
  $full_img_name = $_root.$imglib.$img;

  if (@unlink($full_img_name)) {
  	return true;
  }
  else
  {
  	$errors[] = $l->m('error_cant_delete');
	return false;
  }
}
?>
