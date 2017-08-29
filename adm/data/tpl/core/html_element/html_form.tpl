{if ($message)}
	{$message}
{/if}
<form action="index.php" method="POST" name="{$form_name}" enctype="multipart/form-data" onsubmit="return CheckForm.validate( this )">
	<table border="0" cellspacing="0" cellpadding="0" width="100%">
{$html_fields}
{$html_hidden}
		<tr>
			<td colspan="2">{if !$no_submit}<span class="left-black"><span class="right-black"><input class="button-black" type="submit" value="{$lang_apply}" /></span></span>{else}<input value="" type="submit" style="width: 0px;height: 0px;background: transparent;border: none;position: absolute;"/>{/if}</td>
		</tr>
	</table>
</form>
<script type="text/javascript" languare="javascript">
	function open_editor( field_name )
	{literal}{{/literal}
		window.open( '', 'editor_window', 'scrollbars=1, status=1, statusbar=1, resizable=1, resize=1');
		document.editorForm.target = 'editor_window';
		document.editorForm.form_name.value = '{$form_name}';
		document.editorForm.object_name.value = field_name;
		document.editorForm.object_value.value = document.{$form_name}[field_name].value;
		document.editorForm.submit();
	{literal}}{/literal}
</script>
<form name="editorForm" method="post" action="editor/index.php">
	<input type="hidden" name="form_name"/>
	<input type="hidden" name="object_name"/>
	<input type="hidden" name="object_value"/>
</form>
