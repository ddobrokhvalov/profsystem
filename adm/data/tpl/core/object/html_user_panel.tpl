<table border="0" cellspacing="0" cellpadding="0" width="100%">
	<tr>
{if !$client_mode}
		<td>
			<div class="t-help">
				<a href="help/{$interface_lang}/RBC_Contents_Help_System.htm" target="_blank">{$lang_help_link}</a>
			</div>
		</td>
{/if}
		<td>
			<div class="t-user">
				{$user_name} <span>|</span> <a href="index.php?logout=1">{$lang_logout}</a>
			</div>
		</td>
{if !$client_mode}
		<td>
			<div class="t-lang">
				<div id="MenuLang"></div>
				<script>
					var MenuLang = new Menu(); MenuLang.init( 'MenuLang', [ {$menu_lang} ], 'only_image' );
				</script>
			</div>
		</td>
{/if}
	</tr>
</table>
