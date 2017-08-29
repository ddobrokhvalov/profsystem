<?xml version="1.0" encoding="{$encoding}"?>
<rss version="2.0">
	<channel>
		<title>{$title}</title>
		<description>{$description}</description>
		<lastBuildDate>{$date}</lastBuildDate>
		<link>{$link}</link>
		<generator>{$generator}</generator>
{foreach from=$list item=item}
		<item>
			<title>{$item.title}</title>
			<link>{$item.link}</link>
			<guid>{$item.link}</guid>
			<description><![CDATA[{$item.description}]]></description>
			<pubDate>{$item.pub_date}</pubDate>
		</item>
{/foreach}
	</channel>
</rss>