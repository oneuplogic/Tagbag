<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<style>
    #settings_stop_words
    {
        max-height: 300px;
        overflow: auto;
        background-color: white;
        border: #DFDFDF 1px solid;
        padding: 10px;
    }
</style>
<div class="wrap">
<?php screen_icon(); ?>
<h2>TagBag Settings</h2>
<form action="options.php" method="post">  
<?php settings_fields('tagbag_options'); ?> 
<?php do_settings_sections('tagbag'); ?>   
<br/>
<input name="Submit" type="submit" value="Save Changes" class="button-primary"/> 
</form>
</div>
