jQuery(document).ready(function() {

    // Check & synchronize config of CDN account
    jQuery(document).on( 'click', '#shift8-cdn-check', function(e) {
        jQuery(".shift8-cdn-spinner").show();
        e.preventDefault();
        var button = jQuery(this);
        var url = button.attr('href');
        jQuery.ajax({
            url: url,
            dataType: 'json',
            data: {
                'action': 'shift8_cdn_push',
                'type': 'check'
            },
            success:function(data) {
                // This outputs the result of the ajax request
                if (data != null) {
                    jQuery('#shift8_cdn_api_field').val(data.apikey);
                    jQuery('#shift8_cdn_prefix_field').val(data.cdnprefix);
                }
                jQuery('.shift8-cdn-response').html('Values have been checked & re-populated from CDN settings.').fadeIn();
                setTimeout(function(){ jQuery('.shift8-cdn-response').fadeOut() }, 25000);
                jQuery(".shift8-cdn-spinner").hide();               
            },
            error: function(errorThrown){
                console.log('Error : ' + JSON.stringify(errorThrown));
                jQuery('.shift8-cdn-response').html(errorThrown.responseText).fadeIn();
                setTimeout(function(){ jQuery('.shift8-cdn-response').fadeOut() }, 5000);
                jQuery(".shift8-cdn-spinner").hide();
            }
        }); 
    });
});


function Shift8CDNCopyToClipboard(containerid) {
    if (document.selection) { 
        var range = document.body.createTextRange();
        range.moveToElementText(document.getElementById(containerid));
        range.select().createTextRange();
        document.execCommand("copy"); 

    } else if (window.getSelection) {
        var range = document.createRange();
         range.selectNode(document.getElementById(containerid));
         window.getSelection().addRange(range);
         document.execCommand("copy");
         alert("text copied") 
    }
}
