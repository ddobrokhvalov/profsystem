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

/*
* Рейтинг звездочками
*/
$(".enabled_votes .card-rating__star").mouseenter(function(){
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

$(".enabled_votes .card-rating__star").click(function(){
		//alert(1);
		var data_te_obj = $(this).attr("data_te_obj");
		var data_id = $(this).attr("data_id");
		var rating = $(this).attr("rating");
		$.post("/common/ajax_vote.php", 
			{'data_te_obj':data_te_obj, 'data_id':data_id, 'rating':rating}, 
			function(data){
				var rating = JSON.parse(data);
				console.log(rating);
				var rat_count = rating.COUNT;
				var rat_val = rating.AVG_RATING;
				
				$('.rating_'+data_id+' .card-rating__star').each(function(){
					$(this).removeClass('on');
					$(this).removeClass('on');
					var thisval = $(this).attr('rating');
					if(rat_val>=thisval-0.4){
						$(this).addClass('on');
					}else if(rat_val>thisval-0.8){
						//$(this).addClass('on');
					}
				});
				$('.rating_'+data_id).removeClass('enabled_votes');
				$('.rating_'+data_id).removeClass('rating_'+data_id);
			}
		);
	});
// *******************