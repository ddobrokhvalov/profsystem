<?php
	include 'spaw_control.class.php';

	$form_name		= htmlspecialchars( $_REQUEST["form_name"], ENT_QUOTES );
	$object_name	= htmlspecialchars( $_REQUEST["object_name"], ENT_QUOTES );
	//$object_name	= htmlspecialchars( '_rEdit', ENT_QUOTES );
	$object_value	= stripslashes( $_REQUEST["object_value"] );
	//var_dump($object_value);die();
	$sw = new SPAW_Wysiwyg( $object_name, $object_value, $spaw_default_lang, 'full', 'default', '100%', '500px' );
	//echo '<pre>';var_dump($sw);die();
?>
<html>
	<head>
		<title><?= $sw -> lang -> showMessage( 'title', 'title' ) ?></title>
		<script language="JavaScript" src="/common/js/jquery.js" type="text/javascript"></script>
		<script type="text/javascript" language="javascript">
			var formats_doc = [
				'docx',
				'doc',
				'rtf',
				'xls',
				'xlsx'
			];

			$(document).ready(function() {
				$("#ajax").click(function(){
					$('form[name="loadFile"]').slideToggle('normal');
				});

				$('#loadFile').click(function(){

					var file = $('form[name="loadFile"] input[name="docfile"]').val();
					file = file.split('\\');
					file = file[file.length-1].split('.');
					var ext = file[file.length-1];
					if($.inArray(ext, formats_doc) == -1)
					{
						alert('Формат файла не поддерживается, выберите допустимый: doc, docx, rtf, xls, xlsx');
						return;
					}
					AsyncUploadFile();
				});
			});

			function AsyncUploadFile()
			{
				$('form[name="loadFile"]').unbind();
				$('#frforload').unbind();
				$('form[name="loadFile"]').submit();
				
				var fr = $('#frforload')[0];

				if (fr.attachEvent) 
				{
					fr.attachEvent('onload', fr_onload);

				} 
				else
				{
					fr.onload = fr_onload;
				}

				/*$('#frforload')[0].onload =function()
				{
					var mes = window.frames['frameforload'].document.body.innerHTML;
					if(mes == undefined)
						return;
					else
					{
						$(window.frames[0].document.body).html(mes);
						$('form[name="loadFile"] input[name="docfile"]').val('');
					}
				};*/
			}
		
			function fr_onload()
			{
				var mes = window.frames['frameforload'].document.body.innerHTML;
				if(mes == undefined)
					return;
				else
				{
					$(window.frames[0].document.body).html(mes);
					$(window.frames[0].document.body).append('<link rel="stylesheet" type="text/css" href="/common/css/style.css" media="all">');
					$('form[name="loadFile"] input[name="docfile"]').val('');
				}
			}

		</script>
	</head>
	<body>
		<form name="editorForm">
<?php
	$sw -> show();
?>
			<input type="button" value="<?= $sw -> lang -> showMessage( 'title', 'save' ) ?>" onclick="SPAW_UpdateFields(); window.opener.document.forms['<?= $form_name ?>']['<?= $object_name ?>'].value = document.forms['editorForm']['<?= $object_name ?>'].value; self.close();">
			<input type='button' name='ajax' id="ajax" value='Загрузить документ'>
		</form>

		<form name="loadFile" method="post" enctype="multipart/form-data" style="display: none" action="/common/ajax.php" target="frameforload">
			<input type="file" name="docfile" />
			<input type="button" name="frforload" id="loadFile"  value="Загрузить"/>(doc, docx, rtf, xls, xlsx)
		</form >

		<iframe style="display: none" id="frforload" name="frameforload" src="/common/ajax.php" ></iframe>
	</body>
</html>
