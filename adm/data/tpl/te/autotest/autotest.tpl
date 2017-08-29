<h2 style="margin: 10px 0px 20px 0px;">{$lang_autotest_tool}</h2>

<form method="post">
<input type='hidden' name='action' value='run'>

<div class="autotest"><input type='checkbox' onClick="check_group(this)">{$lang_autotest_full_check}<br>
	{$items}
</div>
<div style="margin: 12px 0px 0px 22px;">

<span class="left-black"><span class="right-black"><input class="button-black" type="submit" value="{$lang_autotest_do_check}" /></span></span>

</div>

</form>