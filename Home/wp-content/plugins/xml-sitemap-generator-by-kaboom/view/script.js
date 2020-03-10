$=jQuery
var api_token = $('.test-api-token').attr('api_token')

if ($('.old-ids').val()){
  $.each($('.old-ids').val().split(','), function(index,value){
    $('ol.crawl li[post_id="'+value+'"]').addClass('selected cant-change')
  })  
}

$('.fa-question-circle').click(function(){
  $('.more-info-table').slideUp()
  var q = $(this).attr('question')
  $('.more-info-table[question="' + q + '"]').slideDown()
})

$('.more-info-table a.close').click(function(e){
  e.preventDefault()
  $('.more-info-table').slideUp()
})

$('input.exclude').click(function(){
  $(this).removeAttr('readonly')
})

$('input.exclude').keyup(function(){
  var val = $(this).val()
  if (val.length > 1){
    $('ol.crawl li').each(function(){
      if ($(this).is(":contains(" + val + ")")){
        $(this).addClass('selected')
        var html = $(this)[0].outerHTML
        $('ol.disabled').prepend(html)
        $(this).remove()
      } else {
        if ( $(this).hasClass('cant-change') ){
        } else {
          $(this).removeClass('selected')          
          var html = $(this)[0].outerHTML        
          $('ol.enabled').prepend(html)
          $(this).remove()
        }
      }
    })
  } else {
    $('ol.crawl li:not(.cant-change)').removeClass('selected')
    $('ol.disabled li:not(.cant-change)').each(function(){
      $(this).removeClass('selected')
      var html = $(this)[0].outerHTML
      $('ol.enabled').prepend(html)
      $(this).remove()
    })
  }
  ids = []
  $('ol.crawl li.selected').each(function(){
    ids.push($(this).attr('post_id'))
  })
  $('.old-ids').val(ids.join(','))    
})
$.each($('.exclude_post_types').val().split(','), function(index,value){
  $('.post-type-activation[post-type="' + value + '"]').removeClass('disabled')
})

$('.post-type-activation:not(.disabled)').click(function(){
  $(this).addClass('disabled')
  val = []
  $('.post-type-activation:not(.disabled)').each(function(){
    val.push($(this).attr('post-type'))
  })

  $('.exclude_post_types').val(val.join(','))
  $('form#submit-for-exclude-ids').submit()
})

$('.post-type-activation.disabled').click(function(){
  $(this).removeClass('disabled')
  val = []
  $('.post-type-activation:not(.disabled)').each(function(){
    val.push($(this).attr('post-type'))
  })

  $('.exclude_post_types').val(val.join(','))
  $('form#submit-for-exclude-ids').submit()    
})

$('.remove-exclude').click(function(e){
  e.preventDefault()
  $('.old-ids').val(" ")
  $('form#submit-for-exclude-ids').submit()
})

$(document).on('click', 'ol.crawl li', function(e){
  e.preventDefault()
  $(this).toggleClass('selected')
  $('.safe-exclude').html('Safe exluded pages (unsaved changes!)')
  ids = []
  $('ol.crawl li.selected').each(function(){
    ids.push($(this).attr('post_id'))
  })
  $('.old-ids').val(ids.join(','))

  $('.crawl.disabled li:not(.selected)').each(function(){
    var html = $(this)[0].outerHTML
    $('ol.enabled').prepend(html)
    $(this).remove()
  }) 

  $('.crawl.enabled li.selected').each(function(){
    var html = $(this)[0].outerHTML
    $('ol.disabled').prepend(html)
    $(this).remove()
  })   
})
$(document).on('click', 'ol.crawl li a', function(e){
  e.preventDefault()
})