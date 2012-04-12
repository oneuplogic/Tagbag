<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>



<p class="howto">Existing <b>Tags</b><p>  
<div id="tagbag_tags">
    <input id="tagbag_post_id" type="hidden" value="<?php echo $tag_data->post_id ?>" />
    <div id="tagbag_existing" class="the-tagcloud">
        <?php echo $tag_data->existing_tags; ?>
    </div>
    <p class="howto">New <b>Tags</b><p>
    <div id="tagbag_new" class="the-tagcloud">
        <?php echo $tag_data->new_tags; ?>
    </div>
</div>
