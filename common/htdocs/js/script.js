$(".contacts__call, .contacts__call_big").click(function(){
	$(".call_order_form").fadeIn(300);
});
$(".call_order_form_close").click(function(){
	$(".call_order_form").hide();
});
$(".call_order_form_result .call_order_form_close").click(function(){
	if($(this).attr("rel")){
		document.location = $(this).attr("rel");
	}
});
$(".select-view-item__s").click(function(){
	$(".select-view-item__s").addClass("active");
	$(".select-view-item__p").removeClass("active");
	$(".items").addClass("mode_list");
});
$(".select-view-item__p").click(function(){
	$(".select-view-item__p").addClass("active");
	$(".select-view-item__s").removeClass("active");
	$(".items").removeClass("mode_list");
});