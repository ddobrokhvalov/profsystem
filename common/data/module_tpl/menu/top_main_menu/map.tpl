<ul>
{foreach from=$ITEMS item="LEVEL1" name="LEVEL1"}
	<li>
	{if $smarty.foreach.LEVEL1.first}{/if}
	<a href="{$LEVEL1.URL}">{$LEVEL1.TITLE}</a>

	{* Второй уровень *}
	{if $LEVEL1.CHILDREN}
		<ul>
		{foreach from=$LEVEL1.CHILDREN item="LEVEL2" name="LEVEL2"}
			<li>
			{if $smarty.foreach.LEVEL2.first}{/if}
			<a href="{$LEVEL2.URL}">{$LEVEL2.TITLE}</a>

			{* Третий уровень *}
			{if $LEVEL2.CHILDREN}
				<ul>
				{foreach from=$LEVEL2.CHILDREN item="LEVEL3" name="LEVEL3"}
					<li>
					{if $smarty.foreach.LEVEL3.first}{/if}
					<a href="{$LEVEL3.URL}">{$LEVEL3.TITLE}</a>
						{* Четвертый уровень иерархии размещать здесь*}
                    </li>
				{/foreach}
				</ul>
			{/if}
			{* /Третий уровень *}
            </li>
		{/foreach}
		</ul>
	{/if}
	{* /Второй уровень *}
    </li>
{/foreach}
</ul>