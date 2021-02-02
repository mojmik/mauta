 function saveAndAdd() {
			var $butt=jQuery('button.editor-post-publish-button__button');
			var customPost=jQuery('#post_type').val();
			var url='./post-new.php?post_type='+customPost;
			
			jQuery('button.editor-post-publish-button__button').click();  
            setTimeout(function() {				
        				window.location=url;
        			}, 2000);	
            return false;  
		 }