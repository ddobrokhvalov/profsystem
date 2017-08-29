{if $TITLE}
<h3>{$TITLE}</h3>
{$BODY}
<br><br>

<div><b>{$sysw_vote_variants}:</b></div>
{if $ANSWER_LIST}
<script type="text/javascript" language="javascript" src="/common/js/core/translate.js.php?lang={$env.lang_root_dir}"></script>
<script type="text/javascript" language="javascript" src="/common/js/core/check_form.js"></script>
<form action="" method="post" name="vote_form" onsubmit="return CheckForm.validate( this )">
{foreach from=$ANSWER_LIST item=item name=fields}
	<input type="{if $VOTE_TYPE == 'single'}radio{else}checkbox{/if}" name="answer_{$env.area_id}[]" value="{$item.VOTE_ANSWER_ID}" lang="errors{if $VOTE_TYPE == 'single'}_radio_{else}_checkboxgroup_{/if}"{if $IS_VOTED} disabled="disabled"{/if}{if $smarty.foreach.fields.first} checked="checked"{/if}>{$item.TITLE}<br>
{/foreach}
	<br>
	<input type="hidden" name="vote_{$env.area_id}" value="{$VOTE_ID}">
	<input type="submit" value="{$sysw_vote_send}"{if $IS_VOTED} disabled="disabled"{/if} class="button">
</form>
{if $IS_VOTED}<span style="color: red">{$sysw_vote_is_voted}</span>{/if}
{else}
    <div>{$sysw_vote_no_answers}</div>
{/if}
{else}
    <div>{$sysw_vote_no_question}</div>
{/if}

<div class="sub-links">
{if $result_link}
<a href="{$result_link}">{$sysw_vote_results}</a>
{/if}
{if $archives_link}
<br><a href="{$archives_link}">{$sysw_vote_archives}</a>
{/if}
</div>