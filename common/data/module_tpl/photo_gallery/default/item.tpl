{if $content}
  {foreach from=$content item=item}
    {if $item.IMG_BIG}
      {literal}
	  <script language="javascript">
		function resizeWin(w,h){
		 self.resizeTo(w+100,h+200);
		}
	  </script>
	  {/literal}
	  <div align="center" style="padding: 5px 10px;">
		<table>
			<tr>
				<td align="left"><h3>{$item.TITLE}</h3></td>
				<td align="right" valign="top">
					<a href="javascript:window.close();">{$sysw_photo_gallery_close_window}</a>
				</td>
			</tr>
			<tr>
				<td colspan="2">
					<div style="text-align: center;">
					{if $item.URL_NEXT}
			 			<a href="{$item.URL_NEXT}"><img src="{$item.IMG_BIG}" name="image" onload="resizeWin(this.width,this.height);" border="0" /></a></div>
		 			{else}
		 				<img src="{$item.IMG_BIG}" name="image" onload="resizeWin(this.width,this.height);" border="0" />
		 			{/if}
			 		<div style="padding-top:10px; text-align: left;">{$item.BODY}</div>
				</td>
		 	</tr>
		 	<tr>
		 		<td>
		 			{if $can_vote}
						{if $voted}
							{$sysw_photo_gallery_is_voted}
						{elseif $thanks}
							{$sysw_photo_gallery_vote_thanks}
						{elseif $fail}
							{$sysw_photo_gallery_vote_failed}	
					  	{else}
							{if $capcha_error}
								{$sysw_captcha_error}
							{/if}
							<form action="{$item.URL_CURRENT}" method="post">
								<input type="hidden" name="id_{$env.area_id}" value="{$item.PHOTO_GALLERY_ID}" />
								<input type="hidden" name="vote_{$env.area_id}" value="1" />
								<input type="hidden" name="captcha_id_{$env.area_id}" value="{$capcha_id}" />
								<img src="/common/tool/getcaptcha.php?captcha_id={$capcha_id}" />
								<input type="text" name="captcha_code_{$env.area_id}"><br />
								<input type="submit" value="{$sysw_photo_gallery_do_vote}" />
							</form>
						{/if}
					{/if}
		 		</td>
		 	</tr>
		</table>
	  </div>
	{/if}
  {/foreach}
{/if}