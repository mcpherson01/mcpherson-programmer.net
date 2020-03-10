$=jQuery
$(document).on('change', 'select#exclude_ids.async', function(e){
  value = $(this).attr('value')
  message = $(this).find('option:selected').attr('message')  
  html = '<div class="notice notice-success">' + message + '</div>'
  url = '/wp-admin/index.php?update_exclude_ids&ids=' + value
  $.ajax({
    url: url,
    method: 'GET',
    success: function(){
      $('.kaboom-alert').html(html)
    }
  })
})