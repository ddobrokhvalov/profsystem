{literal}
	<script type="text/javascript" src="/common/js/jquery.nicescroll.js"></script>
	<script type="text/javascript">
		$(document).ready(function(){
			$(".user_disclaimer_text").niceScroll({cursorborder:"",cursorcolor:"#737373",boxzoom:false, autohidemode: false});
			$(".user_disclaimer__close").click(function(){
				$(".user_disclaimer_popup").hide();;
			});
		});
	</script>
{/literal}
<div class="user_disclaimer_popup">
	<div class="user_disclaimer__close"></div>
	<div class="user_disclaimer_text_wrap">
		<div class="user_disclaimer_text">
			{if $TITLE}<h2>{$TITLE}</h2>{/if}
			{$BODY}
		</div>
	</div>
</div>