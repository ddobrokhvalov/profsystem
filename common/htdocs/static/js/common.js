/*
* Рейтинг звездочками
*/
$(".item-rating__star").mouseenter(function(){
	var rat_id = $(this).attr('data_id');
	var rat_val = $(this).attr('rating');
	$('.rating_'+rat_id+' .item-rating__star').removeClass('hover');
	$('.rating_'+rat_id+' .item-rating__star').removeClass('unhover');
	$('.rating_'+rat_id+' .item-rating__star').each(function(){
		if($(this).attr('rating') <= rat_val){
			$(this).addClass('hover');
			$(this).removeClass('unhover');
		}else{
			$(this).removeClass('hover');
			$(this).addClass('unhover');
		}
	});
});

$(".item-rating").mouseleave(function(){
	$(this).children('.item-rating__star').removeClass('hover');
	$(this).children('.item-rating__star').removeClass('unhover');
});
// *******************

/*
* Листалка статей
*/
obj = {};
obj.articles = $(".main-page-article");
obj.c = 1;
obj.l = obj.articles.length;
obj.w =  $(obj.articles[0]).width();
obj.o = $(".mp-articles");
obj['o'].width(obj.w*obj.l+"px");

$(".articles__next").click(function(){
	var ml;
	if (obj.c<obj.l) {
		ml = -obj.c*obj.w;
		obj['o'].animate({marginLeft: ml+"px"});
		obj.c++;
	};
});
$(".articles__prev").click(function(){
	var ml;
	if (obj.c>1) {
		ml = -(obj.c-2)*obj.w;
		obj['o'].animate({marginLeft: ml+"px"});
		obj.c--;
	};
});

// *******************
