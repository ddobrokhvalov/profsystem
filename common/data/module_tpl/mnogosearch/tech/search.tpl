<!--

    This is default template file for mnoGoSearch 3.2
    (C) 1999-2001, mnoGoSearch developers team <devel@mnogosearch.org>

    Please rename to search.htm and edit as desired.
    See doc/msearch-templates.html for detailed information.
    You may want to keep the original file for future reference.

    WARNING: Use proper chmod to protect your passwords!
-->
<!--variables

Include "indexer.conf"
HLBeg <b><font color="#000000">
HLEnd </font></b>
-->

<!--top-->
{$sysw_search_inquiry}: $&(q)
<!--
-->
<!--/top-->
<!--restop-->
<span class="or">{$sysw_search_result_time} <b>$(SearchTime)</b> {$sysw_search_result_seconds}.<BR>
{$sysw_search_result_documents} $(first)-$(last) {$sysw_search_result_from} <B>$(total)</B> {$sysw_search_result_found}.</span><BR>
<BR>
<!--/restop-->
<!--res-->

  <a href="$(URL)" TARGET="_blank"><!IF NAME="Title" CONTENT="">$(URL:40)<!ELSE>$&(Title)<!ENDIF></a>
  <small>[<b>$(Score)</b>]</small>
<BR>
$&(Body)...<BR>

<BR>
<!--/res-->

<!--resbot-->
<!-- start of nav -->
<table width=100% bgcolor=#FFFFFF>
  <tr>
    <td align=center>
      <TABLE BORDER=0>
        <TR><TD>$(NL)$(NB)$(NR)</TD></TR>
      </TABLE>
    </td>
  </tr>
</table>
<!-- end of nav -->
<!--/resbot-->

<!--clone-->
<!--/clone-->

<!--navleft-->
<TD><A HREF="$(NH)" class="s"><img src="/common/img/arr1l.gif" width="11" height="9" border="0" alt=""> {$sysw_search_result_back}</A></TD>
<!--/navleft-->

<!--navleft_nop-->
<!-- start page nav -->
<TD class="s"><img src="/common/img/arr1l.gif" width="11" height="9" border="0" alt=""> <b>{$sysw_search_result_back}</b></TD>
<!--/navleft_nop-->

<!--navbar1-->
<TD class="s"><A HREF="$(NH)" class="s">$(NP)</A></TD>
<!--/navbar1-->

<!--navbar0-->
<TD class="s"><b>$(NP)</b></TD>
<!--/navbar0-->

<!--navright-->
<TD class="s"><A HREF="$(NH)" class="s">{$sysw_search_result_next} <img src="/common/img/arr1.gif" width="11" height="9" border="0" alt=""></TD>
<!--/navright-->

<!--navright_nop-->
<TD class="s"><b>{$sysw_search_result_next}</b> <img src="/common/img/arr1.gif" width="11" height="9" border="0" alt=""></TD>
<!-- end page nav -->
<!--/navright_nop-->

<!--notfound-->
<CENTER>
{$sysw_search_result_notfound}.
</CENTER>
<p><img src="/common/img/gr.gif" width="100%" height="1" border="0" alt="">
<!--/notfound-->

<!--noquery-->
<CENTER>
{$sysw_search_result_noquery}.
</CENTER>
<p><img src="/common/img/gr.gif" width="100%" height="1" border="0" alt="">
<!--/noquery-->

<!--error-->
<CENTER>
<FONT COLOR="#FF0000">{$sysw_search_result_error}</FONT>
<P><B>$(E)</B>
</CENTER>
<!--/error-->