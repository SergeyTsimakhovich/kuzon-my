//poll__block dots=======================
$('.poll__dots-item').click(function(event){
	if ($(this).hasClass('poll__dots-item_acive')) return;
	$('.poll__dots-item.poll__dots-item_acive').removeClass('poll__dots-item_acive');
	$(this).toggleClass('poll__dots-item_acive');
});