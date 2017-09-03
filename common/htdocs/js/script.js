$(".contacts__call, .contacts__call_big").click(function(){
	$(".call_order_form").fadeIn(300);
});
$(".call_order_form_close").click(function(){
	$(".call_order_form").hide();
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

/*
* Рейтинг звездочками в карточке товара
*/
$(".card-rating__star").mouseenter(function(){
	var rat_id = $(this).attr('data_id');
	var rat_val = $(this).attr('rating');
	$('.rating_'+rat_id+' .card-rating__star').removeClass('hover');
	$('.rating_'+rat_id+' .card-rating__star').removeClass('unhover');
	$('.rating_'+rat_id+' .card-rating__star').each(function(){
		if($(this).attr('rating') <= rat_val){
			$(this).addClass('hover');
			$(this).removeClass('unhover');
		}else{
			$(this).removeClass('hover');
			$(this).addClass('unhover');
		}
	});
});

$(".card-rating").mouseleave(function(){
	$(this).children('.card-rating__star').removeClass('hover');
	$(this).children('.card-rating__star').removeClass('unhover');
});
// *******************