<?php

/*
  Plugin Name: TagBag
  Plugin URI: http://www.explodybits.com/
  Description: Stop thinking about what tags you should add, forgetting to add certain tags, and typing in tags by hand.  Let Tagbag do the work for you. Just look at the list of suggestions, click the ones you want, and move on.
  Author: Birch Dunford (birch@oneuplogic.com)
  Version: 0.1
  Author URI: http://www.explodybits.com/
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */


//for Artical: Offers up Tag suggestions based on the current post content.  Tagbag first presents suggested tags based on locating existing tags with in the current post.  Tagbag then presents suggestions for new tags based on word repetition, excluding common stop words.  Both stop words and the required repetition can be modified through the Tagbag settings page.  All suggested tags are ordered Tag count and the Tag Alphabetically.   Tagbag will work with custom post types.  Be sure that Tags are included in the supports of the Custom post type and that you have check the custom post type in the Tagbag settings.
//TODO:  Some how stop splitting sugested new on space so that Prases can be displayed as well so like for "hound dog" user would see "hound", "dog" and "hound dog" 
//TODO:  Change the display to make the ranking of the tag more appearent.  Not the Font size that is played out. 
class Xb_TagBag {

    var $plugin_options = 'tagbag_options';
    var $option_post_types = 'post_types';
    var $option_stop_words = 'stop_words';
    var $option_word_count = 'word_count';
    var $word_boundry = '[^a-z-A-Z\d\-\'\_]';

    function __construct() {
        if (is_admin()) {
            add_action('plugins_loaded', array($this, 'load'));
            register_activation_hook(__FILE__, array($this, 'activate'));
            register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        }
    }

    function load() {
        if (is_admin()) {
            add_action('admin_menu', array($this, 'settings_add_page'));
            add_action('admin_init', array($this, 'settings_add_sections'));
            add_action('add_meta_boxes', array($this, 'meta_box_setup'));
            add_action('wp_ajax_tagbag_ajax_post', array($this, 'meta_box_ajax_post'));
            add_action('admin_enqueue_scripts', array($this, 'settings_add_javascript_and_style'));
        }
    }

    function activate() {
        $options = array(
            $this->option_post_types => array('post'),
            $this->option_stop_words => $this->settings_get_stop_words(),
            $this->option_word_count => 3,
        );
        add_option($this->plugin_options, $options);
    }

    function deactivate() {
        delete_option($this->plugin_options);
    }

    
    function settings_add_page() {
        add_options_page('TagBag', 'TagBag', 'manage_options', 'tagbag', array($this, 'settings_page_display'));
    }

    function settings_add_sections() {
        register_setting('tagbag_options', 'tagbag_options', array($this, 'settings_validate'));

        add_settings_section('tagbag_settings_post_types', 'Post Types', array($this, 'settings_post_types_display'), 'tagbag');
        add_settings_field($this->option_post_types, 'Available Post Types', array($this, 'settings_post_types_input'), 'tagbag', 'tagbag_settings_post_types');

        add_settings_section('tagbag_settings_new_tags', 'New Tag Suggestion', array($this, 'settings_new_tags_display'), 'tagbag');
        add_settings_field($this->option_word_count, 'Word Count', array($this, 'settings_word_count_input'), 'tagbag', 'tagbag_settings_new_tags');
        add_settings_field($this->option_stop_words, 'Stop Words', array($this, 'settings_stop_words_input'), 'tagbag', 'tagbag_settings_new_tags');
    }

    function settings_validate($input) {

        array_multisort($input['stop_words']);

        $input['word_count'] = intval($input['word_count']);

        return $input;
    }

    function settings_post_types_display() {
        echo "<p>Select the Post types that you would like to use <b>TagBag</b> with. Post types need to have at least <b>one post published</b>, before they will be available for selection</p>";
    }

    function settings_new_tags_display() {
        echo "<p><b>Word Count</b> Controls the number of times a word needs to repeated before it is suggested. Default 3<br/><b>Stop Words</b> is a list of words, character sets, and even regular expressions that are to be ignored when suggesting new tags</p>";
    }

    function settings_post_types_input() {
        $select_post_types = $this->settings_get_plugin_options($this->option_post_types);
        $post_types = $this->settings_get_available_post_types();

        if (is_array($post_types)) {
            $html = array();
            foreach ($post_types as $post_type) {
                $html[] = "<input type='checkbox' id='$this->option_post_types' name='tagbag_options[$this->option_post_types][]'" . checked(in_array($post_type, $select_post_types), true, false) . "  value='$post_type' /> $post_type";
            }
            echo "<div class=''>" . implode('<br />', $html) . "</div>";
        }
    }

    function settings_word_count_input() {
        $word_count = $this->settings_get_plugin_options($this->option_word_count);
        echo "<input type='text' id='$this->option_word_count' name='tagbag_options[$this->option_word_count]' value='$word_count'/>";
    }

    function settings_stop_words_input() {
        $stop_words = $this->settings_get_plugin_options($this->option_stop_words);

        if (is_array($stop_words)) {
            $html = array();
            foreach ($stop_words as $stop_word) {
                $html[] = "<input type='checkbox' id='$this->option_stop_words' name='tagbag_options[$this->option_stop_words][]'" . checked(true, true, false) . "  value='$stop_word' /> $stop_word";
            }
            echo "<div id='settings_stop_words'>" . implode('<br />', $html) . "</div>" . "<br />" . "<input id='settings_input_stop_word' type='text'/>&nbsp<input id='settings_add_stop_word' type='button' class='button-secondary' value='Add' />";
        }
    }

    function settings_page_display() {
        include_once plugin_dir_path(__FILE__) . 'tpl/tagbag_settings.php';
    }

    function settings_get_available_post_types() {
        global $wpdb;
        $sql = "SELECT distinct post_type FROM $wpdb->posts";
        $results = $wpdb->get_results($sql);
        $post_types = array();

        if (is_array($results)) {
            foreach ($results as $post_type) {
                $post_types[] = $post_type->post_type;
            }
        }

        return $post_types;
    }

    function settings_get_stop_words() {

        return array(
            "a", "able", "about", "above", "abroad", "according", "accordingly", "across", "actually", "adj", "after", "afterwards",
            "again", "against", "ago", "ahead", "ain't", "all", "allow", "allows", "almost", "alone", "along", "alongside", "already",
            "also", "although", "always", "am", "amid", "amidst", "among", "amongst", "an", "and", "another", "any", "anybody",
            "anyhow", "anyone", "anything", "anyway", "anyways", "anywhere", "apart", "appear", "appreciate", "appropriate", "are",
            "aren't", "around", "as", "a's", "aside", "ask", "asking", "associated", "at", "available", "away", "awfully", "back",
            "backward", "backwards", "be", "became", "because", "become", "becomes", "becoming", "been", "before", "beforehand",
            "begin", "behind", "being", "believe", "below", "beside", "besides", "best", "better", "between", "beyond", "both",
            "brief", "but", "by", "came", "can", "cannot", "cant", "can't", "caption", "cause", "causes", "certain", "certainly",
            "changes", "clearly", "c'mon", "co", "co.", "com", "come", "comes", "concerning", "consequently", "consider",
            "considering", "contain", "containing", "contains", "corresponding", "could", "couldn't", "course", "c's", "currently",
            "dare", "daren't", "definitely", "described", "despite", "did", "didn't", "different", "directly", "do", "does",
            "doesn't", "doing", "done", "don't", "down", "downwards", "during", "each", "edu", "eg", "eight", "eighty", "either",
            "else", "elsewhere", "end", "ending", "enough", "entirely", "especially", "et", "etc", "even", "ever", "evermore", "every",
            "everybody", "everyone", "everything", "everywhere", "ex", "exactly", "example", "except", "fairly", "far", "farther",
            "few", "fewer", "fifth", "first", "five", "followed", "following", "follows", "for", "forever", "former", "formerly",
            "forth", "forward", "found", "four", "from", "further", "furthermore", "get", "gets", "getting", "given", "gives", "go",
            "goes", "going", "gone", "got", "gotten", "greetings", "had", "hadn't", "half", "happens", "hardly", "has", "hasn't",
            "have", "haven't", "having", "he", "he'd", "he'll", "hello", "help", "hence", "her", "here", "hereafter", "hereby",
            "herein", "here's", "hereupon", "hers", "herself", "he's", "hi", "him", "himself", "his", "hither", "hopefully", "how",
            "howbeit", "however", "hundred", "i'd", "ie", "if", "ignored", "i'll", "i'm", "immediate", "in", "inasmuch", "inc", "inc.",
            "indeed", "indicate", "indicated", "indicates", "inner", "inside", "insofar", "instead", "into", "inward", "is", "isn't",
            "it", "it'd", "it'll", "its", "it's", "itself", "i've", "just", "k", "keep", "keeps", "kept", "know", "known", "knows",
            "last", "lately", "later", "latter", "latterly", "least", "less", "lest", "let", "let's", "like", "liked", "likely",
            "likewise", "little", "look", "looking", "looks", "low", "lower", "ltd", "made", "mainly", "make", "makes", "many", "may",
            "maybe", "mayn't", "me", "mean", "meantime", "meanwhile", "merely", "might", "mightn't", "mine", "minus", "miss", "more",
            "moreover", "most", "mostly", "mr", "mrs", "much", "must", "mustn't", "my", "myself", "name", "namely", "nd", "near",
            "nearly", "necessary", "need", "needn't", "needs", "neither", "never", "neverf", "neverless", "nevertheless", "new",
            "next", "nine", "ninety", "no", "nobody", "non", "none", "nonetheless", "noone", "no-one", "nor", "normally", "not",
            "nothing", "notwithstanding", "novel", "now", "nowhere", "obviously", "of", "off", "often", "oh", "ok", "okay", "old",
            "on", "once", "one", "ones", "one's", "only", "onto", "opposite", "or", "other", "others", "otherwise", "ought", "oughtn't",
            "our", "ours", "ourselves", "out", "outside", "over", "overall", "own", "particular", "particularly", "past", "per", "perhaps",
            "placed", "please", "plus", "possible", "presumably", "probably", "provided", "provides", "que", "quite", "qv", "rather",
            "rd", "re", "really", "reasonably", "recent", "recently", "regarding", "regardless", "regards", "relatively", "respectively",
            "right", "round", "said", "same", "saw", "say", "saying", "says", "second", "secondly", "see", "seeing", "seem", "seemed",
            "seeming", "seems", "seen", "self", "selves", "sensible", "sent", "serious", "seriously", "seven", "several", "shall",
            "shan't", "she", "she'd", "she'll", "she's", "should", "shouldn't", "since", "six", "so", "some", "somebody", "someday",
            "somehow", "someone", "something", "sometime", "sometimes", "somewhat", "somewhere", "soon", "sorry", "specified", "specify",
            "specifying", "still", "sub", "such", "sup", "sure", "take", "taken", "taking", "tell", "tends", "th", "than", "thank", "thanks",
            "thanx", "that", "that'll", "thats", "that's", "that've", "the", "their", "theirs", "them", "themselves", "then", "thence",
            "there", "thereafter", "thereby", "there'd", "therefore", "therein", "there'll", "there're", "theres", "there's", "thereupon",
            "there've", "these", "they", "they'd", "they'll", "they're", "they've", "thing", "things", "think", "third", "thirty",
            "this", "thorough", "thoroughly", "those", "though", "three", "through", "throughout", "thru", "thus", "till", "to", "together",
            "too", "took", "toward", "towards", "tried", "tries", "truly", "try", "trying", "t's", "twice", "two", "un", "under",
            "underneath", "undoing", "unfortunately", "unless", "unlike", "unlikely", "until", "unto", "up", "upon", "upwards", "us",
            "use", "used", "useful", "uses", "using", "usually", "v", "value", "various", "versus", "very", "via", "viz", "vs", "want",
            "wants", "was", "wasn't", "way", "we", "we'd", "welcome", "well", "we'll", "went", "were", "we're", "weren't", "we've",
            "what", "whatever", "what'll", "what's", "what've", "when", "whence", "whenever", "where", "whereafter", "whereas",
            "whereby", "wherein", "where's", "whereupon", "wherever", "whether", "which", "whichever", "while", "whilst", "whither",
            "who", "who'd", "whoever", "whole", "who'll", "whom", "whomever", "who's", "whose", "why", "will", "willing", "wish", "with",
            "within", "without", "wonder", "won't", "would", "wouldn't", "yes", "yet", "you", "you'd", "you'll", "your", "you're", "yours",
            "yourself", "yourselves", "you've", "zero", "\d{1,2}",
        );
    }

    function settings_get_plugin_options($option_name) {
        $options = get_option($this->plugin_options);
        if (is_array($options)) {
            return $options[$option_name];
        }
    }

    function settings_add_javascript_and_style($hook) {

        if ($hook == 'post.php' | $hook == 'post-new.php' | $hook == 'settings_page_tagbag') {
            wp_enqueue_script('livequery', plugins_url('/js/livequery/jquery.livequery.js', __FILE__), array('jquery'));
            wp_enqueue_script('tagbag_metabox', plugins_url('/js/tagbag_metabox.js', __FILE__), array('jquery', 'livequery'));
            wp_enqueue_script('tagbag_settings', plugins_url('/js/tagbag_settings.js', __FILE__), array('jquery', 'livequery'));
            wp_enqueue_style('tagbag_styles', plugins_url('/css/tagbag_styles.css', __FILE__));
        }
    }

    //Meta Box Methods
    function meta_box_setup() {
        $post_types = $this->settings_get_plugin_options($this->option_post_types);
        if (is_array($post_types)) {
            foreach ($post_types as $post_type) {
                $this->meta_box_add($post_type);
            }
        }
    }

    function meta_box_add($post_type) {
        add_meta_box('tagbag', 'Tag Bag', array($this, 'meta_box_display'), $post_type, 'side', 'core', null);
    }

    function meta_box_display() {
        $tag_data = $this->tags_get_display();
        include_once plugin_dir_path(__FILE__) . 'tpl/tagbag_metabox.php';
    }

    function meta_box_ajax_post() {
        $post_id = intval($_POST['post_id']);
        $current_tags = ($_POST['tags']);

        if (!is_array($current_tags)) {
            $current_tags = array('');
        }

        echo json_encode($this->tags_get_display($current_tags, $post_id));

        die();
    }

    //Tag information Methods

    function tags_get_display($current_tags = null, $post_id = null) {

        global $post;

        if (!$post) {
            $post = get_post($post_id);
        }

        if (null == $current_tags) {
            $current_tags = $this->tags_get_current($post->ID);
        }

        $post_tags = $this->tags_get_available($current_tags);
        $post_content = $this->tags_get_post_text_content($post);

        $tag_data = new stdClass();
        $tag_data->existing_tags = $this->tags_format_for_display($this->tags_get_suggested_existing($post_tags, $post_content));
        $tag_data->new_tags = $this->tags_format_for_display($this->tags_get_suggested_new(array_merge($post_tags, $current_tags), $post_content));
        $tag_data->post_id = $post->ID;

        return $tag_data;
    }

    function tags_format_for_display($tags) {
        $html = array();
        foreach ($tags as $tag) {
            $html[] = sprintf('<a  title="add %s (found %s)" class="%s" href="#">%s</a>', trim($tag->tag), $tag->count, $this->tags_get_css_class($tag->count), trim($tag->tag));
        }

        if (count($html) == 0) {
            return "<p><i>No suggestions found.</i></p>";
        }

        return implode(" ", $html);
    }
    
    function tags_get_css_class($count) {
        
        if ($count >= 6) {
            return 'tb-hot';
        }
        
        if ($count >= 4) {
            return 'tb-medium';
        }
        
        if ($count >= 2) {
            return 'tb-mild';
        }
        
        return 'tb-mellow';
    }

    function tags_get_suggested_existing($post_tags, $post_content) {
        $regex = "/(?<=" . $this->word_boundry . ")(" . implode('|', $post_tags) . ")(" . $this->word_boundry . ")/i";
        preg_match_all($regex, $post_content, $match, PREG_PATTERN_ORDER);

        $tags = array();

        if (is_array($match)) {
            $tags = $this->tags_get_sorted_counted($match[0]);
        }

        return $tags;
    }

    function tags_get_suggested_new($exclude_tags, $post_content) {
        $regex = "/((?<=" . $this->word_boundry . ")(" . implode('|', array_merge($this->settings_get_plugin_options($this->option_stop_words), $exclude_tags)) . ")(" . $this->word_boundry . "))|" . $this->word_boundry . "/i";
        $words = preg_split($regex, $post_content);
        $tags = $this->tags_get_sorted_counted($words, intval($this->settings_get_plugin_options($this->option_word_count)));

        return $tags;
    }

    function tags_get_sorted_counted($input_tags, $count = 0) {

        $tags = array();

        if (is_array($input_tags)) {

            $counted = array_count_values(array_map(array($this, 'tags_sort_strip'), $input_tags));
            array_multisort(array_values($counted), SORT_DESC, array_keys($counted), SORT_STRING, $counted);

            foreach ($counted as $key => $val) {
                if ($val >= $count && trim($key) != '') {
                    $t = new stdClass();
                    $t->count = $val;
                    $t->tag = $key;
                    $tags[] = $t;
                }
            }
        }
        return $tags;
    }

    function tags_sort_strip($value) {
        $value = ' ' . trim($value);
        $value = preg_replace('/\.$/', '', $value);
        return ' ' . trim($value);
    }

    function tags_get_current($post_id) {
        $tags = array();
        foreach (wp_get_post_tags($post_id) as $tag) {
            $tags[] = preg_quote($tag->name);
        }

        return $tags;
    }

    function tags_get_available($current_tags) {
        $tags = array();

        foreach (get_tags(array('hide_empty' => false)) as $tag) {
            if (!in_array(strtolower($tag->name), array_map('strtolower', $current_tags))) {
                $tags[] = preg_quote($tag->name);
            }
        }

        return $tags;
    }

    function tags_get_post_text_content($post) {
        $post_content = ' ' . $post->post_title . ' ' . strip_tags($post->post_content) . ' ' . strip_tags($post->post_excerpt) . ' ';
        return preg_replace("/" . $this->word_boundry . "/", ' ', $post_content);
    }

}

$xb_tagbag = new Xb_TagBag();