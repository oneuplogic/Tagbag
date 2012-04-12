(function($) {
        
    function getCurrentTags() {

        var tags = new Array();
                        
        $('a[id^=post_tag-check-num]').each(function(index){
            var txt = $.trim($(this).parent().clone().children().remove().end().text());
            tags.push(txt);
        });
            
        return tags;
    }
    
    function removeExistingTag(tag) {        
        $('#tagbag_tags a').each(function(index){
            if ($.trim($(this).text().toLowerCase()) == $.trim(tag).toLowerCase()) {
                $(this).remove();
            }
            
        })
        
    }
          
    $(window).ready(function(){
                    
        $('a[id^=post_tag-check-num]').livequery('click',function(e){
            
            var data = {
                action: 'tagbag_ajax_post',
                post_id: $('#tagbag_post_id').val(),
                'tags[]': getCurrentTags()
            };
                
            $.post(ajaxurl, data, function(response) {
                var tag_data = $.parseJSON(response);
                $('#tagbag_existing').html(tag_data.existing_tags);
                $('#tagbag_new').html(tag_data.new_tags);                    
            });
                
            return false;
        }); 
            
        $('#tagbag_tags a').livequery('click',function(e) {
            
            $('#new-tag-post_tag').val($(this).text());
            $('.tagadd').click();      
            return false;
        });
        
        $('.tagadd').livequery('click',function(e){
            var t = $('#new-tag-post_tag').val();
            removeExistingTag(t);
            return false;
        });
        
        $('#new-tag-post_tag').keypress(function(e){
            if (e.which == 13) {
                var t = $('#new-tag-post_tag').val();
                removeExistingTag(t);
            }
        })
        
        $('a[class^=tag-link]').livequery('click',function(e){
            var t = $(this).text();
            removeExistingTag(t);
            return false;
        });

    });
        
        
})(jQuery);

