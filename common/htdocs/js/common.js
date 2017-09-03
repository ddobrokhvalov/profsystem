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
/*
* Листалка изображений
*/
img_slider = {};
img_slider.o = $(".card-img");
img_slider.images = $(".card-img-s");
img_slider.c = 1;
img_slider.k = 3;
img_slider.l = img_slider.images.length;
img_slider.h =  $(img_slider.images[0]).height();

img_slider['o'].height(img_slider.h*img_slider.l+"px");

img_slider.img_next = function(){
	var ml;
	if (img_slider.c<img_slider.k) {
		ml = -(img_slider.c+1)*img_slider.h;
		img_slider['o'].animate({marginTop: ml+"px"});
		img_slider.c = img_slider.c+2;
	};
};
img_slider.img_prev = function(){
	var ml;
	if (img_slider.c>1) {
		ml = -(img_slider.c-3)*img_slider.h;
		img_slider['o'].animate({marginTop: ml+"px"});
		img_slider.c= img_slider.c-2;
	};
};
img_slider.check =  function(){
	if (img_slider.l>img_slider.k) {
		if (img_slider.c>1) {
			$('.card-img-prev').show();
		}else{
			$('.card-img-prev').hide();
		};
		if (img_slider.c<img_slider.k) {
			$('.card-img-next').show();
		}else{
			$('.card-img-next').hide();
		};

	}else{
		$('.card-img-prev').hide();
		$('.card-img-next').hide();
	};
};
img_slider.check();

$('.card-img-next').click(function() {
	img_slider.img_next();
	img_slider.check();
});
$('.card-img-prev').click(function() {
	img_slider.img_prev();
	img_slider.check();
});

$('.card-img-s img').click(function(){
	var src = $(this).attr('src');
	$('.card-img-b img').attr('src',src);
	$('a#fancy_image').attr('href',src);
});
$("a#fancy_image").fancybox();

// *******************
