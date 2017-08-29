<script type="text/javascript" language="javascript" src="/common/js/core/translate.js.php?lang={$env.lang_root_dir}"></script>
<script type="text/javascript" language="javascript" src="/common/js/core/check_form.js"></script>
<script type="text/javascript" language="javascript" src="/common/js/core/calendar.js"></script>

<form action="" method="get" name="archives_form" onsubmit="return CheckForm.validate( this )">
<table border="0" cellspacing="0" cellpadding="0">
<tr>
	<td>{$sysw_arch_news_show_from}</td>
    <td class="f-input"><input type="text" name="afrom_{$env.area_id}" value="{$afrom}" size="10" lang="errors_nonempty__date_"></td>
    <td class="f-calendar"><a href="" onclick="Calendar.show( document.forms['archives_form']['afrom_{$env.area_id}'], this); return false"><img src="/common/img/calendar.gif" alt="{$sysw_calendar}" title="{$sysw_calendar}" class="calendar_w" border="0"></a></td>
    <td>{$sysw_arch_news_show_to}</td>
    <td class="f-input"><input type="text" name="ato_{$env.area_id}" value="{$ato}" size="10" lang="errors_nonempty__date_"></td>
    <td class="f-calendar"><a href="" onclick="Calendar.show( document.forms['archives_form']['ato_{$env.area_id}'], this); return false"><img src="/common/img/calendar.gif" alt="{$sysw_calendar}" title="{$sysw_calendar}" class="calendar_w" border="0"></a></td>
    <td><input type="submit" class="button" value="{$sysw_arch_news_show}"></td>
</tr>
</table>
</form>
<br><br>
