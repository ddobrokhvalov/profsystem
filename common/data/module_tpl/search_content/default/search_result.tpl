<div class="search_result_wrap">
	<div class="search_result_form">
		<form id="search_form" class="search_form" name="search_form">
			<input type="text" id="search_string" class="search_string" name="search_string_{$env.area_id}" value="{$search_string}">
			<input type="submit" class="search_submit" value="Поиск">
		</form>
	</div>
	<div class="search_result_div">
		{foreach from=$search_results item=search_result}
			<div class="search_result">
				<div class="article_title">{$search_result.TITLE}</div>
				<div class="article_announce">{$search_result.ANNOUNCE|truncate:200:"..."} <a href="{$search_result.URL}">Подробнее</a></div>
			</div>
		{foreachelse}
			<div class="search_result">
				<div class="article_announce">Ничего не найдено</div>
			</div>
		{/foreach}
	</div>
	{if $navigation}
		<div class="sub-links">{$navigation}</div>
	{/if}
</div>