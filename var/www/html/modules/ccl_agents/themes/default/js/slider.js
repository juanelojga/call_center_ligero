$(function() {
	$( "#slider-vertical" ).slider({
		orientation: "vertical",
		range: "min",
		min: 1,
		max: 99,
		value: 1,
		slide: function(event, ui) {
			$("#amount").val( ui.value );
		}
	});
	$("#amount").val($("#slider-vertical").slider("value"));
});