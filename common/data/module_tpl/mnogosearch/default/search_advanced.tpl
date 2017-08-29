<!-- Должен располагаться в css проекта -->
{literal}
<style type="text/css">
	.search_advansed .inp_width {width: 190px;}  /* ширина Текстовых полей и некоторых Селектов */
	.search_advansed .button_td {padding-top: 10px;}  /*  Отступ сверху для ячейки в которой находится кнопка */
</style>
{/literal}
<!-- /Должен располагаться в css проекта -->

<h3>{$sysw_search_advanced_title}</h3>
<FORM ACTION="{$search_page_url}" class="search_advansed">
<table border="0" cellspacing="0" cellpadding="3">
<tr>
<td align="right">{$sysw_search_inquiry}:</td>
<td><input class="inp_width" type="text" name="q_{$tarea_id}" {if ($q)}value="{$q}"{else}value="{$sysw_search_what}" onfocus="javascript: this.value=''"{/if}></td>
</tr>
<tr>
<td align="right">{$sysw_search_area}:</td>
<td><select class="inp_width" name="ul_{$tarea_id}">
<option value="{$all_site}"> {$sysw_search_all_site}
{section name=section_list loop=sect}
<option value="{$sect[section_list].url}" {if ($sect[section_list].selected)}SELECTED{/if}>$sect[section_list].name
{/section}
</select></td>
</tr>

<tr>
<td align="right">{$sysw_search_where}:</td>
<td><SELECT class="inp_width" NAME="wf_{$tarea_id}">
<OPTION VALUE="2221" {if ($wf_2221)}SELECTED{/if}>{$sysw_search_all}
<OPTION VALUE="2000" {if ($wf_2000)}SELECTED{/if}>{$sysw_search_description}
<OPTION VALUE="0200" {if ($wf_0200)}SELECTED{/if}>{$sysw_search_keywords}
<OPTION VALUE="0020" {if ($wf_0020)}SELECTED{/if}>{$sysw_search_header}
<OPTION VALUE="0001" {if ($wf_0001)}SELECTED{/if}>{$sysw_search_documents}
</SELECT></td>
</tr>

<tr>
<td align="right" nowrap>{$sysw_search_results_per_page}:</td>
<td><SELECT style="width: 40px;" NAME="ps_{$tarea_id}">
<OPTION VALUE="10" {if ($ps_10)}SELECTED{/if}>10
<OPTION VALUE="20" {if ($ps_20)}SELECTED{/if}>20
<OPTION VALUE="50" {if ($ps_50)}SELECTED{/if}>50
</SELECT></td>
</tr>
<tr>
<td align="right">{$sysw_search_format}:</td>
<td><SELECT class="inp_width" NAME="wm_{$tarea_id}">
<OPTION VALUE="wrd" {if ($wm_wrd)}SELECTED{/if}>{$sysw_search_word}
<OPTION VALUE="beg" {if ($wm_beg)}SELECTED{/if}>{$sysw_search_beginning_of_word}
<OPTION VALUE="end" {if ($wm_end)}SELECTED{/if}>{$sysw_search_end_of_word}
<OPTION VALUE="sub" {if ($wm_sub)}SELECTED{/if}>{$sysw_search_substring}
</SELECT></td>
</tr>
<tr>
<td align="right">{$sysw_search_wordform}:</td>
<td><SELECT class="inp_width" NAME="sp_{$tarea_id}">
<OPTION VALUE="1" {if ($sp_1)}SELECTED{/if}>{$sysw_search_all_wordforms}
<OPTION VALUE="0" {if ($sp_0)}SELECTED{/if}>{$sysw_search_exact}
</SELECT></td>
</tr>
<tr>
<td align="right">{$sysw_search_in_synonyms}:</td>
<td><SELECT class="inp_width" NAME="sy_{$tarea_id}">
<OPTION VALUE="1" {if ($sy_1)}SELECTED{/if}>{$sysw_yes}
<OPTION VALUE="0" {if ($sy_0)}SELECTED{/if}>{$sysw_no}
</SELECT></td>
</tr>
<tr>
<td align="right" nowrap>{$sysw_search_concurrence}:</td>
<td><SELECT class="inp_width" NAME="m_{$tarea_id}">
<OPTION VALUE="all"  {if ($m_all)}SELECTED{/if}>{$sysw_search_all_words}
<OPTION VALUE="any"  {if ($m_any)}SELECTED{/if}>{$sysw_search_any_word}
<OPTION VALUE="bool" {if ($m_bool)}SELECTED{/if}>{$sysw_search_logic}
</SELECT></td>
</tr>
<tr>
<td> </td>
<td class="button_td"><input type="submit" value="{$sysw_search}" title="{$sysw_search}" class="button"></td>
</tr>
</table>
</FORM>

{if $q}
<h1>{$sysw_search_results_title}</h1>
{$search_result}
{/if}