jQuery(document).ready(function($) {

	if (jQuery('.datepicker').datepicker) {
    	jQuery('.datepicker').datepicker({format:'yyyy-mm-dd',autoclose: true});
    }
		
}); //jQuery(document).ready(...)

function onOffSocialRadio(controlId) {
	jQuery('#'+controlId+'-on').click(function(){
		//alert('onOffSocialRadio - on');
		jQuery(this).addClass('btn-success');
		jQuery(this).addClass('active');
		jQuery('#'+controlId+'-off').removeClass('btn-danger');
		jQuery('#'+controlId+'-off').removeClass('active');
		jQuery('#'+controlId+'-hidden').val('true');
	});
	jQuery('#'+controlId+'-off').click(function(){
		//alert('onOffSocialRadio - off');
		jQuery(this).addClass('btn-danger');
		jQuery(this).addClass('active');
		jQuery('#'+controlId+'-on').removeClass('btn-success');
		jQuery('#'+controlId+'-on').removeClass('active');
		jQuery('#'+controlId+'-hidden').val('false');
	});	
}

function deleteLogItem(id) {
	if (id!=null && id!='') {
		if (confirm('Are you sure you want to re-schedule this post?')) {
			var data = {
					who: 'slm',
					action: 'ajax_delete_log_item',
					id: encodeURIComponent(id)
				};
			jQuery('#submission-wait-loading-wait').show();
			jQuery.post(ajaxurl, data, function(response) {			
				if (response!=null) {
					jQuery('#submission-wait-loading-wait').hide();
					alert('Done');
				}
			});
		}
	}	
}
