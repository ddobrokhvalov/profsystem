<div class="auth">
    <div class="box-tline"><div class="box-t"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div></div><div class="box">
        
        <h1>{$lang_authentication}</h1>
        
        {if ($error)}<div class="err">{$error}</div>{/if}
        <table border="0" cellspacing="0" cellpadding="0">
        <form action="index.php" method="POST" id="login" onsubmit="return CheckForm.validate( this )">
        	<tr>
        		<td class="auth_td">{$lang_login}</td>
        		<td><input type="text" name="login" lang="errors_nonempty_"/></td>
        	</tr>
        	<tr>
        		<td class="auth_td">{$lang_password}</td>
        		<td><input type="password" name="password" lang="errors_nonempty_"/></td>
        	</tr>
        	<tr>
        		<td>&nbsp;</td>
                <td style="padding: 3px 0px 0px 0px;"><span class="left-black"><span class="right-black"><input class="button-black" type="submit" value="{$lang_come_in}" style="color: #F7F5DF; width: 50px;" /></span></span></td>
        	</tr>
        </form>
        </table>
    </div><div class="box-bline"><div class="box-b"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div></div>
</div>
<script language="javascript">
	window.onload = new Function( "document.forms['login']['login'].focus()" );
</script>
