<div align="center" style="text-align: center;">
    <div style="margin: 25%; width: 300px; text-align: left; color: #445A54;">
        
        <div id="distributedDiv">
        	<div style="text-align: left; margin: 0px 0px 9px 0px;">
        		<img src="/common/adm/img/distributed/wait.gif" alt="" border="0" align="absmiddle" style="margin: 0px 6px 0px 1px;"/>
        		<span id="progressSpan"></span>
        	</div>
        	<div id="progressDiv"></div>
        </div>
        
        <div style="margin: 9px 0px 0px 0px;"><a href="{$back_url}">{$lang_back}</a><br/></div>
        
        <script>
        	Distributed.init( {$total}, '{$action_url}' ); Distributed.send();
        </script>
    </div>
</div>
