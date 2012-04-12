(function($) {

    function addSettingsStopWord(word) {
        var html = $('#settings_stop_words').html();
        var append = "<br/><input type='checkbox' id='stop_words' name='tagbag_options[stop_words][]' checked='checked' value='" + word + "' /> " + word;
        $('#settings_stop_words').html(html + append);
        $('#settings_input_stop_word').val("");
    }

    $(window).ready(function(){
                     
        $('#settings_add_stop_word').live('click',function(e){
            var word = $('#settings_input_stop_word').val();
            addSettingsStopWord(word);
            return false;
        });
        
        $('#settings_input_stop_word').keypress(function(e){
         
            if (e.which == 13) {
                var word = $('#settings_input_stop_word').val();
                addSettingsStopWord(word);
                e.stopPropagation();
                return false;
            }
            return true;
        })
    });
})(jQuery);


