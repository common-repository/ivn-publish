 jQuery(document).ready(function($) {
 	$('#ivn-push-all-form').submit(function() {

 		$('#ivn_loading').show();
 		$('#ivn_submit').attr('disabled', true);

 		data = {
 			action: 'ivn_get_results',
 			ivn_nonce: ivn_vars.ivn_nonce
 		};
 	

 		$.post(ajaxurl, data, function (response) {
 			$('#ivn_results').html(response);
 			$('#ivn_loading').hide();
 			$('#ivn_submit').attr('disabled', false);
 		});

 		return false;

 	});


    $("#ivn_button").click( function(){
    

		$('#ivn_button').fadeOut("fast");
        $('#ivn_loading_post').show();
 		var hv = $('#post_id_hidden').val();

 		data = {
 			action: 'ivn_get_results_post',
 			ivn_nonce: ivn_vars.ivn_nonce,
 			ivn_post_id: hv
 		};
 	

 		$.post(ajaxurl, data, function (response) {
 			$('#ivn_results_post').html(response);
 			$('#ivn_loading_post').hide();
 			
 		});

 		return false;
  
    });

	$('.ivn_post_button').live('click', function(){
		
		var edit_id = $(this).attr('edit_id');
		$('.ivn_post_button').fadeOut("fast");
		$('#ivn_loading_post_row_' + edit_id).show();

 		data = {
 			action: 'ivn_get_results_post',
 			ivn_nonce: ivn_vars.ivn_nonce,
 			ivn_post_id: edit_id
 		};
 	

 		$.post(ajaxurl, data, function (response) {
 			$('#ivn_results_post_' + edit_id).html(response);
 			$('#ivn_loading_post_row_' + edit_id).hide();
 			
 		});
	
		return false;
	});


 });