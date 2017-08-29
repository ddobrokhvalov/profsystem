{php}
	$this->assign ('adm_tpl_path', params::$params["adm_data_server"]["value"].'tpl/');
{/php}
{if ($table_caption)}
	<div style="padding: 0px 0px 3px 0px; font-weight: bold;">{$table_caption}</div>
{/if}
{include file="$adm_tpl_path/core/html_element/html_table.tpl"}
{$text_after}