<?php
	include 'spaw_control.class.php';

	$form_name		= htmlspecialchars( $_REQUEST["form_name"], ENT_QUOTES );
	$object_name	= htmlspecialchars( $_REQUEST["object_name"], ENT_QUOTES );
	$object_value	= stripslashes( $_REQUEST["object_value"] );

	$sw = new SPAW_Wysiwyg( $object_name, $object_value, $spaw_default_lang, 'default', 'default', '100%', '500px' );
?>
<html>
	<head>
		<title><?= $sw -> lang -> showMessage( 'title', 'title' ) ?></title>
	</head>
	<body>
		<form name="editorForm">
<?php
	$sw -> show();
?>
			<input type="button" value="<?= $sw -> lang -> showMessage( 'title', 'save' ) ?>" onclick="SPAW_UpdateFields(); window.opener.document.forms['<?= $form_name ?>']['<?= $object_name ?>'].value = document.forms['editorForm']['<?= $object_name ?>'].value; self.close();">
		</form>
	</body>
</html>
