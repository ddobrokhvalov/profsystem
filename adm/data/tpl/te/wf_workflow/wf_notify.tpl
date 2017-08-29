<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">

<html>
<head>
	<title>{$lang_resolution_reminder}</title>
    <style type="text/css">
{literal}
html { height: 100%;}
body.adm {
	height: 100%;
    width: 100%;
	color: #757E7B !important; 
	font-size: 11px;
	font-family: Arial, Helvetica, sans-serif;
	background-color: #ffffff;
	
	margin: 0px;
	padding: 0px;
}

.adm div, .adm span, .adm td, .adm input, .adm select, .adm div li, {
	font-size: 11px;
	font-family: Arial, Helvetica, sans-serif;
    color: #445a54 !important;
}

.adm div a:link, .adm div a:active, .adm div a:visited {
  color: #0465a4;
  text-decoration: underline;
} 
.adm div a:hover {
  color: #0465a4;
  text-decoration: none; 
}   
/*   Copyrights  */ 
.adm td.bottom {
{/literal}
   background: url({$bottom_background_src}) repeat-x bottom;
{literal}
}
.adm div.copy {
  font-family: Tahoma, Helvetica, sans-serif;
  font-size: 10px;
  color: #aab3b0;
  margin: 44px 0px 0px 0px;
/*  padding: 0px 21px 28px 21px; */
  white-space: nowrap;
}
.adm div.copy a:link, .adm div.copy a:active, .adm div.copy a:visited {
   font-family: Tahoma, Helvetica, sans-serif;
   font-size: 10px;
   color: #0068b3;
   white-space: nowrap;
   text-decoration: underline;
}
.adm div.copy a:hover {
   font-family: Tahoma, Helvetica, sans-serif;
   font-size: 10px;
   color: #0068b3;
   white-space: nowrap;
   text-decoration: none;
}


.adm h1 {
   font-family: Arial, Helvetica, sans-serif;
   font-size: 18px;
   color: #445954;
}

.adm h2 {
   font-family: Arial, Helvetica, sans-serif;
	font-size: 18px;
   font-weight: normal;
   color: #445954;
}

.adm h3 {
   font-family: Arial, Helvetica, sans-serif;
	font-size: 14px;
   color: #445954;
}
.adm h4 {
   font-family: Arial, Helvetica, sans-serif;
   font-size: 12px;
   color: #445954;
}
.adm h5 {
   font-family: Arial, Helvetica, sans-serif;
	font-size: 10px;
   color: #445954;
}

.adm a {
	color: #0069b1;
}

.adm a:hover {
	color: #618a14;
}

.adm img {
	border: none;
}

.adm form {
	margin: 0px;
	padding: 0px;
}
/*   Template top  */
.adm td.top {
{/literal}
   background: url({$top_background_src}) repeat-x 0px 0px;
{literal}
}
.adm .t-logo {
/*   padding: 12px 39px 12px 18px; */
}
{/literal}

    </style>
</head>
<body class="adm" style="font-family: Arial; font-size: 11px; color: #445a54;">
<table border="0" cellspacing="0" cellpadding="0" style="height: 100%;" width="100%">
<tr>
	<td valign="top" class="top" style="background: url({$top_background_src}) repeat-x 0px 0px;">
    <!-- top -->
    
        <table border="0" cellspacing="0" cellpadding="0" width="100%">
        <tr>
        	<td background="{$top_background_src}">
            <!-- logo -->
            <div class="t-logo" style="padding: 12px 39px 12px 18px;">
            <img src="{$logo_src}" width="143" height="16" border="0" alt="RBC CONTENTS 5.0" title="RBC CONTENTS 5.0">
            </div>
            <!-- /logo -->
            </td>
        </tr>
        </table>
    
    <!-- /top -->
    <!-- middle -->
    <div style="padding: 20px 18px 0px 19px; font-size: 12px;">
    
        <h1>{$lang_resolution_document} "{$document_title|escape}" {$lang_resolution_go_to_state} "{$resolution.WF_STATE2_TITLE}"</h1>
        
        <p>
            <b>{$lang_resolution}:</b> "{$resolution.TITLE}"<br>
            <b>{$lang_resolution_date}:</b> {$smarty.now|date_format:"%Y-%m-%d %H:%M:%S"}
        </p>
        
        <h3>{$lang_administrators}:</h3>
        
        <ul>
        
            {foreach from=$administrators item=administrator name=administrators}
                <li><b>{$smarty.foreach.administrators.iteration}. {$administrator.SURNAME}</b> {if $administrator.PRIVILEGE_TITLE}<span style="font-weight: normal; color: #757e7b;"> ({$administrator.PRIVILEGE_TITLE})</span>{/if}
                
                {if $administrator.COMMENTS}
                    <div style="font-size: 10px; font-family: Tahoma, Helvetica, sans-serif; padding: 3px 0px 5px 13px; margin: 0;"><b>{$lang_resolution_comment}:</b><br> {$administrator.COMMENTS|escape|nl2br}</div>
                {/if}
                </li>
            {/foreach}
        
        </ul>
        
        <p><div><a href="{$table_url}">{$lang_resolution_document_table}</a></div></p>
    
    </div>
    <!-- /middle -->
    </td>
</tr>
<tr style="vertical-align: bottom;">
	<td class="bottom" style="padding: 0px 21px 28px 21px; background: url({$bottom_background_src}) repeat-x bottom;">
    <!-- bottom -->
           
    <table border="0" cellspacing="0" cellpadding="0">
    <tr>
    	<td>
            <div class="copy" style="width: 179px;">
                &copy; 2003 - 2007 <a href="http://rbcsoft.ru/">RBC SOFT</a>
            </div>
        </td>
    </tr>
    </table>
    
    <!-- /bottom -->
    </td>
</tr>
</table>
</body>
</html>
