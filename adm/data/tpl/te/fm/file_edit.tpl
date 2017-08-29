<h2 style="margin: 10px 5px 5px 5px">{$lang_fm_edit_file}</h2>
<h3 style="margin: 5px">{$path_line|escape}</h3>
{if $readonly && $error_message}
{$error_message}
{/if}
<div>
    <form action="index.php" method="post" enctype="multipart/form-data">
    	<table border="0" cellspacing="0" cellpadding="0" width="97%">
    		<tr style="vertical-align: top;">
    			<td class="td21">
    				{$lang_text}:
    			</td>
    			<td class="td22">
                    <textarea style="width: 100%; font-size: 12px; height: 500px; color: #000000;" name="text"{if $readonly} readonly="readonly"{/if}>{$text|escape:"html"}</textarea>
    			</td>
    		</tr>
    		<tr>
    			<td>&nbsp;</td>
                <td>
					{$html_hidden}
<table border="0" cellspacing="0" cellpadding="0">
<tr>
	<td style="padding: 0px 10px 0px 0px">{if !$readonly}<span class="left-black"><span class="right-black"><input class="button-black" type="submit" value="{$lang_action_apply}" /></span></span></td><td>{/if}<span class="left-black"><span class="right-black"><input class="button-black" type="button" value="{$lang_fm_cancel}" onclick="document.location.href = '{$back_url}'" /></span></span>
                    </td>
</tr>
</table>
    			</td>
    		</tr>
    	</table>
    </form>
</div>
