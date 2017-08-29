<html>
<head>
		<title>{$title}</title>
		<meta http-equiv="Content-Language" content="{$interface_lang}"/>
		<meta http-equiv="Content-Type" content="text/html; charset={$encoding}">
		<meta http-equiv="expires" content="Sun, 01 Jan 1996 07:01:00 GMT">
		<meta http-equiv="pragma" content="no-cache">
		<link rel="stylesheet" type="text/css" href="/common/adm/css/rbccontents.css"/>
		<link rel="stylesheet" type="text/css" href="/common/adm/css/rbccontents-{$font_size}.css" id="css-font"/>
		
		<script type="text/javascript" src="/common/js/core/translate.js.php?lang={$interface_lang}"></script>
		<script type="text/javascript" src="/common/js/core/check_form.js"></script>
		<script type="text/javascript" src="/common/js/core/calendar.js"></script>
		
		<script type="text/javascript" src="/common/adm/js/core/check.js"></script>
		<script type="text/javascript" src="/common/adm/js/core/manager.js"></script>
		<script type="text/javascript" src="/common/adm/js/core/interface.js"></script>
</head>

<body class="adm">

<table border="0" cellspacing="0" cellpadding="0" style="height: 100%;" width="100%">
<tr>
	<td valign="top" class="top">
    
    <!-- top -->
    
        <table border="0" cellspacing="0" cellpadding="0" width="100%">
        <tr>
        	<td>
            <!-- logo -->
            <div class="t-logo"{if $is_auth} onclick="location.href = 'index.php'" style="cursor: pointer;"{/if}>
            <!--<img src="" width="143" height="16" border="0"><br>-->
            </div>
            <!-- /logo -->
            </td>
            <td width="100%">
            <!-- top_menu -->
            <div class="top_menu">
            {if $is_auth}{$head_menu}{else}&nbsp;{/if}
            </div>
            <!-- /top_menu -->
            </td>
{if $is_auth}
            <td>
{$user_panel} 
            </td>
{/if}
            <td>
				<img src="/common/adm/img/emp.gif" width="17" height="0" border="0" alt="">
			</td>
        </tr>
        </table>
    
    <!-- /top -->
    <!-- middle -->
<!-- Favorits -->
{if $is_auth}<table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
	<td class="favorits">
		<table cellpadding="0" cellspacing="0" style="width: 100%; height: 30px">
			<tr>
				<td align="left" valign="top">
					<table cellpadding="0" cellspacing="0">
						<tr>
							<td>
								<select onchange="document.location.href = this.options[this.selectedIndex].value">
									<option value="">{$lang_favourite}</option>
{foreach from=$favourite_list item=item}
									<option value="{$item.URL|escape}" style="background: #edefee;">{$item.TITLE|escape}</option>
{/foreach}
								</select>
							</td>
							<td style="width: 50px" align="center">
								<img alt="{$lang_addfavourite}" title="{$lang_addfavourite}" src="/common/adm/img/favourite.gif" style="margin: 0px 20px 0px 5px; cursor: pointer" onclick="location.href = 'index.php?obj=FAVOURITE&action=add&url={$favourite_url|escape:'url'}&title={$favourite_title|escape:'url'}'"/>
							</td>
						</tr>
					</table>
				</td>
				<td align="left" valign="middle" style="width: 100%;">
					<div id="status_line" style="display: none;">
                        <table border="0" cellpadding="0" cellspacing="0" style="width: 100%;">
							<tr>
                                <td rowspan="3" class="status_line" style="background-color: #ffffff; padding: 2px 0px 0px 29px; vertical-align: top;">
									<img src="/common/adm/img/home.gif" width="11" height="10" border="0" alt="{$lang_home}" title="{$lang_home}" align="abstop"{if $is_auth} onclick="location.href = 'index.php'" style="cursor: pointer;"{/if}>{foreach from=$system_path item=item name=path}<img src="/common/adm/img/f-hr1.gif" width="2" height="3" border="0" alt="" align="absmiddle" style="margin: 0px 6px 0px 6px;">{if $item.URL && !$smarty.foreach.path.last}<a class="status" href="{$item.URL}">{$item.TITLE|escape}</a>{else}{$item.TITLE|escape}{/if}{/foreach}<img src="/common/adm/img/f-hr2.gif" width="5" height="5" border="0" alt="" align="absmiddle" style="margin: 0px 6px 0px 6px;">
								</td>
								<td style="width: 18px; height: 7px; font-size: 1px; background: #90b829 url( '/common/adm/img/left_arrow-bg3-q.gif' ) no-repeat; cursor: pointer; vertical-align: top; border-right: 1px solid #AED031;" onmousedown="StatusLine.next()" title="{$lang_see_help}" /><img src="/common/adm/img/left_arrow-t-q.gif" width="18" height="7" border="0" alt="{$lang_see_help}" title="{$lang_see_help}"></td>
							</tr>
                            <tr>
                                <td style="cursor: pointer; background: #90b829; border-right: 1px solid #AED031;" onmousedown="StatusLine.next()" title="{$lang_see_help}" /><img src="/common/adm/img/left_arrow-q.gif" width="18" height="9" border="0" alt="{$lang_see_help}" title="{$lang_see_help}"></td>
                            </tr>
                            <tr>
                                <td style="vertical-align: bottom; cursor: pointer; background: #90b829; border-right: 1px solid #AED031; height: 7px;" onmousedown="StatusLine.next()" title="{$lang_see_help}" /><img src="/common/adm/img/left_arrow-b-q.gif" width="18" height="7" border="0" alt="{$lang_see_help}" title="{$lang_see_help}"></td>
                            </tr>
						</table>
					</div>
					<div id="context_help_line" style="display: none">
						<table cellpadding="0" cellspacing="0" style="width: 100%; height: 20px;">
							<tr>
								<td style="width: 14px; background: url( '/common/adm/img/right_arrow-bg1.gif' ) repeat-y; cursor: pointer; vertical-align: top;" onmousedown="StatusLine.next()" title="{$lang_hidden_help}" /><img src="/common/adm/img/right_arrow-t.gif" width="14" height="7" border="0" alt="{$lang_hidden_help}" title="{$lang_hidden_help}"></td>
								<td class="status_line_help" style="background-color: #e6ebe9; padding: 3px 0px 3px 10px; vertical-align: top;" rowspan="3">
									{$context_help|default:'&nbsp;'}
								</td>
							</tr>
                            <tr>
                                <td style="background: url( '/common/adm/img/right_arrow-bg1.gif' ) repeat-y; cursor: pointer;" onmousedown="StatusLine.next()" title="{$lang_hidden_help}" /><img src="/common/adm/img/right_arrow-vector.gif" width="14" height="5" border="0" alt="{$lang_hidden_help}" title="{$lang_hidden_help}"></td>
                            </tr>
                            <tr>
                                <td style="background: url( '/common/adm/img/right_arrow-bg1.gif' ) repeat-y; cursor: pointer; vertical-align: bottom;" onmousedown="StatusLine.next()" title="{$lang_hidden_help}" ><img src="/common/adm/img/right_arrow-b.gif" width="14" height="7" border="0" alt="{$lang_hidden_help}" title="{$lang_hidden_help}"></td>
                            </tr>
						</table>
					</div>
					<script>
						var StatusLine = new StatusLine(); StatusLine.init( 'status_line', 'context_help_line' );
					</script>
				</td>
			</tr>
		</table>
</td>
</tr>
</table>        
{/if}
<!-- /Favorits -->
    
<!-- Center --><table border="0" cellspacing="0" cellpadding="0" width="100%">
<tr>
	<td class="center">
{if $is_auth}<div class="box2-tline"><div class="box2-t"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div></div>
    <div class="box2">{/if}
    		<table width="100%" height="100%" border="0" cellpadding="0" cellspacing="0">
    			<tr>
{if $system_menu}
    				<td width="0%" style="padding: 0px 5px 0px 3px" valign="top">
				        {$system_menu}
    				</td>
    				<td width="0%" onmousedown="Splitter.startResize( event )" class="splitterCell" valign="middle">
    					<div class="toggleCell" onmousedown="Splitter.toggle()"/>
			    		<script type="text/javascript">
			    			var Splitter = new Splitter(); Splitter.init( 'TreeMenu' );
			    		</script>
    				</td>
{/if}
    				<td width="100%" valign="top" style="padding: 0px 0px 0px 0px;">
						<table border="0" cellspacing="0" cellpadding="0" width="100%">
							<tr>
								<td style="padding: 0px 10px 18px 9px;"{if $is_auth}{else} align="center"{/if}>
									<div id="error" style="display: none">
<div class="spacer" style="width: 0px; height: 8px;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div>
<div class="errors-t"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
<div class="errors-box">
	<div><div class="img"><img src="/common/adm/img/messages/error.gif" alt=""></div>{$lang_user_error_message}:</div>
	<div style="padding: 5px 0px 5px 24px;"> <span id="error_msg"></span></div>
{if $is_debug}
	<div style="padding: 5px 0px 5px 24px;">{$lang_file}: <span id="error_file"></span></div>
	<div style="padding: 5px 0px 5px 24px;">{$lang_string}: <span id="error_line"></span></div>
	<div style="padding: 5px 0px 5px 24px;">{$lang_backtrace}: <span id="error_trace"></span></div>
	<div style="padding: 5px 0px 5px 24px;">{$lang_debug_info}: <span id="error_debug"></span></div>
{/if}
</div>
<div class="errors-b"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div>
<div class="spacer" style="width: 0px; height: 15px;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div>
<div><a href="javascript:history.back()">{$lang_back}</a></div>
									</div>
							        {$body}
							    </td>
							</tr>
						</table>
                        <div class="spacer" style="height: 0px; width: 600px;"><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div>
                    </td>
                    <td>
						<script type="text/javascript">
							document.write( '<div class="spacer" style=" height:' + ( screen.height - 450 ) + 'px; width: 0px;">&nbsp;</div>' ); 
						</script>
                    </td>
    			</tr>
    		</table>
{if $is_auth}</div>
<div class="box2-bline"><div class="box2-b"><div><img src="/common/adm/img/emp.gif" width="1" height="1" border="0" alt=""></div></div></div>{/if}
</td>
</tr>
</table>
<!-- /Center -->
    
    <!-- /middle -->
    </td>
</tr>
<tr valign="bottom">
	<td class="bottom">
    <!-- bottom -->
           
<table border="0" cellspacing="0" cellpadding="0">
<tr>
	<td>
        <div class="copy" style="width: 179px;">
            &copy; 2003 - <script language="JavaScript">Data = new Date; document.write(Data.getFullYear());</script>
        </div>
    </td>
    <td style="width: 100%;">
    	{if ($is_auth)}
            <table style="width: 100%;">
            <tr>
            	<td></td>
                <td style="text-align: right;"><div class="copy2"><span>{$lang_font_size}</span><img id="css-img-small" style="cursor: pointer" alt="{$lang_font_small}" title="{$lang_font_small}" onmouseover="this.setAttribute( 'src', this.getAttribute( 'srcOver' ) )" onmouseout="this.setAttribute( 'src', this.getAttribute( 'srcOut' ) )" onclick="set_font( 'small' )"/><img id="css-img-middle" style="cursor: pointer" alt="{$lang_font_middle}" title="{$lang_font_middle}" onmouseover="this.setAttribute( 'src', this.getAttribute( 'srcOver' ) )" onmouseout="this.setAttribute( 'src', this.getAttribute( 'srcOut' ) )" onclick="set_font( 'middle' )"/><img id="css-img-big" style="cursor: pointer" alt="{$lang_font_big}" title="{$lang_font_big}" onmouseover="this.setAttribute( 'src', this.getAttribute( 'srcOver' ) )" onmouseout="this.setAttribute( 'src', this.getAttribute( 'srcOut' ) )" onclick="set_font( 'big' )"/><script type="text/javascript">set_font_image( '{$font_size}' )</script></div></td>
            </tr>
            </table>
        {/if}
    </td>
</tr>
</table>

    
    
    <!-- /bottom -->
    </td>
</tr>
</table>
</body>
</html>
