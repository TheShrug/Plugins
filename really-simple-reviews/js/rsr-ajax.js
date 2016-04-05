jQuery(document).ready(function($) {

	$(document).on( 'click', '.rsr-reviews.simple span', function( event ) {
		var rating = $(this).attr('data-rating');
		var postid = $(this).parent().attr('data-id');
		$.ajax({
			url: ajax_object.ajax_url,
			type: 'post',
			data: {
				action: 'rsr_ajax',
				rating: rating,
				postid: postid
			},
			beforeSend: function() {
				$('.rsr-reviews').remove();
			},
			success: function( html ) {
				$('.review-container').append(html);
			}
		})
	});

	$(document).on('click', '.rsr-full-review-form button', function(event) {
		// Prevent Form non-ajax submission
		event.preventDefault();

		// Get values for submission
		var rating = $('.star-container .givenRating').attr('data-rating');
		var name = $('.rsr-full-review-form input[name="name"]').val();
		var text = $('.rsr-full-review-form textarea[name="text"]').val();
		var postid = $('.rsr-reviews').attr('data-id');

		// Errors
		if (rating == null) {
			$('.star-container').addClass('error');
		} else {
			$('.star-container').removeClass('error');
		}
		if (name == '') {
			$('.rsr-full-review-form input[name="name"]').addClass('error');
		} else {
			$('.rsr-full-review-form input[name="name"]').removeClass('error');
		}
		if (text == '') {
			$('.rsr-full-review-form textarea[name="text"]').addClass('error');
		} else {
			$('.rsr-full-review-form textarea[name="text"]').removeClass('error');
		}
		// End Errors

		// if no errors lets do some ajax to enter into database
		if (rating != null && name != '' && text != '') {

			console.log('no errors');
			$.ajax({
				url: ajax_object.ajax_url,
				type: 'post',
				data: {
					action: 'rsr_ajax_full',
					rating: rating,
					postid: postid,
					name:   name,
					text:   text
				},
				beforeSend: function() {
					$('.rsr-reviews').remove();
				},
				success: function( html ) {
					$('.review-container').html('Thank you for your submission. Your review has been submitted for approval.');
				}
			});

		}


	});

	$('.star-container span').on('click', function() {
		$(this).siblings().removeClass('givenRating');
		$(this).prevAll('span').children('.fa').removeClass('fa-star-o').addClass('fa-star');
		$(this).children('.fa').removeClass('fa-star-o').addClass('fa-star');
		$(this).nextAll('span').children('.fa').removeClass('fa-star').addClass('fa-star-o');
		$(this).addClass('givenRating');
	});

});