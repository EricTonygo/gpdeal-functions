<?php

/* * *************************************** The Custom Wordpress Rewrite Rule******************************************************************* */

//CPT Posts
function posts_cpt_generating_rule($wp_rewrite) {
    $rules = array();

    $post_type = 'post';

    $rules['blog/' . '/([^/]*)$'] = 'index.php?post_type=' . $post_type . '&post=$matches[1]&name=$matches[1]';
    // merge with global rules
    $wp_rewrite->rules = $rules + $wp_rewrite->rules;
}

/* * ******************************************************************************************************************************************** */

//The Post Link
function change_link($permalink, $post) {
    if ($post->post_type == 'post') {
        $permalink = get_home_url() . "/blog/" . '/' . $post->post_name;
    }
    return $permalink;
}
