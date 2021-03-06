<?php
/*
  Plugin Name: Global Parcel Deal Custom functions
  Description: L'ensemble des fonctions globales du site.
  Version: 0.1
  License: GPL
  Author: Eric TONYE
  Author URI: https://gpdeal.com/
 */

use Themosis\Facades\Action;
use Themosis\Facades\User;
use Themosis\Facades\Section;
use Themosis\Facades\Field;
use Themosis\Facades\Metabox;
use src\Gpdeal\TransportOffer;

require_once 'src/Gpdeal/POI.php';
require_once 'src/Gpdeal/TransportOffer.php';
require 'paypal/gpdeal-paypal-functions.php';
//require 'wp_rewrite_rules/posts_rewrite_rule.php';

$sitekey = '6LfoxhcUAAAAAL3L_vo5dnG1csXgdaYYf5APUTqn'; // votre clé publique

add_action('after_setup_theme', 'my_theme_supports');

/* * ******************************Change the text domain ********************************************** */
// CHANGE LOCAL LANGUAGE
// must be called before load_theme_textdomain()
//add_filter( 'locale', 'my_theme_localized' );
//function my_theme_localized( $locale )
//{
//	if ( isset( $_GET['l'] ) )
//	{
//		return sanitize_key( $_GET['l'] );
//	}
//        do_action( 'pll_language_defined', 'en', pll_current_language(get_locale()));
//	return $locale;
//}
//load_theme_textdomain( 'gpdealdomain', get_template_directory() . '/languages' );


add_action('init', 'my_custom_init');

function wpse66093_no_admin_access() {
    $redirect = home_url('/');
    //exit(wp_redirect($redirect));
    if (is_admin() && !current_user_can('manage_options') && !wp_doing_ajax()) {
        exit(wp_redirect($redirect));
    }
}

add_action('admin_init', 'wpse66093_no_admin_access', 100);

function my_awesome_mail_content_type() {
    return "text/html";
}

function text_domain_setup() {
    load_theme_textdomain('gpdealdomain', get_template_directory() . '/languages');
}

add_filter("wp_mail_content_type", "my_awesome_mail_content_type");

function wpb_sender_email($original_email_address) {
    if ($original_email_address == 'wordpress@test.gpdeal.com' || $original_email_address == 'wordpress@gpdeal.com') {
        return get_bloginfo('admin_email');
    } else {
        return $original_email_address;
    }
}

// Function to change sender name
function wpb_sender_name($original_name_from) {
    if (strtolower($original_name_from) == "wordpress") {
        return get_bloginfo('name');
    } else {
        return $original_name_from;
    }
}

// Hooking up our functions to WordPress filters
add_filter('wp_mail_from', 'wpb_sender_email');
add_filter('wp_mail_from_name', 'wpb_sender_name');

//Action for notifing user for new post available
add_action("publish_post", "gpdeal_publication_notification");

add_action("post_updated", "gpdeal_publication_notification");

function gpdeal_publication_notification($post_ID) {
    if ('publish' != get_post_status($post_ID)) {
        return false;
    }
    $post = get_post($post_ID);
    $post_type = get_post_type($post);
    if ('transport-offer' == $post_type) {
        gpdeal_send_notification_unsatisfied_package_user($post_ID);
    }
}

//This function Un-quotes a quoted string even if it is more than one
function removeslashes($string) {
    $string = implode("", explode("\\", $string));
    return stripslashes(trim($string));
}

add_filter('auth_cookie_expiration', 'my_expiration_filter', 99, 3);

function my_expiration_filter($expiration, $user_id, $remember) {

    //if "remember me" is checked;
    if ($remember) {
        //WP defaults to 2 weeks;
        $expiration = 14 * 24 * 60 * 60; //UPDATE HERE;
    } else {
        //WP defaults to 48 hrs/2 days;
        $expiration = 2 * 24 * 60 * 60; //UPDATE HERE;
    }

    //http://en.wikipedia.org/wiki/Year_2038_problem
    if (PHP_INT_MAX - time() < $expiration) {
        //Fix to a little bit earlier!
        $expiration = PHP_INT_MAX - time() - 5;
    }

    return $expiration;
}

function woocommerce_support() {
    add_theme_support('woocommerce');
}

function childtheme_formats() {
    //Enable a support thumbnail
    add_theme_support('post-thumbnails');
    add_theme_support('post-formats', array('aside', 'gallery', 'link'));
}

function my_theme_supports() {
    //woocommerce_support();
    childtheme_formats();
    remove_theme_supports();
    text_domain_setup();
}

function remove_theme_supports() {
    //remove_post_type_support('package', 'editor');
    //remove_post_type_support('transport-offer', 'editor');
    /* ----------------------------------------------------------------------------- */
    //Prevent wordpress to display version of wordpress installation
    /* ----------------------------------------------------------------------------- */
    remove_action('wp_head', 'wp_generator');
}

add_action('show_user_profile', 'my_show_extra_profile_fields');
add_action('edit_user_profile', 'my_show_extra_profile_fields');

function my_show_extra_profile_fields($user) {
    ?>
    <?php if (is_super_admin(get_current_user_id())): ?>
        <h3><?php _e("Identity information", "gpdealdomain"); ?></h3>
        <table class="form-table">
            <tr>
                <th><label for="identity_status"><?php _e("Link of identity file", "gpdealdomain"); ?></label></th>
                <td>
                    <?php
                    $identity_file_id = get_user_meta($user->ID, 'identity-file-ID', true);
                    if ($identity_file_id):
                        ?>
                        <a  href="<?php echo wp_get_attachment_url($identity_file_id); ?>" target="_blank"><?php echo basename(get_attached_file($identity_file_id)); ?> </a>
                    <?php else: ?>
                        <span style="color: red"><?php _e("No identity file", "gpdealdomain") ?></span>
                    <?php endif ?>
                </td>
            </tr>
            <tr>
                <th><label for="identity_status"><?php _e("Identity status", "gpdealdomain"); ?></label></th>
                <td>
                    <select name="identity_status">
                        <option value="">Select an identity status</option>
                        <option value="1" <?php if (get_user_meta($user->ID, 'identity-status', true) == 1): ?> selected="selected"<?php endif ?>><?php _e("Verification in Progress", "gpdealdomain"); ?></option>
                        <option value="2" <?php if (get_user_meta($user->ID, 'identity-status', true) == 2): ?> selected="selected"<?php endif ?>><?php _e("Not verified", "gpdealdomain"); ?></option>
                        <?php if ($identity_file_id): ?>
                            <option value="3" <?php if (get_user_meta($user->ID, 'identity-status', true) == 3): ?> selected="selected"<?php endif ?>><?php _e("Verified", "gpdealdomain"); ?></option>
                        <?php endif ?>
                    </select>
                </td>
            </tr>
            <?php if ($identity_file_id): ?>
                <tr>
                    <th>
                        <label for="card_identity_number"><?php _e("Card Identity Number", "gpdealdomain"); ?></label>
                    </th> 
                    <td>
                        <input type="text" name="card_identity_number" id="card_identity_number" class="input" value="<?php echo get_user_meta($user->ID, 'card-identity-number', true) ?>" size="25" />
                    </td>
                </tr>
            <?php endif ?>
        </table>
    <?php endif ?>
    <?php
}

add_action('personal_options_update', 'my_save_extra_profile_fields');
add_action('edit_user_profile_update', 'my_save_extra_profile_fields');

function my_save_extra_profile_fields($user_id) {
    if (!current_user_can('edit_user', $user_id))
        return false;
    /* Copy and paste this line for additional fields. Make sure to change 'twitter' to the field ID. */
    update_user_meta($user_id, 'identity-status', intval(esc_attr(wp_unslash($_POST['identity_status']))));
    update_user_meta($user_id, 'card-identity-number', esc_attr(wp_unslash($_POST['card_identity_number'])));
}

//Add additional role customer for every user because we want to use it in woocommerce
add_action('user_register', 'custom_registration_user_function', 10, 1);

function custom_registration_user_function($user_id) {
    add_secondary_role($user_id);
    //gpdeal_send_activate_link($user_id);
}

function add_secondary_role($user_id) {

    $user = get_user_by('id', $user_id);
    $user->add_role('customer');
}

function is_user_in_role($user_id, $role) {
    return in_array($role, get_user_roles_by_user_id($user_id));
}

function gpdeal_send_activate_link($user_id) {
    $hash = sha1(uniqid(mt_rand(), true));
    update_user_meta($user_id, 'hash', $hash);
    update_user_meta($user_id, 'activate', 1);
    $user_data = get_userdata($user_id);
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: Global Parcel Deal <infos@gpdeal.com>';
    $headers[] = 'Bcc:<erictonyelissouck@yahoo.fr>';

    $subject = "Global Parcel Deal - " . __("Activation of account", "gpdealdomain");
    ob_start();
    ?>

    <div style="font-size: 12.8px;"><?php _e("Welcome to Global Parcel Deal", "gpdealdomain"); ?> !</div>
    <div><br></div>
    <div style="font-size: 12.8px;"><?php _e("You have just registered on our site and we thank you for your confidence", "gpdealdomain"); ?>. </div>
    <div><br></div>
    <div style="font-size: 12.8px;"><?php _e("For security reasons, you must activate your account by clicking on the activation link opposite", "gpdealdomain"); ?> <a href="<?php echo esc_url(add_query_arg(array('id' => $user_data->user_login, 'key' => get_user_meta($user_data->ID, "hash", true)), get_permalink(get_page_by_path(__('activate-your-account', 'gpdealdomain'))))); ?>"><?php _e("activate my global parcel deal account", "gpdealdomain"); ?></a>.</div>
    <div><br></div>
    <div style="font-size: 12.8px;"><?php _e("Do not hesitate to <a href='mailto:contact@gpdeal.com'>contact us</a> if you encounter difficulties during this activation", "gpdealdomain"); ?> !</div>
    <div><br></div>
    <div>
        <p style="margin:0px;padding:0px;border:0px;font-size:12.8px;font-stretch:normal;line-height:normal;font-family:Tahoma;width:auto;height:auto;float:none;color:rgb(0,0,0)"><?php _e("Best regards", "gpdealdomain"); ?>,</p>
        <p style="margin:0px 0px 1em;padding:0px;border:0px;font-size:12.8px;font-stretch:normal;line-height:normal;font-family:Tahoma;width:auto;height:auto;float:none;color:rgb(0,0,0)"><?php _e("The team", "gpdealdomain"); ?> Global Parcel Deal</p>
        <p><a href="<?php echo home_url('/'); ?>"><img src="<?php echo get_template_directory_uri() ?>/assets/images/logo_gpdeal.png" style="width: 115px;"></a></p>
    </div>

    <?php
    $body = ob_get_contents();
    ob_end_clean();
    wp_mail($user_data->user_email, $subject, $body, $headers);
}

//add_action('profile_update', 'custom_profile_update_function', 10, 2);
//
//function custom_profile_update_function($user_id, $old_user_data) {
//    gpdeal_send_activate_link($user_id);
//}

/* * **************************Customize email send to user when his email change********************************************* */
// define the send_email_change_email callback 
function filter_send_email_change_email($true, $user, $userdata) {
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: Global Parcel Deal <infos@gpdeal.com>';
    $headers[] = 'Bcc:<erictonyelissouck@yahoo.fr>';

    $subject = "Global Parcel Deal - " . __("Notification of change of e-mail address", "gpdealdomain");
    ob_start();
    ?>

    <div style="font-size: 12.8px;"><?php _e("Hello", "gpdealdomain"); ?> <?php echo $user['user_login'] ?> !</div>
    <div><br></div>
    <div style="font-size: 12.8px;"><?php _e("The modification of your e-mail address has been considered on our Global Parcel Deal website", "gpdealdomain"); ?>. </div>
    <div><br></div>
    <div style="font-size: 12.8px;"><?php _e("The e-mail registered for your transactions is now", "gpdealdomain"); ?> <a href="mailto:<?php echo $userdata['user_email']; ?>"><?php echo $userdata['user_email']; ?></a>.</div>
    <div><br></div>
    <div style="font-size: 12.8px;"><?php _e("If you are not the owner of this action, please contact the site administrator at", "gpdealdomain"); ?> <a href="mailto:<?php echo get_bloginfo("admin_email"); ?>"><?php echo get_bloginfo("admin_email"); ?></a></div>
    <div><br></div>
    <div style="font-size: 12.8px;"><?php _e("Thank you for your loyalty", "gpdealdomain"); ?>.</div>
    <div><br></div>
    <div>
        <p style="margin:0px;padding:0px;border:0px;font-size:12.8px;font-stretch:normal;line-height:normal;font-family:Tahoma;width:auto;height:auto;float:none;color:rgb(0,0,0)"><?php _e("Best regards", "gpdealdomain"); ?>,</p>
        <p style="margin:0px 0px 1em;padding:0px;border:0px;font-size:12.8px;font-stretch:normal;line-height:normal;font-family:Tahoma;width:auto;height:auto;float:none;color:rgb(0,0,0)"><?php _e("The team", "gpdealdomain"); ?> Global Parcel Deal</p>
        <p><a href="<?php echo home_url('/'); ?>"><img src="<?php echo get_template_directory_uri() ?>/assets/images/logo_gpdeal.png" style="width: 115px;"></a></p>
    </div>
    <?php
    $body = ob_get_contents();
    ob_end_clean();
    wp_mail($user['user_email'], $subject, $body, $headers);
}

// add the filter 
add_filter('send_email_change_email', 'filter_send_email_change_email', 10, 3);

/* * ************************************************************************************************************************* */

//Check whether a user has a specifique role
function get_user_roles_by_user_id($user_id) {
    $user = get_userdata($user_id);
    return empty($user) ? array() : $user->roles;
}

function get_user_role_by_user_id($user_id) {
    $user = get_userdata($user_id);
    $roles = $user->roles;
    if (in_array('particular', $roles)) {
        return __('Particular', 'gpdealdomain');
    } elseif (in_array('professional', $roles)) {
        return __('Professional', 'gpdealdomain');
    } elseif (in_array('enterprise', $roles)) {
        return __('Professional', 'gpdealdomain');
    } else {
        return "";
    }
}

//Get a role of user (particular, professional or enterprise
function get_role_of_user($user_id) {
    $user = get_userdata($user_id);
    return empty($user) ? array() : $user->roles;
}

/* * ****************************************Customize user registration form ****************************************************** */


/* * ******************************************************************************************************************************** */

function post_type_transport_offer_init() {
    $labels = array(
        'name' => __('Transport offers', 'post type general name', 'gpdealdomain'),
        'singular_name' => _x('Transport offer', 'post type singular name', 'gpdealdomain'),
        'menu_name' => _x('Transport offers', 'admin menu', 'gpdealdomain'),
        'name_admin_bar' => _x('Transport offer', 'add new on admin bar', 'gpdealdomain'),
        'add_new' => _x('Add New', 'transport-offer', 'gpdealdomain'),
        'add_new_item' => __('Add New Transport offer', 'gpdealdomain'),
        'new_item' => __('New Transport offer', 'gpdealdomain'),
        'edit_item' => __('Edit Transport offer', 'gpdealdomain'),
        'view_item' => __('View Transport offer', 'gpdealdomain'),
        'all_items' => __('All Transport offers', 'gpdealdomain'),
        'search_items' => __('Search Transport offers', 'gpdealdomain'),
        'parent_item_colon' => __('Parent Transport offers:', 'gpdealdomain'),
        'not_found' => __('No transport offers found.', 'gpdealdomain'),
        'not_found_in_trash' => __('No transport offers found in Trash.', 'gpdealdomain')
    );

    $args = array(
        'labels' => $labels,
        'description' => __('This is a post type for the transport offer.', 'gpdealdomain'),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'delete_with_user' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'transport-offer'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title', 'author', 'thumbnail', 'excerpt', 'comments')
    );

    register_post_type('transport-offer', $args);
}

function post_type_city_init() {
    $labels = array(
        'name' => _x('Cities', 'post type general name', 'gpdealdomain'),
        'singular_name' => _x('City', 'post type singular name', 'gpdealdomain'),
        'menu_name' => _x('Cities', 'admin menu', 'gpdealdomain'),
        'name_admin_bar' => _x('City', 'add new on admin bar', 'gpdealdomain'),
        'add_new' => _x('Add New', 'transport-offer', 'gpdealdomain'),
        'add_new_item' => __('Add New City', 'gpdealdomain'),
        'new_item' => __('New City', 'gpdealdomain'),
        'edit_item' => __('Edit City', 'gpdealdomain'),
        'view_item' => __('View City', 'gpdealdomain'),
        'all_items' => __('All Cities', 'gpdealdomain'),
        'search_items' => __('Search Cities', 'gpdealdomain'),
        'parent_item_colon' => __('Parent Cities:', 'gpdealdomain'),
        'not_found' => __('No city found.', 'gpdealdomain'),
        'not_found_in_trash' => __('No city found in Trash.', 'gpdealdomain')
    );

    $args = array(
        'labels' => $labels,
        'description' => __('This is a post type for the city.', 'gpdealdomain'),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'delete_with_user' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'city'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title')
    );

    register_post_type('city', $args);
}

function post_type_term_use_init() {
    $labels = array(
        'name' => _x('Terms of use', 'post type general name', 'gpdealdomain'),
        'singular_name' => _x('Term of use', 'post type singular name', 'gpdealdomain'),
        'menu_name' => _x('Terms of use', 'admin menu', 'gpdealdomain'),
        'name_admin_bar' => _x('Term of use', 'add new on admin bar', 'gpdealdomain'),
        'add_new' => _x('Add New', 'term-use', 'gpdealdomain'),
        'add_new_item' => __('Add New Term of use', 'gpdealdomain'),
        'new_item' => __('New Term of use', 'gpdealdomain'),
        'edit_item' => __('Edit Term of use', 'gpdealdomain'),
        'view_item' => __('View Term of use', 'gpdealdomain'),
        'all_items' => __('All Terms of use', 'gpdealdomain'),
        'search_items' => __('Search Terms of use', 'gpdealdomain'),
        'parent_item_colon' => __('Parent Terms of use:', 'gpdealdomain'),
        'not_found' => __('No terms of use found.', 'gpdealdomain'),
        'not_found_in_trash' => __('No terms of use found in Trash.', 'gpdealdomain')
    );

    $args = array(
        'labels' => $labels,
        'description' => __('This is a post type for the term of use.', 'gpdealdomain'),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'delete_with_user' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'term-use'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title', 'editor', 'author', 'thumbnail', 'excerpt')
    );

    register_post_type('term-use', $args);
}

function create_transport_offer_taxonomies() {
// Add new taxonomy, make it hierarchical (like categories)
    $labels = array(
        'name' => _x('Type of packages', 'taxonomy general name', 'gpdealdomain'),
        'singular_name' => _x('Type of package', 'taxonomy singular name', 'gpdealdomain'),
        'search_items' => __('Search Type of packages', 'gpdealdomain'),
        'all_items' => __('All Type of packages', 'gpdealdomain'),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __('Edit Type of package', 'gpdealdomain'),
        'update_item' => __('Update Type of package', 'gpdealdomain'),
        'add_new_item' => __('Add New Type of package', 'gpdealdomain'),
        'new_item_name' => __('New Type of package Name', 'gpdealdomain'),
        'menu_name' => __('Type of package', 'gpdealdomain'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_admin_column' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'rewrite' => array('slug' => 'type_package'),
    );

    register_taxonomy('type_package', array('transport-offer', 'package'), $args);

// Add new taxonomy, NOT hierarchical (like tags)
    $labels = array(
        'name' => _x('Transport Methods', 'taxonomy general name', 'gpdealdomain'),
        'singular_name' => _x('Transport Method', 'taxonomy singular name', 'gpdealdomain'),
        'search_items' => __('Search Transport Methods', 'gpdealdomain'),
        'popular_items' => __('Popular Transport Methods', 'gpdealdomain'),
        'all_items' => __('All Transport Methods', 'gpdealdomain'),
        'parent_item' => __('Parent Transport Method', 'gpdealdomain'),
        'parent_item_colon' => __('Parent Transport Method', 'gpdealdomain'),
        'edit_item' => __('Edit Transport Method', 'gpdealdomain'),
        'update_item' => __('Update Transport Method', 'gpdealdomain'),
        'add_new_item' => __('Add New Transport Method', 'gpdealdomain'),
        'new_item_name' => __('New Transport Method Name', 'gpdealdomain'),
        'menu_name' => __('Transport Methods', 'gpdealdomain'),
    );

    $args = array(
        'hierarchical' => true,
        'labels' => $labels,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'rewrite' => array('slug' => 'transport-method'),
    );

    register_taxonomy('transport-method', 'transport-offer', $args);


    // Add new taxonomy, hierarchical (like tags)
    $labels = array(
        'name' => _x('Portable Objets', 'taxonomy general name', 'gpdealdomain'),
        'singular_name' => _x('Portable Objet', 'taxonomy singular name', 'gpdealdomain'),
        'search_items' => __('Search Portable Objets', 'gpdealdomain'),
        'popular_items' => __('Popular Portable Objets', 'gpdealdomain'),
        'all_items' => __('All Portable Objets', 'gpdealdomain'),
        'parent_item' => __('Parent Portable Objet', 'gpdealdomain'),
        'parent_item_colon' => __('Parent Portable Objets', 'gpdealdomain'),
        'edit_item' => __('Edit Portable Objet', 'gpdealdomain'),
        'update_item' => __('Update Portable Objet', 'gpdealdomain'),
        'add_new_item' => __('Add New Portable Objet', 'gpdealdomain'),
        'new_item_name' => __('New Portable Objets Name', 'gpdealdomain'),
        'menu_name' => __('Portable Objets', 'gpdealdomain'),
    );

    $args = array(
        'hierarchical' => false,
        'labels' => $labels,
        'show_ui' => true,
        'show_in_rest' => true,
        'show_admin_column' => true,
        'update_count_callback' => '_update_post_term_count',
        'query_var' => true,
        'rewrite' => array('slug' => 'portable-object'),
    );

    register_taxonomy('portable-object', 'package', $args);
}

function post_type_package_init() {
    $labels = array(
        'name' => _x('Packages', 'post type general name', 'gpdealdomain'),
        'singular_name' => _x('Package', 'post type singular name', 'gpdealdomain'),
        'menu_name' => _x('Packages', 'admin menu', 'gpdealdomain'),
        'name_admin_bar' => _x('Package', 'add new on admin bar', 'gpdealdomain'),
        'add_new' => _x('Add New', 'package', 'gpdealdomain'),
        'add_new_item' => __('Add New Package', 'gpdealdomain'),
        'new_item' => __('New Package', 'gpdealdomain'),
        'edit_item' => __('Edit Package', 'gpdealdomain'),
        'view_item' => __('View Package', 'gpdealdomain'),
        'all_items' => __('All Packages', 'gpdealdomain'),
        'search_items' => __('Search Packages', 'gpdealdomain'),
        'parent_item_colon' => __('Parent Packages:', 'gpdealdomain'),
        'not_found' => __('No packages found.', 'gpdealdomain'),
        'not_found_in_trash' => __('No packages found in Trash.', 'gpdealdomain')
    );

    $args = array(
        'labels' => $labels,
        'description' => __('This is a post type for the package.', 'gpdealdomain'),
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'delete_with_user' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'package'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title', 'author', 'thumbnail', 'excerpt', 'comments')
    );

    register_post_type('package', $args);
}

function post_type_question_init() {
    $labels = array(
        'name' => _x('Questions', 'post type general name', 'gpdealdomain'),
        'singular_name' => _x('Question', 'post type singular name', 'gpdealdomain'),
        'menu_name' => _x('Questions', 'admin menu', 'gpdealdomain'),
        'name_admin_bar' => _x('Question', 'add new on admin bar', 'gpdealdomain'),
        'add_new' => _x('Add New', 'question', 'gpdealdomain'),
        'add_new_item' => __('Add New Question', 'gpdealdomain'),
        'new_item' => __('New Question', 'gpdealdomain'),
        'edit_item' => __('Edit Question', 'gpdealdomain'),
        'view_item' => __('View Question', 'gpdealdomain'),
        'all_items' => __('All Questions', 'gpdealdomain'),
        'search_items' => __('Search Questions', 'gpdealdomain'),
        'parent_item_colon' => __('Parent Questions:', 'gpdealdomain'),
        'not_found' => __('No questions found.', 'gpdealdomain'),
        'not_found_in_trash' => __('No questions found in Trash.', 'gpdealdomain')
    );

    $args = array(
        'labels' => $labels,
        'description' => __('This is a post type for the question.', 'gpdealdomain'),
        'public' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'delete_with_user' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'question'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title')
    );

    register_post_type('question', $args);
}

function post_type_evaluation_init() {
    $labels = array(
        'name' => _x('Evaluations', 'post type general name', 'gpdealdomain'),
        'singular_name' => _x('Evaluation', 'post type singular name', 'gpdealdomain'),
        'menu_name' => _x('Evaluations', 'admin menu', 'gpdealdomain'),
        'name_admin_bar' => _x('Evaluation', 'add new on admin bar', 'gpdealdomain'),
        'add_new' => _x('Add New', 'evaluation', 'gpdealdomain'),
        'add_new_item' => __('Add New Evaluation', 'gpdealdomain'),
        'new_item' => __('New Evaluation', 'gpdealdomain'),
        'edit_item' => __('Edit Evaluation', 'gpdealdomain'),
        'view_item' => __('View Evaluation', 'gpdealdomain'),
        'all_items' => __('All Evaluations', 'gpdealdomain'),
        'search_items' => __('Search Evaluations', 'gpdealdomain'),
        'parent_item_colon' => __('Parent Evaluations:', 'gpdealdomain'),
        'not_found' => __('No evaluations found.', 'gpdealdomain'),
        'not_found_in_trash' => __('No evaluations found in Trash.', 'gpdealdomain')
    );

    $args = array(
        'labels' => $labels,
        'description' => __('This is a post type for the evaluation.', 'gpdealdomain'),
        'public' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'delete_with_user' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'evaluation'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title', 'author', 'thumbnail', 'excerpt', 'comments')
    );

    register_post_type('evaluation', $args);
}

//Disable password reset from wp-login.php page
function disable_password_reset() {
    return false;
}

//Remove Reset lost password link form wp-login page
function remove_lostpassword_text($text) {
    if ($text == 'Lost your password?' || $text == 'Mot de passe oublié ?') {
        $text = '';
    }
    return $text;
}

//Prevent access to wp-login 
function custom_login_page() {
 global $pagenow;
 if( $pagenow == "wp-login.php" && $_SERVER['REQUEST_METHOD'] == 'GET') {
    wp_redirect(home_url( '/' ));
    exit;
 }
}

//Disable password reset from wp-login.php page
add_filter('allow_password_reset', 'disable_password_reset');
//Remove Reset lost password link form wp-login page
add_filter('gettext', 'remove_lostpassword_text');

function my_custom_init() {
    add_role('particular', __('Particular', 'gpdealdomain'), array('read' => true, 'publish_posts' => true, 'edit_posts' => true));
    add_role('professional', __('Professional', 'gpdealdomain'), array('read' => true, 'publish_posts' => true, 'edit_posts' => true));
    //add_role('enterprise', __('Enterprise', 'gpdealdomain'), array('read' => true, 'publish_posts' => true, 'edit_posts' => true));
    post_type_transport_offer_init();
    post_type_package_init();
    post_type_question_init();
    post_type_evaluation_init();
    post_type_term_use_init();
    post_type_city_init();
    create_transport_offer_taxonomies();
    //addUserCustomsField();
    add_my_featured_image_to_home();
    custom_login_page();
}

//The Custom Wordpress Rewrite Rule
//add_filter('generate_rewrite_rules', 'posts_cpt_generating_rule');

//The Posts Link
//add_filter('post_type_link',"change_link",10,2);

function get_published_questions() {
    $posts = query_posts(array(
        'post_type' => 'question',
        'posts_per_page' => -1,
        'post_status' => 'publish'
    ));

    $questions = array();
    foreach ($posts as $post) {
        $questions[$post->ID] = $post->post_title;
    }

    wp_reset_query();
    return $questions;
}

//Add user Customs fields for Security informations
function addUserCustomsField() {
    $user = User::addSections([
                Section::make('user-security-infos', 'Informations de sécurité')
    ]);
    $questions = get_published_questions();
    $user->addFields([
        'user-security-infos' => [
            Field::select('test-question-ID', array($questions), array(
                'title' => __('Question test', 'gpdealDomain'),
            )),
            Field::text('answer-test-question', ['title' => __('Réponse à la question test', 'gpdealDomain')])
        ]
    ]);
}

//Add user Customs fields for Home page (Slider Images)
function add_my_featured_image_to_home() {
    $home = (int) get_option('page_on_front');
    if (themosis_is_post($home)) {
        remove_post_type_support('page', 'editor');
        Metabox::make("Informations à la une de la page d'accueil", 'page')->set(array(
            Field::media('my-featured-image', ['title' => __("L'image à la une ", 'gpdealdomain')]),
            Field::text('promotional-text', ['title' => __('Texte de promotion', 'gpdealdomain')]),
            Field::text('button-text', ['title' => __('Texte sur le button', 'gpdealdomain')])
        ));
    }
}

function upload_file($file = array(), $parent_post_id = 0) {
    require_once( ABSPATH . 'wp-admin/includes/admin.php' );
    $file_return = wp_handle_upload($file, array('test_form' => false));
    if (isset($file_return['error']) || isset($file_return['upload_error_handler'])) {
        return false;
    } else {
        $filename = $file_return['file'];
        $attachment = array(
            'post_mime_type' => $file_return['type'],
            'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
            'post_content' => '',
            'post_status' => 'inherit',
            'guid' => $file_return['url']
        );

        $attachment_id = wp_insert_attachment($attachment, $file_return['url'], $parent_post_id);

        require_once(ABSPATH . 'wp-admin/includes/image.php');
        $attachment_data = wp_generate_attachment_metadata($attachment_id, $filename);
        wp_update_attachment_metadata($attachment_id, $attachment_data);

        if (0 < intval($attachment_id)) {
            return $attachment_id;
        }
    }
    return false;
}

//Fonction to verify user in grecaptcha
function verify_use_grecaptcha($codesecurity) {
    $privatekey = '6LfoxhcUAAAAAKfy_FPsm9L3reQoUs7oE0y32M2m'; // votre clé privée
    $post_data = "secret=" . $privatekey . "&response=" .
            $codesecurity . "&remoteip=" . $_SERVER['REMOTE_ADDR'];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://www.google.com/recaptcha/api/siteverify");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded; charset=utf-8',
        'Content-Length: ' . strlen($post_data)));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $googresp = curl_exec($ch);
    $decgoogresp = json_decode($googresp);
    curl_close($ch);

    return $decgoogresp->success;
}

//Function of registration user account in gpdead front-end website.
function register_user() {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        if (isset($_POST['testunicity']) && $_POST['testunicity'] = 'yes' && $_POST['g-recaptcha-response']) {
            if (!verify_use_grecaptcha($_POST['g-recaptcha-response'])) {
                $json = array("message" => __("We could not verify your security code. Verify it and try again", "gpdealdomain"));
                return wp_send_json_error($json);
            }
            $user_login = removeslashes(esc_attr(trim($_POST['username'])));
            $user_email = removeslashes(esc_attr(trim($_POST['email'])));
            $unique_user_email = get_user_by('email', $user_email);
            $unique_user_login = get_user_by('login', $user_login);
            if ($unique_user_login) {
                $json = array("message" => __("A user with this username already exists, please change it", "gpdealdomain"));
                return wp_send_json_error($json);
            } elseif ($unique_user_email) {
                $json = array("message" => __("A user with this e-mail already exists please change it", "gpdealdomain"));
                return wp_send_json_error($json);
            } else {
                $json = array("message" => __("Add is possible", "gpdealdomain"));
                return wp_send_json_success($json);
            }
        }
    } elseif (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['g-recaptcha-response-register'])) {
//        if (!verify_use_grecaptcha($_POST['g-recaptcha-response-register'])) {
//            // What happens when the CAPTCHA was entered incorrectly
//            $_SESSION['error_message'] = "Nous n'avons pas pu verifier votre code de sécurité. Verifiez le puis essayez à nouveau :".$_POST['g-recaptcha-response-register'];
//        } else {
        $role = removeslashes(esc_attr(trim($_POST['role'])));
        $user_login = removeslashes(esc_attr(trim($_POST['username'])));
        $user_pass = esc_attr($_POST['password']);
        $user_email = removeslashes(esc_attr(trim($_POST['email'])));
        $new_user_data = array(
            'user_login' => $user_login,
            'user_pass' => $user_pass,
            'user_email' => $user_email,
            'role' => $role
        );
        $user_id = wp_insert_user($new_user_data);
        $receive_notifications = removeslashes(esc_attr(trim($_POST['receive_notifications'])));
        if ($receive_notifications && $receive_notifications == 'on') {
            update_user_meta($user_id, 'receive-notifications', 'yes');
        } else {
            update_user_meta($user_id, 'receive-notifications', 'no');
        }
        update_user_meta($user_id, 'registration-completed', 1);
        update_user_meta($user_id, 'identity-status', 0);
        if (!is_wp_error($user_id)) {
            gpdeal_send_activate_link($user_id);
            $_SESSION['success_registration_message_title'] = __("Your account has been created successfully", "gpdealdomain");
            $_SESSION['success_registration_message_content'] = __("For security reasons, an activation link has been sent to the e-mail address indicated. Click on this link to log in", "gpdealdomain");
            wp_safe_redirect(get_permalink(get_page_by_path(__("confirmation-registration", 'gpdealdomain'))));
            exit;
        } else {
            $_SESSION['faillure_process'] = __("An error occurred while creating your account", "gpdealdomain");
            wp_safe_redirect(get_permalink(get_page_by_path(__('registration', 'gpdealdomain'))));
            exit;
        }
//        }
    } else {
        $_SESSION['error_message'] = __("Security code not found", "gpdealdomain");
    }
}

//Function of Updating registred user account in gpdead front-end website.
function update_user($user_id) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        if (isset($_POST['testunicity']) && $_POST['testunicity'] = 'yes') {
            $user_login = removeslashes(esc_attr(trim($_POST['username'])));
            $user_email = removeslashes(esc_attr(trim($_POST['email'])));
            $unique_user_email = get_user_by('email', $user_email);
            $unique_user_login = get_user_by('login', $user_login);
            if ($unique_user_login && $unique_user_login->ID != $user_id) {
                $json = array("message" => __("A user with this username already exists, please change it", "gpdealdomain"));
                return wp_send_json_error($json);
            } elseif ($unique_user_email && $unique_user_email->ID != $user_id) {
                $json = array("message" => __("A user with this e-mail already exists please change it", "gpdealdomain"));
                return wp_send_json_error($json);
            } else {
                $json = array("message" => __("Updating is possible", "gpdealdomain"));
                return wp_send_json_success($json);
            }
        }
    } elseif (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST') {
        $role = removeslashes(esc_attr(trim($_POST['role'])));
        if ($role == "particular") {
            $user_login = removeslashes(esc_attr(trim($_POST['username'])));
            $user_email = removeslashes(esc_attr(trim($_POST['email'])));
            $first_name = removeslashes(esc_attr(trim($_POST['first_name'])));
            $last_name = removeslashes(esc_attr(trim($_POST['last_name'])));
            $gender = removeslashes(esc_attr(trim($_POST['gender'])));
            $number_street = removeslashes(esc_attr(trim($_POST['number_street'])));
            $complement_address = removeslashes(esc_attr(trim($_POST['complement_address'])));
            $locality = removeslashes(esc_attr(trim($_POST['locality'])));
            $postal_code = removeslashes(esc_attr(trim($_POST['postal_code'])));
            $country_region_city = getCountryRegionCityInformations($locality);
            $mobile_phone_country_code = removeslashes(esc_attr(trim($_POST['mobile_phone_country_code'])));
            $mobile_phone_number = removeslashes(esc_attr(trim($_POST['mobile_phone_number'])));
            $test_question_ID = removeslashes(esc_attr(trim($_POST['test_question'])));
            $answer_test_question = removeslashes(esc_attr(trim($_POST['answer_test_question'])));
            $receive_notifications = removeslashes(esc_attr(trim($_POST['receive_notifications'])));
            $profile_picture_id = removeslashes(esc_attr(trim($_POST['profile_picture_id'])));
            $identity_file_id = removeslashes(esc_attr(trim($_POST['identity_file_id'])));

            $update_user_data = array(
                'ID' => $user_id,
                //'user_login' => $user_login,
                //'user_pass' => $user_pass,
                'user_email' => $user_email,
                'role' => $role,
                'first_name' => $first_name,
                'last_name' => $last_name
            );

            $user_id = wp_update_user($update_user_data);
            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'gender', $gender);
                update_user_meta($user_id, 'postal-code', $postal_code);
                update_user_meta($user_id, 'number-street', $number_street);
                update_user_meta($user_id, 'complement-address', $complement_address);
                update_user_meta($user_id, 'country', $country_region_city['country']);
                update_user_meta($user_id, 'region-province-state', $country_region_city['region']);
                update_user_meta($user_id, 'commune-city-locality', $country_region_city['city']);
                update_user_meta($user_id, 'mobile-phone-country-code', $mobile_phone_country_code);
                update_user_meta($user_id, 'mobile-phone-number', $mobile_phone_number);
                update_user_meta($user_id, 'test-question-ID', $test_question_ID);
                update_user_meta($user_id, 'answer-test-question', $answer_test_question);
                update_user_meta($user_id, 'profile-picture-ID', $profile_picture_id);
                update_user_meta($user_id, 'identity-file-ID', $identity_file_id);
                if ($receive_notifications && $receive_notifications == 'on') {
                    update_user_meta($user_id, 'receive-notifications', 'yes');
                } else {
                    update_user_meta($user_id, 'receive-notifications', 'no');
                }
                update_user_meta($user_id, 'registration-completed', 2);
                if ($identity_file_id) {
                    $old_identity = get_user_meta($user_id, 'identity-status', true);
                    if ($old_identity == null || $old_identity == 0) {
                        update_user_meta($user_id, 'identity-status', 1);
                    }
                } else {
                    update_user_meta($user_id, 'identity-status', 0);
                }
            }
        } elseif ($role == "professional" || $role == "enterprise") {
            $user_login_pro = removeslashes(esc_attr(trim($_POST['company_name'])));
            $user_email_pro = removeslashes(esc_attr(trim($_POST['email_pro'])));
            $civility_represntative1_pro = removeslashes(esc_attr(trim($_POST['civility_representative1'])));
            $first_name_representative1_pro = removeslashes(esc_attr(trim($_POST['first_name_representative1'])));
            $last_name_representative1_pro = removeslashes(esc_attr(trim($_POST['last_name_representative1'])));
            $email_representative1_pro = removeslashes(esc_attr(trim($_POST['email_representative1'])));
            $function_representative1_pro = removeslashes(esc_attr(trim($_POST['function_representative1'])));
            $mobile_phone_country_code_representative1 = removeslashes(esc_attr(trim($_POST['mobile_phone_country_code_representative1'])));
            $mobile_phone_number_representative1_pro = removeslashes(esc_attr(trim($_POST['mobile_phone_number_representative1'])));
            $civility_represntative2_pro = removeslashes(esc_attr(trim($_POST['civility_represntative2'])));
            $first_name_representative2_pro = removeslashes(esc_attr(trim($_POST['first_name_representative2'])));
            $last_name_representative2_pro = removeslashes(esc_attr(trim($_POST['last_name_representative2'])));
            $email_representative2_pro = removeslashes(esc_attr(trim($_POST['email_representative2'])));
            $function_representative2_pro = removeslashes(esc_attr(trim($_POST['function_representative2'])));
            $mobile_phone_country_code_representative2 = removeslashes(esc_attr(trim($_POST['mobile_phone_country_code_representative2'])));
            $mobile_phone_number_representative2_pro = removeslashes(esc_attr(trim($_POST['mobile_phone_number_representative2'])));
            $company_name_pro = removeslashes(esc_attr(trim($_POST['company_name'])));
            $company_identity_number_pro = removeslashes(esc_attr(trim($_POST['company_identity_number'])));
            $company_identity_tva_number_pro = removeslashes(esc_attr(trim($_POST['company_identity_tva_number'])));
            $number_street_pro = removeslashes(esc_attr(trim($_POST['number_street'])));
            $complement_address_pro = removeslashes(esc_attr(trim($_POST['complement_address'])));
            $locality_pro = removeslashes(esc_attr(trim($_POST['locality_pro'])));
            $country_region_city_pro = getCountryRegionCityInformations($locality_pro);
            $postal_code_pro = removeslashes(esc_attr(trim($_POST['postal_code'])));
            $home_phone_country_code = removeslashes(esc_attr(trim($_POST['home_phone_country_code'])));
            $home_phone_number_pro = removeslashes(esc_attr(trim($_POST['home_phone_number'])));
            $test_question_ID_pro = removeslashes(esc_attr(trim($_POST['test_question_pro'])));
            $answer_test_question_pro = removeslashes(esc_attr(trim($_POST['answer_test_question_pro'])));
            $receive_notifications_pro = removeslashes(esc_attr(trim($_POST['receive_notifications'])));
            $company_logo_id = removeslashes(esc_attr(trim($_POST['company_logo_id'])));
            $identity_file_pro_id = removeslashes(esc_attr(trim($_POST['identity_file_pro_id'])));

            $update_user_data = array(
                'ID' => $user_id,
                //'user_login' => $user_login_pro,
                //'user_pass' => $user_pass_pro,
                'user_email' => $user_email_pro,
                'role' => $role,
                'first_name' => "",
                'last_name' => $company_name_pro
            );

            $user_id = wp_update_user($update_user_data);
            if (!is_wp_error($user_id)) {
                if (!empty($_FILES['company_logo'])) {
                    $logo_pro = $_FILES['company_logo'];
                    $attachment_id = upload_user_file($logo_pro);
                    update_user_meta($user_id, 'company-logo-ID', $attachment_id);
                }
                if (!empty($_FILES['company_attachments'])) {
                    $company_attachements = $_FILES['company_attachments'];
                    $company_attachements_ids = array();
                    foreach ($company_attachements as $$company_attachement) {
                        $attachment_id = upload_user_file($logo_pro);
                        $company_attachements_ids[] = $attachment_id;
                    }
                    update_user_meta($user_id, 'company-attachements-IDs', $company_attachements_ids);
                }
                update_user_meta($user_id, 'company-name', $company_name_pro);
                update_user_meta($user_id, 'company-identity-number', $company_identity_number_pro);
                update_user_meta($user_id, 'company-identity-tva-number', $company_identity_tva_number_pro);
                update_user_meta($user_id, 'number-street', $number_street_pro);
                update_user_meta($user_id, 'complement-address', $complement_address_pro);
                update_user_meta($user_id, 'country', $country_region_city_pro['country']);
                update_user_meta($user_id, 'region-province-state', $country_region_city_pro['region']);
                update_user_meta($user_id, 'commune-city-locality', $country_region_city_pro['city']);
                update_user_meta($user_id, 'postal-code', $postal_code_pro);
                update_user_meta($user_id, 'home-phone-country-code', $home_phone_country_code);
                update_user_meta($user_id, 'home-phone-number', $home_phone_number_pro);
                update_user_meta($user_id, 'civility-representative1', $civility_represntative1_pro);
                update_user_meta($user_id, 'first-name-representative1', $first_name_representative1_pro);
                update_user_meta($user_id, 'last-name-representative1', $last_name_representative1_pro);
                update_user_meta($user_id, 'company-function-representative1', $function_representative1_pro);
                update_user_meta($user_id, 'mobile-phone-country-code-representative1', $mobile_phone_country_code_representative1);
                update_user_meta($user_id, 'mobile-phone-number-representative1', $mobile_phone_number_representative1_pro);
                update_user_meta($user_id, 'email-representative1', $email_representative1_pro);
                update_user_meta($user_id, 'civility-representative2', $civility_represntative2_pro);
                update_user_meta($user_id, 'first-name-representative2', $first_name_representative2_pro);
                update_user_meta($user_id, 'last-name-representative2', $last_name_representative2_pro);
                update_user_meta($user_id, 'company-function-representative2', $function_representative2_pro);
                update_user_meta($user_id, 'mobile-phone-country-code-representative2', $mobile_phone_country_code_representative2);
                update_user_meta($user_id, 'mobile-phone-number-representative2', $mobile_phone_number_representative2_pro);
                update_user_meta($user_id, 'email-representative2', $email_representative2_pro);
                update_user_meta($user_id, 'test-question-ID', $test_question_ID_pro);
                update_user_meta($user_id, 'answer-test-question', $answer_test_question_pro);
                update_user_meta($user_id, 'company-logo-ID', $company_logo_id);
                update_user_meta($user_id, 'identity-file-ID', $identity_file_pro_id);
                if ($receive_notifications_pro && $receive_notifications_pro == 'on') {
                    update_user_meta($user_id, 'receive-notifications', 'yes');
                } else {
                    update_user_meta($user_id, 'receive-notifications', 'no');
                }
                update_user_meta($user_id, 'registration-completed', 2);
                if ($identity_file_pro_id) {
                    $old_identity = get_user_meta($user_id, 'identity-status', true);
                    if ($old_identity == null || $old_identity == 0) {
                        update_user_meta($user_id, 'identity-status', 1);
                    }
                } else {
                    update_user_meta($user_id, 'identity-status', 0);
                }
            }
        }

        if (!is_wp_error($user_id)) {
            // Set the global user object
            $current_user = get_user_by('id', $user_id);

            // set the WP login cookie
            $secure_cookie = is_ssl() ? true : false;
            wp_set_auth_cookie($user_id, true, $secure_cookie);
            //gpdeal_send_activate_link($user_id);
            $_SESSION["success_process"] = __("Your profile has been updated successfully", "gpdealdomain");
            wp_safe_redirect(get_permalink(get_page_by_path(__('my-account', 'gpdealdomain'))));
            exit;
        } else {
            $_SESSION['faillure_process'] = __("An error occurred while updating your profile", "gpdealdomain");
            wp_safe_redirect(get_permalink(get_page_by_path(__('registration', 'gpdealdomain'))));
            exit;
        }
    } else {
        wp_safe_redirect(get_permalink(get_page_by_path(__('my-account', 'gpdealdomain') . '/' . __('profile', 'gpdealdomain'))));
        exit;
    }
}

//Function for getting forgot password of user
function get_password($user_email) {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' && $user_email != "") {
        $user = get_user_by('email', $user_email);
        if ($user != null) {
            $json = array("message" => __("Correct information", "gpdealdomain"));
            return wp_send_json_success($json);
        } else {
            $json = array("message" => __("Unknown user", "gpdealdomain"));
            return wp_send_json_error($json);
        }
    } elseif (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'POST' || $_SERVER['REQUEST_METHOD'] == 'GET' && $user_email != "") {
        $user = get_user_by('email', $user_email);
        $hash_reset_password = sha1(uniqid(mt_rand(), true)) . '' . sha1(uniqid(mt_rand(), true)) . '' . sha1(uniqid(mt_rand(), true));
        update_user_meta($user->ID, 'hash-reset-password', $hash_reset_password);
        update_user_meta($user->ID, 'last-reset-password-time', time());
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: Global Parcel Deal - Informations <infos@gpdeal.com>';
        $headers[] = 'Bcc:<erictonyelissouck@yahoo.fr>';

        $subject = "Global Parcel Deal - " . __("Resetting your password", "gpdealdomain");
        ob_start();
        ?>
        <div style="font-size: 12.8px;"><?php _e("Modify your password and you can continue", "gpdealdomain"); ?>.</div>
        <div><br></div>
        <div style="font-size: 12.8px;"><?php _e("To change your GPDEAL password", "gpdealdomain"); ?>, <?php _e("click", "gpdealdomain"); ?> <a href="<?php echo esc_url(add_query_arg(array('id' => $user->user_login, 'key' => get_user_meta($user->ID, "hash-reset-password", true)), get_permalink(get_page_by_path(__('change-the-password', 'gpdealdomain'))))); ?>"><?php _e("here", "gpdealdomain"); ?></a>
            <?php _e("or paste the following link into your browser", "gpdealdomain"); ?> :</div>
        <div><br></div>
        <div><?php echo esc_url(add_query_arg(array('id' => $user->user_login, 'key' => get_user_meta($user->ID, "hash-reset-password", true)), get_permalink(get_page_by_path(__('change-the-password', 'gpdealdomain'))))); ?></div>
        <div><br></div>
        <div style="font-size: 12.8px;"><?php _e("This link will expire in 24 hours, be sure to use it soon", "gpdealdomain"); ?>.</div>
        <div><br></div>
        <div>
            <p style="margin:0px;padding:0px;border:0px;font-size:12.8px;font-stretch:normal;line-height:normal;font-family:Tahoma;width:auto;height:auto;float:none;color:rgb(0,0,0)"><?php _e("Thank you for using GPDEAL", "gpdealdomain"); ?>,</p>
            <p style="margin:0px 0px 1em;padding:0px;border:0px;font-size:12.8px;font-stretch:normal;line-height:normal;font-family:Tahoma;width:auto;height:auto;float:none;color:rgb(0,0,0)"><?php _e("The team", "gpdealdomain"); ?> Global Parcel Deal</p>
            <p><a href="<?php echo home_url('/'); ?>"><img src="<?php echo get_template_directory_uri() ?>/assets/images/logo_gpdeal.png" style="width: 115px;"></a></p>
        </div>
        <?php
        $body = ob_get_contents();
        ob_end_clean();
        if (wp_mail($user_email, $subject, $body, $headers)) {
            $_SESSION['success_send_password'] = true;
        } else {
            $_SESSION['error_send_password'] = true;
        }
    } else {
        wp_safe_redirect(home_url('/'));
        exit;
    }
}

//Function for getting forgot password of user
function gp_reset_password($login, $new_password) {
    if ($login != "" && $new_password) {
        $user = get_user_by('login', $login);

        if ($user) {
            update_user_meta($user->ID, 'plain-text-password', $new_password);
            wp_set_password($new_password, $user->ID);
            $hash_reset_password = sha1(uniqid(mt_rand(), true)) . '' . sha1(uniqid(mt_rand(), true)) . '' . sha1(uniqid(mt_rand(), true));
            update_user_meta($user->ID, 'hash-reset-password', $hash_reset_password);
            if (is_user_logged_in()) {
                $creds = array('user_login' => $user->data->user_login, 'user_password' => $new_password, 'remember' => false);
                $secure_cookie = is_ssl() ? true : false;
                $user = wp_signon($creds, $secure_cookie);
                $_SESSION["success_process"] = __("Your password has been changed successfully", "gpdealdomain");
                wp_safe_redirect(get_permalink(get_page_by_path(__('my-account', 'gpdealdomain'))->ID));
            } else {
                $_SESSION["success_process"] = __("Your password has been changed successfully", "gpdealdomain") . "! " . __("Log in now to start using our services", "gpdealdomain");
                wp_safe_redirect(get_permalink(get_page_by_path(__('log-in', 'gpdealdomain'))->ID));
            }
            exit;
        } else {
            $_SESSION['faillure_process'] = __("Unable to change password. Incorrect user", "gpdealdomain");
            wp_safe_redirect(get_permalink(get_page_by_path(__('change-the-password', 'gpdealdomain'))->ID));
            exit;
        }
    } else {
        wp_safe_redirect(home_url('/'));
        exit;
    }
}

//Function of signing in gpdeal front-end website
function signin($username, $password, $remember = null, $redirect_to = null) {
    if ($remember == 'on') {
        $remember = true;
    } else {
        $remember = false;
    }

    if (filter_var($username, FILTER_VALIDATE_EMAIL)) { //Invalid Email
        $user = get_user_by('email', $username);
    } else {
        $user = get_user_by('login', $username);
    }

    if ($user && wp_check_password($password, $user->data->user_pass, $user->ID)) {
        if (get_user_meta($user->ID, 'activate', true) == 1) {
            $_SESSION['signin_error'] = __("Your account is not activated", "gpdealdomain");
            wp_safe_redirect(get_permalink(get_page_by_path(__('log-in', 'gpdealdomain'))));
            exit;
        }
        $creds = array('user_login' => $user->data->user_login, 'user_password' => $password, 'remember' => $remember);
        $secure_cookie = is_ssl() ? true : false;
        $user = wp_signon($creds, $secure_cookie);
        $_SESSION['REMEMBER_ME'] = $remember;
        if (!$remember) {
            $_SESSION['LAST_ACTIVITY'] = time();
        }
        if ($redirect_to) {
            wp_safe_redirect($redirect_to);
        } else {
            wp_safe_redirect(get_permalink(get_page_by_path(__('my-account', 'gpdealdomain'))));
        }
        exit;
//        } else {
//            $_SESSION['signin_error'] = __("Your account is not activated", "gpdealdomain");
//            wp_safe_redirect(get_permalink(get_page_by_path(__('log-in', 'gpdealdomain'))));
//            exit;
//        }
    } else {
        $_SESSION['signin_error'] = __("Username or password incorrect", "gpdealdomain");
        wp_safe_redirect(get_permalink(get_page_by_path(__('log-in', 'gpdealdomain'))));
        exit;
    }
}

//Return a name of user custom role defined by gpdeal
function getUserRoleName($role) {
    switch ($role) {
        case 'particular':
            return __("Particular", "gpdealdomain");
        case 'professional':
            return __("Professional", "gpdealdomain");
        case 'enterprise':
            return __('Enterprise', "gpdealdomain");
        default :
            return $role;
    }
}

//Return a gender of hold name
function getGenderHoldName($gender) {
    switch ($gender) {
        case 'M':
            return __('Masculin', 'gpdealdomain');
        case 'F':
            return __("Feminin", 'gpdealdomain');
        default :
            return '';
    }
}

//Function use to retrieve a list of countries online
function getCountriesList() {
    $countries = array(['code' => 'AL', 'flag' => 'al', 'name' => 'Alabama'], ['code' => 'AK', 'flag' => 'ak', 'name' => 'Alaska'], ['code' => 'AZ', 'flag' => 'az', 'name' => 'Arizona'],
        ['code' => 'AR', 'flag' => 'ar', 'name' => 'Arkansas'], ['code' => 'CA', 'flag' => 'ca', 'name' => 'California']
    );
    return $countries;
}

//Function use to retrieve a list of States or Regions of a specific country by countryCode
function getStatesListOfCountry($countryCode = null) {
    $states = array(['code' => 'R1', 'flag' => 'al', 'name' => 'Region 1'], ['code' => 'R2', 'flag' => 'ak', 'name' => 'Region 2'], ['code' => 'R3', 'flag' => 'az', 'name' => 'Region 3'],
        ['code' => 'R4', 'flag' => 'ar', 'name' => 'Region 4'], ['code' => 'R5', 'flag' => 'ca', 'name' => 'Region 5']
    );
    return $states;
}

//Function use to retrieve a list of cities of a specific State
function getCitiesListOfState($stateCode = null) {
    $cities = array(['code' => 'V1', 'flag' => 'al', 'name' => 'Ville 1'], ['code' => 'V2', 'flag' => 'ak', 'name' => 'Ville 2'], ['code' => 'V3', 'flag' => 'az', 'name' => 'Ville 3'],
        ['code' => 'V4', 'flag' => 'ar', 'name' => 'Ville 4'], ['code' => 'V5', 'flag' => 'ca', 'name' => 'Ville 5']
    );
    return $cities;
}

//Function use to retrieve a list of cities of a specific State
function getCurrenciesList() {
    $currencies = array(['code' => 'USD', 'name' => 'Dollard Americain'], ['code' => 'CAD', 'name' => 'Dollard Canadien'], ['code' => 'EUR', 'name' => 'EURO'],
        ['code' => 'CHF', 'name' => 'Franc suisse'], ['code' => 'GBP', 'name' => 'Livre sterling'], ['code' => 'ZAR', 'name' => 'Rand RSA']
    );
    return $currencies;
}

//Fonction for sending a package
function sendPackage($package_data) {
    if ($package_data) {
        $type = $package_data['package_type'];
        $package_content = $package_data['package_content'];
        $length = $package_data['package_dimensions_length'];
        $width = $package_data['package_dimensions_width'];
        $height = $package_data['package_dimensions_height'];
        $weight = $package_data['package_weight'];
        $start_country = $package_data['start_country'];
        $start_state = $package_data['start_state'];
        $start_city = $package_data['start_city'];
        $start_city_as_gmap = $package_data['start_city_as_gmap'];
        $start_date = $package_data['start_date'];
        $destination_country = $package_data['destination_country'];
        $destination_state = $package_data['destination_state'];
        $destination_city = $package_data['destination_city'];
        $destination_city_as_gmap = $package_data['destination_city_as_gmap'];
        $destination_date = $package_data['destination_date'];
        $package_picture_id = $package_data['package_picture_id'];
        $distance_between_departure_arrival = $package_data['distance_between_departure_arrival'];

        $date = new DateTime('now');
        $post_title = str_replace(":", "", str_replace("-", "", str_replace(" ", "", "P" . $date->format('Y-m-d H:i:s') . $date->getTimestamp())));
        $post_args = array(
            'post_title' => wp_strip_all_tags($post_title),
            'post_type' => 'package',
            'post_author' => get_current_user_id(),
            'post_status' => 'publish',
            'tax_input' => array('type_package' => array(intval($type))),
            'meta_input' => array(
                'length' => floatval($length),
                'width' => floatval($width),
                'height' => floatval($height),
                'weight' => floatval($weight),
                'package-number' => $post_title,
                'package-content' => $package_content,
                'departure-country-package' => $start_country,
                'departure-state-package' => $start_state,
                'departure-city-package' => $start_city,
                'start-city-as-gmap' => $start_city_as_gmap,
                'date-of-departure-package' => $start_date,
                'destination-country-package' => $destination_country,
                'destination-state-package' => $destination_state,
                'destination-city-package' => $destination_city,
                'destination-city-as-gmap' => $destination_city_as_gmap,
                'arrival-date-package' => $destination_date,
                'package-picture-ID' => $package_picture_id,
                'distance-between-departure-arrival' => $distance_between_departure_arrival,
                'carrier-ID' => -1,
                'package-status' => 1
            )
        );
        $package_id = wp_insert_post($post_args, true);
        return $package_id;
    }
}

//Fonction for updating information of a package even if a transport is not begin
function updateSendPackage($post_ID, $package_data) {
    if ($post_ID && $package_data) {
        $type = $package_data['package_type'];
        $package_content = $package_data['package_content'];
        $length = $package_data['package_dimensions_length'];
        $width = $package_data['package_dimensions_width'];
        $height = $package_data['package_dimensions_height'];
        $weight = $package_data['package_weight'];
        $start_country = $package_data['start_country'];
        $start_state = $package_data['start_state'];
        $start_city = $package_data['start_city'];
        $start_city_as_gmap = $package_data['start_city_as_gmap'];
        $start_date = $package_data['start_date'];
        $destination_country = $package_data['destination_country'];
        $destination_state = $package_data['destination_state'];
        $destination_city = $package_data['destination_city'];
        $destination_city_as_gmap = $package_data['destination_city_as_gmap'];
        $destination_date = $package_data['destination_date'];
        $package_picture_id = $package_data['package_picture_id'];
        $distance_between_departure_arrival = $package_data['distance_between_departure_arrival'];

        $post_args = array(
            'ID' => $post_ID,
            'tax_input' => array('type_package' => array(intval($type))),
            'meta_input' => array(
                'package-content' => $package_content,
                'length' => floatval($length),
                'width' => floatval($width),
                'height' => floatval($height),
                'weight' => floatval($weight),
                'departure-country-package' => $start_country,
                'departure-state-package' => $start_state,
                'departure-city-package' => $start_city,
                'start-city-as-gmap' => $start_city_as_gmap,
                'date-of-departure-package' => $start_date,
                'destination-country-package' => $destination_country,
                'destination-state-package' => $destination_state,
                'destination-city-package' => $destination_city,
                'destination-city-as-gmap' => $destination_city_as_gmap,
                'arrival-date-package' => $destination_date,
                'package-picture-ID' => $package_picture_id,
                'distance-between-departure-arrival' => $distance_between_departure_arrival
            )
        );
        $package_id = wp_update_post($post_args, true);
        return $package_id;
    }
}

//Function to get and echo all reply of comment recursively
function getAndEchoAllReplyForCarrier($evaluation_id, $comment_id) {
    global $current_user;
    $comments_children_view_content = "";
    if ($evaluation_id && $comment_id) {
        $comments_children = get_comments(array('post_id' => $evaluation_id, "parent" => $comment_id, "orderby" => "comment_date", "order" => "asc"));
        if ($comments_children && !empty($comments_children)) {
            ob_start();
            ?>
            <div class="comments">
                <?php
                foreach ($comments_children as $comment):
                    $comment_user = get_userdata($comment->user_id);
                    $comment_profile_picture_id = get_user_meta($comment->user_id, 'profile-picture-ID', true) ? get_user_meta($comment->user_id, 'profile-picture-ID', true) : get_user_meta($comment->user_id, 'company-logo-ID', true);
                    ?>
                    <div class="comment">
                        <a class="avatar">
                            <img  <?php if ($comment_profile_picture_id): ?> src= "<?php echo wp_make_link_relative(wp_get_attachment_url($comment_profile_picture_id)); ?>" <?php else: ?> src="<?php echo wp_make_link_relative(get_template_directory_uri()) ?>/assets/images/avatar.png"<?php endif ?>>
                        </a>
                        <div class="content">
                            <a class="author"><?php echo $comment_user->user_login; ?></a>
                            <div class="metadata">
                                <div class="date"><?php
                                    $date = apply_filters('get_comment_time', $comment->comment_date, 'U', false, true, $comment);
                                    echo __("has commented", "gpdealdomain") . " " . human_time_diff(strtotime($date), current_time('timestamp'));
                                    ?> <?php _e("ago", "gpdealdomain"); ?></div>
                            </div>
                            <div class="text">
                                <p><?php echo $comment->comment_content; ?></p>
                            </div>
                        </div>
                        <?php echo getAndEchoAllReplyForCarrier($evaluation_id, $comment->comment_ID); ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php
            $comments_children_view_content = ob_get_contents();
            ob_end_clean();
        }
    }
    return $comments_children_view_content;
}

//Function to get and echo all reply of comment recursively
function getAndechoAllReply($evaluation_id, $comment_id, $transport_offer_link) {
    global $current_user;
    $comments_children_view_content = "";
    if ($evaluation_id && $comment_id) {
        $comments_children = get_comments(array('post_id' => $evaluation_id, "parent" => $comment_id, "orderby" => "comment_date", "order" => "asc"));
        $current_user_comments_count = get_comments(array('post_id' => $evaluation_id, "user_id" => $current_user->ID, 'count' => true));
        if ($comments_children && !empty($comments_children)) {
            ob_start();
            ?>
            <div class="comments">
                <?php
                foreach ($comments_children as $comment):
                    $comment_user = get_userdata($comment->user_id);
                    $comment_profile_picture_id = get_user_meta($comment->user_id, 'profile-picture-ID', true) ? get_user_meta($comment->user_id, 'profile-picture-ID', true) : get_user_meta($comment->user_id, 'company-logo-ID', true);
                    ?>
                    <div class="comment">
                        <a class="avatar">
                            <img  class="ui avatar image" <?php if ($comment_profile_picture_id): ?> src= "<?php echo wp_make_link_relative(wp_get_attachment_url($comment_profile_picture_id)); ?>" <?php else: ?> src="<?php echo wp_make_link_relative(get_template_directory_uri()) ?>/assets/images/avatar.png"<?php endif ?>>
                        </a>
                        <div class="content">
                            <a class="author"><?php echo $comment_user->user_login; ?></a>
                            <div class="metadata">
                                <div class="date"><?php
                                    $date = apply_filters('get_comment_time', $comment->comment_date, 'U', false, true, $comment);
                                    echo __("has commented", "gpdealdomain") . " " . human_time_diff(strtotime($date), current_time('timestamp'));
                                    ?></div>
                            </div>
                            <div class="text">
                                <p><?php echo $comment->comment_content; ?></p>
                            </div>
                            <?php if ($current_user_comments_count == 0): ?>
                                <div class="actions">
                                    <a id="show_comment_reply_form<?php echo $comment->comment_ID; ?>" onclick="show_comment_reply_form(<?php echo $comment->comment_ID; ?>)" class="reply"><?php echo __("Answer", "gpdealdomain") ?></a>
                                    <a id="hide_comment_reply_form<?php echo $comment->comment_ID; ?>" onclick="hide_comment_reply_form(<?php echo $comment->comment_ID; ?>)" class="reply" style="display: none"><?php echo __("Cancel", "gpdealdomain") ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <?php echo getAndechoAllReply($evaluation_id, $comment->comment_ID, $transport_offer_link); ?>
                    </div>
                    <?php if ($current_user_comments_count == 0): ?>
                        <form id="comment_reply_form<?php echo $comment->comment_ID; ?>" class="ui reply form add_comment_reply_form" method="POST" action="<?php echo $transport_offer_link; ?>" onsubmit="add_comment_reply(event, <?php echo $comment->comment_ID; ?>)" style="display: none">
                            <div class="field">
                                <textarea name="comment_content" placeholder="<?php _e("Enter your answer here", "gpdealdomain"); ?>"></textarea>
                            </div>
                            <input type="hidden" name="action" value="add-comment-reply">
                            <input type="hidden" name="evaluation_id" value="<?php echo $evaluation_id; ?>">
                            <input type="hidden" name="comment_parent_id" value="<?php echo $comment->comment_ID; ?>">
                            <div class="field">
                                <div id="server_error_message<?php echo $comment->comment_ID; ?>" class="ui negative message" style="display:none">
                                    <i class="close icon"></i>
                                    <div id="server_error_content<?php echo $comment->comment_ID; ?>" class="header"><?php _e("Internal server error", "gpdealdomain"); ?></div>
                                </div>
                                <div id="error_name_message<?php echo $comment->comment_ID; ?>" class="ui error message" style="display: none">
                                    <i class="close icon"></i>
                                    <div id="error_name_header<?php echo $comment->comment_ID; ?>" class="header"></div>
                                    <ul id="error_name_list<?php echo $comment->comment_ID; ?>" class="list">

                                    </ul>
                                </div>
                            </div>
                            <button class="ui blue submit icon button">
                                <i class="icon edit"></i> <?php _e("Answer", "gpdealdomain"); ?>
                            </button>
                        </form>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php
            $comments_children_view_content = ob_get_contents();
            ob_end_clean();
        }
    }
    return $comments_children_view_content;
}

//Function for getting total statistique of evalation of spécific carrier
function getTotalStatistiticsEvaluationsOfCarrier($carrier_id) {
    global $post;
    $statistics = array("Evaluation globale" => array("0" => 0, "1" => 0, "2" => 0, "3" => 0, "4" => 0, "5" => 0, 'vote_count' => 0, "weighted_average" => 0));
    $evaluations = new WP_Query(array('post_type' => 'evaluation', 'posts_per_page' => -1, "post_status" => 'publish', 'orderby' => 'post_date', 'order' => 'DESC', 'meta_query' => array(array('key' => 'carrier-author', 'value' => $carrier_id, 'compare' => '='))));
    if ($evaluations->have_posts()) {
        while ($evaluations->have_posts()) {
            $evaluations->the_post();
            $questions = get_post_meta(get_the_ID(), 'questions', true);
            $responses = get_post_meta(get_the_ID(), 'responses', true);
            if (is_array($questions) && is_array($responses) && count($questions) == 5 && count($responses) == 5) {
                if (intval($responses[4]) >= 0) {
                    $statistics[$questions[4]][$responses[4]] ++;
                    $statistics[$questions[4]]["vote_count"] ++;
                }
            }
        }
        wp_reset_postdata();
    }

    foreach ($statistics as $statistic_key => $statistic) {
        foreach ($statistic as $key => $value) {
            if ($key != "vote_count" && $key != "weighted_average") {
                $statistic["weighted_average"] += (intval($key) * $value);
            }
        }
        $statistic["weighted_average"] = $statistic["vote_count"] != 0 ? round($statistic["weighted_average"] / $statistic["vote_count"]) : 0;
        $statistics[$statistic_key] = $statistic;
    }
    return $statistics;
}

//Function for getting all evalations of spécific carrier
function getEvaluationsOfCarrier($carrier_id) {
    return $evaluations = new WP_Query(array('post_type' => 'evaluation', 'posts_per_page' => -1, "post_status" => 'publish', 'orderby' => 'post_date', 'order' => 'DESC', 'meta_query' => array(array('key' => 'carrier-author', 'value' => $carrier_id, 'compare' => '='))));
}

//Function for getting total statistique of evalation of spécific transport offer
function getTotalStatistiticsEvaluation($transport_offer_id) {
    global $post;
    $statistics = array("Evaluation globale" => array("0" => 0, "1" => 0, "2" => 0, "3" => 0, "4" => 0, "5" => 0, 'vote_count' => 0, "weighted_average" => 0));
    $evaluations = new WP_Query(array('post_type' => 'evaluation', 'posts_per_page' => -1, "post_status" => 'publish', 'orderby' => 'post_date', 'order' => 'DESC', 'meta_query' => array(array('key' => 'transport-offer-ID', 'value' => $transport_offer_id, 'compare' => '='))));
    if ($evaluations->have_posts()) {
        while ($evaluations->have_posts()) {
            $evaluations->the_post();
            $questions = get_post_meta(get_the_ID(), 'questions', true);
            $responses = get_post_meta(get_the_ID(), 'responses', true);
            if (is_array($questions) && is_array($responses) && count($questions) == 5 && count($responses) == 5) {
                if (intval($responses[4]) >= 0) {
                    $statistics[$questions[4]][$responses[4]] ++;
                    $statistics[$questions[4]]["vote_count"] ++;
                }
            }
        }
        wp_reset_postdata();
    }

    foreach ($statistics as $statistic_key => $statistic) {
        foreach ($statistic as $key => $value) {
            if ($key != "vote_count" && $key != "weighted_average") {
                $statistic["weighted_average"] += (intval($key) * $value);
            }
        }
        $statistic["weighted_average"] = $statistic["vote_count"] != 0 ? round($statistic["weighted_average"] / $statistic["vote_count"]) : 0;
        $statistics[$statistic_key] = $statistic;
    }
    return $statistics;
}

//Function for adding a comment to an evaluation 
function add_comment_reply($evaluation_id, $comment_parent_id, $comment_content) {
    global $current_user;
    $comment_id = null;
    if ($evaluation_id && $comment_parent_id) {
        $commentdata = array(
            'comment_post_ID' => $evaluation_id, // to which post the comment will show up
            'comment_author' => $current_user->user_login, //fixed value - can be dynamic 
            'comment_author_email' => $current_user->user_email, //fixed value - can be dynamic 
            'comment_author_url' => 'http://testgpdeal.com', //fixed value - can be dynamic 
            'comment_content' => $comment_content, //fixed value - can be dynamic 
            'comment_type' => '', //empty for regular comments, 'pingback' for pingbacks, 'trackback' for trackbacks
            'comment_parent' => $comment_parent_id, //0 if it's not a reply to another comment; if it's a reply, mention the parent comment ID here
            'user_id' => $current_user->ID, //passing current user ID or any predefined as per the demand
        );

//Insert new comment and get the comment ID
        $comment_id = wp_new_comment($commentdata);
    }
    return $comment_id;
}

//Function for adding a comment to an evaluation 
function add_evaluation_comment($evaluation_id, $comment_content) {
    global $current_user;
    $comment_id = null;
    if ($evaluation_id) {
        $commentdata = array(
            'comment_post_ID' => $evaluation_id, // to which post the comment will show up
            'comment_author' => $current_user->user_login, //fixed value - can be dynamic 
            'comment_author_email' => $current_user->user_email, //fixed value - can be dynamic 
            'comment_author_url' => 'http://testgpdeal.com', //fixed value - can be dynamic 
            'comment_content' => $comment_content, //fixed value - can be dynamic 
            'comment_type' => '', //empty for regular comments, 'pingback' for pingbacks, 'trackback' for trackbacks
            'comment_parent' => 0, //0 if it's not a reply to another comment; if it's a reply, mention the parent comment ID here
            'user_id' => $current_user->ID, //passing current user ID or any predefined as per the demand
        );

//Insert new comment and get the comment ID
        $comment_id = wp_new_comment($commentdata);
    }
    return $comment_id;
}

//Function for adding an evaluation for Transport offer and comment
function evaluateTransportOffer($evaluation_data) {
    global $post;
    global $current_user;
    if ($evaluation_data) {
        $post_args = array(
            'post_title' => wp_strip_all_tags('evaluation_' . $post->post_title),
            'post_type' => 'evaluation',
            'post_author' => get_current_user_id(),
            'post_status' => 'publish',
            'meta_input' => array(
                'transport-offer-ID' => $post->ID,
                'carrier-author' => get_post_field('post_author', $post->ID),
                'package-ID' => $evaluation_data['package_id'],
                'questions' => array("Items delivered", "State of objects", "Delivery time", "Cost", "Evaluation globale"),
                'responses' => $evaluation_data['responses']
            )
        );
        $evaluation_id = wp_insert_post($post_args, true);
        $comment_content = $evaluation_data['comment_content'];
        if (!is_wp_error($evaluation_id) && $comment_content && $comment_content != "") {

            $commentdata = array(
                'comment_post_ID' => $evaluation_id, // to which post the comment will show up
                'comment_author' => $current_user->user_login, //fixed value - can be dynamic 
                'comment_author_email' => $current_user->user_email, //fixed value - can be dynamic 
                'comment_author_url' => get_site_url(), //fixed value - can be dynamic 
                'comment_content' => $comment_content, //fixed value - can be dynamic 
                'comment_type' => '', //empty for regular comments, 'pingback' for pingbacks, 'trackback' for trackbacks
                'comment_parent' => 0, //0 if it's not a reply to another comment; if it's a reply, mention the parent comment ID here
                'user_id' => $current_user->ID, //passing current user ID or any predefined as per the demand
            );

            //Insert new comment and get the comment ID
            $comment_id = wp_new_comment($commentdata);
        }
        return $evaluation_id;
    }
}

//Fonction for Saving a Transport offer
function saveTransportOffer($transport_offer_data) {
    if ($transport_offer_data) {
        $package_type = $transport_offer_data['transport_offer_package_type'];
        $contact_voices = $transport_offer_data['contact_voices'];
        $transport_method = $transport_offer_data['transport_offer_transport_method'];
        $transport_offer_price = $transport_offer_data['transport_offer_price'];
        $transport_offer_currency = $transport_offer_data['transport_offer_currency'];
        $transport_offer_price_type = $transport_offer_data['transport_offer_price_type'];
        $transport_offer_portable_objects = $transport_offer_data['transport_offer_portable_objects'];
        $max_length = $transport_offer_data['package_length_max'];
        $max_width = $transport_offer_data['package_width_max'];
        $max_height = $transport_offer_data['package_height_max'];
        $max_weight = $transport_offer_data['package_weight_max'];
        $start_country = $transport_offer_data['start_country'];
        $start_state = $transport_offer_data['start_state'];
        $start_city = $transport_offer_data['start_city'];
        $start_city_as_gmap = $transport_offer_data['start_city_as_gmap'];
        $start_date = $transport_offer_data['start_date'];
        $start_deadline = $transport_offer_data['start_deadline'];
        $destination_country = $transport_offer_data['destination_country'];
        $destination_state = $transport_offer_data['destination_state'];
        $destination_city = $transport_offer_data['destination_city'];
        $destination_city_as_gmap = $transport_offer_data['destination_city_as_gmap'];
        $destination_date = $transport_offer_data['destination_date'];
        $distance_between_departure_arrival = $transport_offer_data['distance_between_departure_arrival'];

        $date = new DateTime('now');
        $post_title = str_replace(":", "", str_replace("-", "", str_replace(" ", "", "TRFR" . $date->format('Y-m-d H:i:s') . $date->getTimestamp())));

        $post_args = array(
            'post_title' => wp_strip_all_tags($post_title),
            'post_type' => 'transport-offer',
            'post_author' => get_current_user_id(),
            'post_status' => 'publish',
            'tax_input' => array('type_package' => $package_type, 'transport-method' => array(intval($transport_method))),
            'meta_input' => array(
                'transport-offer-number' => $post_title,
                'price' => floatval($transport_offer_price),
                'currency' => $transport_offer_currency,
                'price-type' => intval($transport_offer_price_type),
                'portable-objects' => $transport_offer_portable_objects,
                'package-length-max' => floatval($max_length),
                'package-width-max' => floatval($max_width),
                'package-height-max' => floatval($max_height),
                'package-weight-max' => floatval($max_weight),
                'departure-country-transport-offer' => $start_country,
                'departure-state-transport-offer' => $start_state,
                'departure-city-transport-offer' => $start_city,
                'start-city-as-gmap' => $start_city_as_gmap,
                'date-of-departure-transport-offer' => $start_date,
                'deadline-of-proposition-transport-offer' => $start_deadline,
                'destination-country-transport-offer' => $destination_country,
                'destination-state-transport-offer' => $destination_state,
                'destination-city-transport-offer' => $destination_city,
                'destination-city-as-gmap' => $destination_city_as_gmap,
                'arrival-date-transport-offer' => $destination_date,
                'distance-between-departure-arrival' => $distance_between_departure_arrival,
                'transport-status' => 1,
                'packages-IDs' => -1,
                'contact-voices' => $contact_voices
            )
        );
        $transport_offer_id = wp_insert_post($post_args, true);
        return $transport_offer_id;
    }
}

//Fonction for Updating informations of Transport offer a package
function updateTransportOffer($post_ID, $transport_offer_data) {
    $transport_offer_id = null;
    if ($transport_offer_data) {
        $package_type = $transport_offer_data['transport_offer_package_type'];
        $contact_voices = $transport_offer_data['contact_voices'];
        $transport_method = $transport_offer_data['transport_offer_transport_method'];
        $transport_offer_price = $transport_offer_data['transport_offer_price'];
        $transport_offer_currency = $transport_offer_data['transport_offer_currency'];
        $transport_offer_price_type = $transport_offer_data['transport_offer_price_type'];
        $transport_offer_portable_objects = $transport_offer_data['transport_offer_portable_objects'];
        $max_length = $transport_offer_data['package_length_max'];
        $max_width = $transport_offer_data['package_width_max'];
        $max_height = $transport_offer_data['package_height_max'];
        $max_weight = $transport_offer_data['package_weight_max'];
        $start_country = $transport_offer_data['start_country'];
        $start_state = $transport_offer_data['start_state'];
        $start_city = $transport_offer_data['start_city'];
        $start_city_as_gmap = $transport_offer_data['start_city_as_gmap'];
        $start_date = $transport_offer_data['start_date'];
        $start_deadline = $transport_offer_data['start_deadline'];
        $destination_country = $transport_offer_data['destination_country'];
        $destination_state = $transport_offer_data['destination_state'];
        $destination_city = $transport_offer_data['destination_city'];
        $destination_city_as_gmap = $transport_offer_data['destination_city_as_gmap'];
        $destination_date = $transport_offer_data['destination_date'];
        $distance_between_departure_arrival = $transport_offer_data['distance_between_departure_arrival'];

        $post_args = array(
            'ID' => $post_ID,
            'tax_input' => array('type_package' => $package_type, 'transport-method' => array(intval($transport_method))),
            'meta_input' => array(
                'price' => floatval($transport_offer_price),
                'currency' => $transport_offer_currency,
                'price-type' => intval($transport_offer_price_type),
                'portable-objects' => $transport_offer_portable_objects,
                'package-length-max' => floatval($max_length),
                'package-width-max' => floatval($max_width),
                'package-height-max' => floatval($max_height),
                'package-weight-max' => floatval($max_weight),
                'departure-country-transport-offer' => $start_country,
                'departure-state-transport-offer' => $start_state,
                'departure-city-transport-offer' => $start_city,
                'start-city-as-gmap' => $start_city_as_gmap,
                'date-of-departure-transport-offer' => $start_date,
                'deadline-of-proposition-transport-offer' => $start_deadline,
                'destination-country-transport-offer' => $destination_country,
                'destination-state-transport-offer' => $destination_state,
                'destination-city-transport-offer' => $destination_city,
                'destination-city-as-gmap' => $destination_city_as_gmap,
                'arrival-date-transport-offer' => $destination_date,
                'distance-between-departure-arrival' => $distance_between_departure_arrival,
                'contact-voices' => $contact_voices
            )
        );
        $transport_offer_id = wp_update_post($post_args, true);
        $today = new \DateTime('today');
        $start_deadline_datetime = new \DateTime($start_deadline);
        if ($today <= $start_deadline_datetime) {
            update_post_meta($transport_offer_id, 'transport-status', 1);
        }
        return $transport_offer_id;
    }
}

//Function for leaving a message in contact form on the website
function contactus() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $subject = removeslashes(esc_attr(trim($_POST['subject'])));
        $message = removeslashes(esc_attr(trim($_POST['message'])));
        $email = $current_user->data->user_email;
        if (is_user_in_role($current_user->ID, 'particular')) {
            $role = getUserRoleName('particular');
            $sender_name = $current_user->first_name . " " . $current_user->last_name;
            $mobile_phone_country_code = get_user_meta($current_user->ID, 'mobile-phone-country-code', true);
            $mobile_phone_number = get_user_meta($current_user->ID, 'mobile-phone-number', true);
            $phone_number = "$mobile_phone_country_code$mobile_phone_number";
        } else {
            $sender_name = get_user_meta($current_user->ID, 'company-name', true);
            if (is_user_in_role($current_user->ID, 'professional')) {
                $role = getUserRoleName('professional');
            } elseif (is_user_in_role($current_user->ID, 'enterprise')) {
                $role = getUserRoleName('enterprise');
            }
            $home_phone_country_code = get_user_meta($current_user->ID, 'home-phone-country-code', true);
            $home_phone_number = get_user_meta($current_user->ID, 'home-phone-number', true);
            $phone_number = "$home_phone_country_code$home_phone_number";
        }
    } elseif (isset($_POST['member']) && $_POST['member'] == 'yes') {
        $email = removeslashes(esc_attr(trim($_POST['email'])));
        $subject = removeslashes(esc_attr(trim($_POST['subject'])));
        $message = removeslashes(esc_attr(trim($_POST['message'])));

        $user = get_user_by('email', $email);
        if ($user == null || is_wp_error($user)) {
            $json = array("message" => __("Unknow user", 'gpdealdomain') . ".");
            return wp_send_json_error($json);
        }
        if (is_user_in_role($user->ID, 'particular')) {
            $sender_name = $user->first_name . " " . $user->last_name;
            $mobile_phone_country_code = get_user_meta($user->ID, 'mobile-phone-country-code', true);
            $mobile_phone_number = get_user_meta($user->ID, 'mobile-phone-number', true);
            $phone_number = "$mobile_phone_country_code$mobile_phone_number";
        } else {
            $sender_name = get_user_meta($user->ID, 'company-name', true);
            $home_phone_country_code = get_user_meta($user->ID, 'home-phone-country-code', true);
            $home_phone_number = get_user_meta($user->ID, 'home-phone-number', true);
            $phone_number = "$home_phone_country_code$home_phone_number";
        }
    } else {
        $email = removeslashes(esc_attr(trim($_POST['email'])));
        $sender_name = removeslashes(esc_attr(trim($_POST['name_companyname'])));
        $subject = removeslashes(esc_attr(trim($_POST['subject'])));
        $message = removeslashes(esc_attr(trim($_POST['message'])));
        $phone_country_code = removeslashes(esc_attr(trim($_POST['phone_number_code'])));
        $phone_number = removeslashes(esc_attr(trim($_POST['phone_number'])));
        $phone_number = "$phone_country_code$phone_number";
    }
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $sender_name . ' <' . $email . '>';
    $headers[] = 'Bcc:<erictonyelissouck@yahoo.fr>';

    ob_start();
    ?>
    <div style="font-size: 12.8px;"><Label><?php _e("Name", "gpdealdomain") ?></label> : <span><?php echo $sender_name; ?></span>.</div>
    <div><br></div>
    <div style="font-size: 12.8px;"><Label><?php _e("Phone Number", "gpdealdomain") ?></label> : <span><?php echo $phone_number; ?></span></div>
    <div><br></div>
    <div style="font-size: 12.8px;"><?php echo $message; ?>.</div>
    <?php
    $body = ob_get_contents();
    ob_end_clean();

    $to = get_bloginfo('admin_email');
    if (wp_mail($to, $subject, $body, $headers)) {
        $json = array("message" => __("Your message has been send successfully", 'gpdealdomain'));
        return wp_send_json_success($json);
    } else {
        $json = array("message" => __("Error sending. Verify the information and try again", 'gpdealdomain'));
        return wp_send_json_error($json);
    }
}

function getUserIdentityStatus($status) {
    switch ($status) {
        case 0:
            return __("Not identified", "gpdealdomain");
        case 1:
            return __("Verification in Progress", "gpdealdomain");
        case 2:
            return __("Not verified", "gpdealdomain");
        case 3:
            return __("Verified", "gpdealdomain");
        default :
            return __("Not verified", "gpdealdomain");
    }
}

function getPackageStatus($status) {
    switch ($status) {
        case -1:
            return _e("Search carriers", "gpdealdomain");
        case 1:
            return _e("Search carriers", "gpdealdomain");
        case 2:
            return _e("Transaction in progress", "gpdealdomain");
        case 3:
            return _e("Evaluated/Closed", "gpdealdomain");
        case 4:
            return _e("Expired", "gpdealdomain");
        case 5:
            return _e("Canceled", "gpdealdomain");
        default :
            return _e("No Status", "gpdealdomain");
    }
}

function getTransportStatus($status) {
    switch ($status) {
        case -1:
            return _e("In progress", "gpdealdomain");
        case 1:
            return _e("In progress", "gpdealdomain");
        case 2:
            return _e("Expired", "gpdealdomain");
        case 3:
            return _e("Canceled", "gpdealdomain");
        case 4:
            return _e("Expired", "gpdealdomain");
        default :
            return _e("No Status", "gpdealdomain");
    }
}

// This Function return arguments of a query for finding transport offers
function getWPQueryArgsForCarrierSearch($search_data) {
    $today = date('Y-m-d H:i:s', strtotime('today'));
    $args = array(
        'post_type' => 'transport-offer',
        'posts_per_page' => -1,
        "post_status" => 'publish',
        'orderby' => 'post_date',
        'order' => 'DESC',
    );
    if ($search_data["excluded_transport_offers"]) {
        $args["post__not_in"] = $search_data["excluded_transport_offers"];
    }
//    if (is_user_logged_in()) {
//        $args["author__not_in"] = array(get_current_user_id());
//    }
    if ($search_data) {
        $package_type = $search_data['package_type'];
        $start_country = $search_data['start_country'];
        $start_city = $search_data['start_city'];
        $start_date = $search_data['start_date'];
        $destination_country = $search_data['destination_country'];
        $destination_city = $search_data['destination_city'];
        $destination_date = $search_data['destination_date'];
        $page = $search_data["page"];
        $posts_per_page = $search_data["posts_per_page"];
        if ($posts_per_page) {
            $args["posts_per_page"] = $posts_per_page;
        }
        if ($page) {
            $args["paged"] = $page;
        }

        if (!empty($package_type)) {
            $tax_query[] = array(
                'taxonomy' => 'type_package',
                'field' => 'term_id',
                'terms' => $package_type,
                'operator' => 'IN',
            );
            $args['tax_query'] = $tax_query;
        }


        $meta_query = array(
            'relation' => 'AND',
        );

        if ($start_city != "") {
            $meta_query[] = array(
                'key' => 'departure-city-transport-offer',
                'value' => $start_city,
                'compare' => 'LIKE',
            );

            if ($start_country != "") {
                $meta_query[] = array(
                    'key' => 'departure-country-transport-offer',
                    'value' => $start_country,
                    'compare' => 'LIKE',
                );
            }
        }

        if ($start_date) {
            $meta_query[] = array(
                'key' => 'date-of-departure-transport-offer',
                'value' => date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $start_date))),
                'compare' => '=',
                'type' => 'DATE'
            );
        }

        if ($destination_city != "") {
            $meta_query[] = array(
                'key' => 'destination-city-transport-offer',
                'value' => $destination_city,
                'compare' => 'LIKE',
            );
            if ($destination_country != "") {
                $meta_query[] = array(
                    'key' => 'destination-country-transport-offer',
                    'value' => $destination_country,
                    'compare' => 'LIKE',
                );
            }
        }

        if ($destination_date) {
            $meta_query[] = array(
                'key' => 'arrival-date-transport-offer',
                'value' => date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $destination_date))),
                'compare' => '=',
                'type' => 'DATE'
            );
        }
        $meta_query[] = array(
            'key' => 'deadline-of-proposition-transport-offer',
            'value' => $today,
            'compare' => '>=',
            'type' => 'DATE'
        );
        $args['meta_query'] = $meta_query;
    }
    return $args;
}

// This Function return arguments of a query for finding unsatisfied send package
function getWPQueryArgsForUnsatifiedSendPackages($search_data) {
    $args = array(
        'post_type' => 'package',
        "post_status" => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'post_date',
        'order' => 'DESC'
    );
//    if (is_user_logged_in()) {
//        $args["author__not_in"] = array(get_current_user_id());
//    }
    if ($search_data) {
        $package_type = $search_data['package_type'];
        $start_country = $search_data['start_country'];
        $start_city = $search_data['start_city'];
        $start_date = $search_data['start_date'];
        $destination_country = $search_data['destination_country'];
        $destination_city = $search_data['destination_city'];
        $destination_date = $search_data['destination_date'];
        $page = $search_data["page"];
        $posts_per_page = $search_data["posts_per_page"];
        if ($posts_per_page) {
            $args["posts_per_page"] = $posts_per_page;
        }
        if ($page) {
            $args["paged"] = $page;
        }

        if (!empty($package_type)) {
            $tax_query[] = array(
                'taxonomy' => 'type_package',
                'field' => 'term_id',
                'terms' => $package_type,
                'operator' => 'IN',
            );
            $args['tax_query'] = $tax_query;
        }

        $meta_query = array(
            'relation' => 'AND',
            array(
                'key' => 'carrier-ID',
                'value' => -1,
                'compare' => '=',
            )//we retrieve here all package that avec carrier-ID equal to -1 it mean that there is no transport offer select for this package
        );


        if ($start_city != "") {
            $meta_query[] = array(
                'key' => 'departure-city-package',
                'value' => $start_city,
                'compare' => 'LIKE',
            );

            if ($start_country != "") {
                $meta_query[] = array(
                    'key' => 'departure-country-package',
                    'value' => $start_country,
                    'compare' => 'LIKE',
                );
            }
        }

        if ($start_date) {
            $meta_query[] = array(
                'key' => 'date-of-departure-package',
                'value' => date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $start_date))),
                'compare' => '=',
                'type' => 'DATE'
            );
        }


        if ($destination_city != "") {
            $meta_query[] = array(
                'key' => 'destination-city-package',
                'value' => $destination_city,
                'compare' => 'LIKE',
            );

            if ($destination_country != "") {
                $meta_query[] = array(
                    'key' => 'destination-country-package',
                    'value' => $destination_country,
                    'compare' => 'LIKE',
                );
            }
        }

        if ($destination_date) {
            $meta_query[] = array(
                'key' => 'arrival-date-package',
                'value' => date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $destination_date))),
                'compare' => '=',
                'type' => 'DATE'
            );
        }
        $args['meta_query'] = $meta_query;
    }
    return $args;
}

// This Function return arguments of a query for finding transport offers with can interest someone
function getWPQueryArgsCarrierSearchForWhichCanInterest($search_data, $exclude_ids = array()) {
    $args = array();
    $today = date('Y-m-d H:i:s', strtotime('today'));
    if ($search_data) {
        $package_type = $search_data['package_type'];
        $start_country = $search_data['start_country'];
        $start_state = $search_data['start_state'];
        $start_city = $search_data['start_city'];
        $start_date = $search_data['start_date'];
        $destination_country = $search_data['destination_country'];
        $destination_state = $search_data['destination_state'];
        $destination_city = $search_data['destination_city'];
        $destination_date = $search_data['destination_date'];
        $page = $search_data["page"];
        $posts_per_page = $search_data["posts_per_page"];
        if ($search_data["excluded_transport_offers"]) {
            $exclude_ids = array_merge($exclude_ids, $search_data["excluded_transport_offers"]);
        }
        if ($start_city || $destination_city) {
            $args = array(
                "post_type" => "transport-offer",
                "post_status" => 'publish',
                'posts_per_page' => -1,
                'orderby' => 'post_date',
                'order' => 'DESC',
                "post__not_in" => $exclude_ids
            );

            if ($posts_per_page) {
                $args["posts_per_page"] = $posts_per_page;
            }
            if ($page) {
                $args["paged"] = $page;
            }
//            if (is_user_logged_in()) {
//                $args["author__not_in"] = array(get_current_user_id());
//            }
            if (!empty($package_type)) {
                $tax_query[] = array(
                    'taxonomy' => 'type_package',
                    'field' => 'term_id',
                    'terms' => $package_type,
                    'operator' => 'IN',
                );
                $args['tax_query'] = $tax_query;
            }


            $meta_query = array(
                'relation' => 'AND'
            );


            if ($start_city != "") {
                if ($start_country != "") {
                    $meta_query[] = array(
                        'key' => 'departure-country-transport-offer',
                        'value' => $start_country,
                        'compare' => 'LIKE',
                    );
                }

                if ($start_state != "") {
                    $meta_query[] = array(
                        'key' => 'departure-state-transport-offer',
                        'value' => $start_state,
                        'compare' => 'LIKE',
                    );
                } else {
                    $meta_query[] = array(
                        'key' => 'departure-city-transport-offer',
                        'value' => $start_city,
                        'compare' => 'LIKE',
                    );
                }
            }

            if ($start_date) {
                $meta_query[] = array(
                    'key' => 'date-of-departure-transport-offer',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                );
            }

            if ($destination_city != "") {
                if ($destination_country != "") {
                    $meta_query[] = array(
                        'key' => 'destination-country-transport-offer',
                        'value' => $destination_country,
                        'compare' => 'LIKE',
                    );
                }

                if ($destination_state != "") {
                    $meta_query[] = array(
                        'key' => 'destination-state-transport-offer',
                        'value' => $destination_state,
                        'compare' => 'LIKE',
                    );
                } else {
                    $meta_query[] = array(
                        'key' => 'destination-city-transport-offer',
                        'value' => $destination_city,
                        'compare' => 'LIKE',
                    );
                }
            }

            if ($destination_date) {
                $meta_query[] = array(
                    'key' => 'arrival-date-transport-offer',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                );
            }
            $meta_query[] = array(
                'key' => 'deadline-of-proposition-transport-offer',
                'value' => $today,
                'compare' => '>=',
                'type' => 'DATE'
            );
            $args['meta_query'] = $meta_query;
        }
    }
    return $args;
}

// This Function return arguments of a query for finding unsatisfied send package with can interest someone
function getWPQueryArgsForUnsatifiedSendPackagesWithCanInterest($search_data, $exclude_ids = array()) {
    $today = date('Y-m-d H:i:s', strtotime('today'));
    $args = array(
        'post_type' => 'package',
        "post_status" => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'post_date',
        'order' => 'DESC',
        "post__not_in" => $exclude_ids
    );
//    if (is_user_logged_in()) {
//        $args["author__not_in"] = array(get_current_user_id());
//    }
    if ($search_data) {
        $package_type = $search_data['package_type'];
        $start_country = $search_data['start_country'];
        $start_state = $search_data['start_state'];
        $start_city = $search_data['start_city'];
        $start_date = $search_data['start_date'];
        $destination_country = $search_data['destination_country'];
        $destination_state = $search_data['destination_state'];
        $destination_city = $search_data['destination_city'];
        $destination_date = $search_data['destination_date'];
        $page = $search_data["page"];
        $posts_per_page = $search_data["posts_per_page"];
        if ($posts_per_page) {
            $args["posts_per_page"] = $posts_per_page;
        }
        if ($page) {
            $args["paged"] = $page;
        }

        if ($start_city || $destination_city) {
            if (!empty($package_type)) {
                $tax_query[] = array(
                    'taxonomy' => 'type_package',
                    'field' => 'term_id',
                    'terms' => $package_type,
                    'operator' => 'IN'
                );
                $args['tax_query'] = $tax_query;
            }

            $meta_query = array(
                'relation' => 'AND',
                array(
                    'key' => 'carrier-ID',
                    'value' => -1,
                    'compare' => '=',
                )//we retrieve here all package that avec carrier-ID equal to -1 it mean that there is no transport offer select for this package
            );

            if ($start_city != "") {
                if ($start_country != "") {
                    $meta_query[] = array(
                        'key' => 'departure-country-package',
                        'value' => $start_country,
                        'compare' => 'LIKE',
                    );
                }
                if ($start_state != "") {
                    $meta_query[] = array(
                        'key' => 'departure-state-package',
                        'value' => $start_state,
                        'compare' => 'LIKE',
                    );
                } else {
                    $meta_query[] = array(
                        'key' => 'departure-city-package',
                        'value' => $start_city,
                        'compare' => 'LIKE',
                    );
                }
            }

            if ($start_date) {
                $meta_query[] = array(
                    'key' => 'date-of-departure-package',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                );
            }

            if ($destination_city) {
                if ($destination_country != "") {
                    $meta_query[] = array(
                        'key' => 'destination-country-package',
                        'value' => $destination_country,
                        'compare' => 'LIKE',
                    );
                }

                if ($destination_state != "") {
                    $meta_query[] = array(
                        'key' => 'destination-state-package',
                        'value' => $destination_state,
                        'compare' => 'LIKE',
                    );
                } else {
                    $meta_query[] = array(
                        'key' => 'destination-city-package',
                        'value' => $destination_city,
                        'compare' => 'LIKE',
                    );
                }
            }

            if ($destination_date) {
                $meta_query[] = array(
                    'key' => 'arrival-date-package',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                );
            }
            $args['meta_query'] = $meta_query;
        }
    }
    return $args;
}

// This Function return arguments of a query for main searching transport offers with start parameters
function getWPQueryArgsForMainCarrierSearchWithStartParameters($search_query_data = null) {
    $today = date('Y-m-d H:i:s', strtotime('today'));
    $args = array(
        'post_type' => 'transport-offer',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'post_date',
        'order' => 'DESC',
    );
//    if (is_user_logged_in()) {
//        $args["author__not_in"] = array(get_current_user_id());
//    }

    if ($search_query_data) {
//array containing city name, region name, and country name of start
        $start_country = $search_query_data['start_country'];
        $start_state = $search_query_data["start_state"];
        $start_city = $search_query_data["start_city"];
        $page = $search_query_data["page"];
        $posts_per_page = $search_query_data["posts_per_page"];
        if ($posts_per_page) {
            $args["posts_per_page"] = $posts_per_page;
        }
        if ($page) {
            $args["paged"] = $page;
        }
        if ($start_state == "" && $start_country == "") {
            $meta_query = array(
                'relation' => 'AND',
//                    array(
//                        'key' => 'transport-status',
//                        'value' => 3,
//                        'compare' => '!=',
//                    ),
                array(
                    'key' => 'deadline-of-proposition-transport-offer',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'departure-country-transport-offer',
                        'value' => $start_city,
                        'compare' => 'LIKE',
                    ),
                    array(
                        'key' => 'departure-state-transport-offer',
                        'value' => $start_city,
                        'compare' => 'LIKE',
                    ),
                    array(
                        'key' => 'departure-city-transport-offer',
                        'value' => $start_city,
                        'compare' => 'LIKE',
                    )
                )
            );
        } elseif ($start_state == "" && $start_country != "") {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'relation' => 'AND',
//                        array(
//                            'key' => 'transport-status',
//                            'value' => 3,
//                            'compare' => '!=',
//                        ),
                    array(
                        'key' => 'deadline-of-proposition-transport-offer',
                        'value' => $today,
                        'compare' => '>=',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => 'departure-state-transport-offer',
                        'value' => $start_country,
                        'compare' => 'LIKE',
                    ),
                    array(
                        'key' => 'departure-city-transport-offer',
                        'value' => $start_city,
                        'compare' => 'LIKE',
                    )
                ),
                array(
                    'relation' => 'AND',
//                        array(
//                            'key' => 'transport-status',
//                            'value' => 3,
//                            'compare' => '!=',
//                        ),
                    array(
                        'key' => 'deadline-of-proposition-transport-offer',
                        'value' => $today,
                        'compare' => '>=',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => 'departure-country-transport-offer',
                        'value' => $start_country,
                        'compare' => 'LIKE',
                    ),
                    array(
                        'key' => 'departure-city-transport-offer',
                        'value' => $start_city,
                        'compare' => 'LIKE',
                    )
                )
            );
        } else {
            $meta_query = array(
                'relation' => 'AND',
//                    array(
//                        'key' => 'transport-status',
//                        'value' => 3,
//                        'compare' => '!=',
//                    ),
                array(
                    'key' => 'deadline-of-proposition-transport-offer',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => 'departure-country-transport-offer',
                    'value' => $start_country,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => 'departure-state-transport-offer',
                    'value' => $start_state,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => 'departure-city-transport-offer',
                    'value' => $start_city,
                    'compare' => 'LIKE',
                )
            );
        }
        $args['meta_query'] = $meta_query;
    }

    return $args;
}

// This Function return arguments of a query for main searching transport offers with destination parameters
function getWPQueryArgsForMainCarrierSearchWithDestinationParameters($search_query_data = null) {
    $today = date('Y-m-d H:i:s', strtotime('today'));
    $args = array(
        'post_type' => 'transport-offer',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'post_date',
        'order' => 'DESC'
    );
//    if (is_user_logged_in()) {
//        $args["author__not_in"] = array(get_current_user_id());
//    }
    if ($search_query_data) {
//array containing city name, region name, and country name of destination
        $destination_country = $search_query_data['destination_country'];
        $destination_state = $search_query_data['destination_state'];
        $destination_city = $search_query_data['destination_city'];
        $page = $search_query_data["page"];
        $posts_per_page = $search_query_data["posts_per_page"];
        if ($posts_per_page) {
            $args["posts_per_page"] = $posts_per_page;
        }
        if ($page) {
            $args["paged"] = $page;
        }
        if ($destination_state == "" && $destination_country == "") {
            $meta_query = array(
                'relation' => 'AND',
//                    array(
//                        'key' => 'transport-status',
//                        'value' => 3,
//                        'compare' => '!=',
//                    ),
                array(
                    'key' => 'deadline-of-proposition-transport-offer',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                ),
                array(
                    'relation' => 'OR',
                    array(
                        'key' => 'destination-country-transport-offer',
                        'value' => $destination_city,
                        'compare' => 'LIKE',
                    ),
                    array(
                        'key' => 'destination-state-transport-offer',
                        'value' => $destination_city,
                        'compare' => 'LIKE',
                    ),
                    array(
                        'key' => 'destination-city-transport-offer',
                        'value' => $destination_city,
                        'compare' => 'LIKE',
                    )
                )
            );
        } elseif ($destination_state == "" && $destination_country != "") {
            $meta_query = array(
                'relation' => 'OR',
                array(
                    'relation' => 'AND',
//                        array(
//                            'key' => 'transport-status',
//                            'value' => 3,
//                            'compare' => '!=',
//                        ),
                    array(
                        'key' => 'deadline-of-proposition-transport-offer',
                        'value' => $today,
                        'compare' => '>=',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => 'destination-country-transport-offer',
                        'value' => $destination_country,
                        'compare' => 'LIKE',
                    ),
                    array(
                        'key' => 'destination-city-transport-offer',
                        'value' => $destination_city,
                        'compare' => 'LIKE',
                    )
                ),
                array(
                    'relation' => 'AND',
//                        array(
//                            'key' => 'transport-status',
//                            'value' => 3,
//                            'compare' => '!=',
//                        ),
                    array(
                        'key' => 'deadline-of-proposition-transport-offer',
                        'value' => $today,
                        'compare' => '>=',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => 'destination-state-transport-offer',
                        'value' => $destination_country,
                        'compare' => 'LIKE',
                    ),
                    array(
                        'key' => 'destination-city-transport-offer',
                        'value' => $destination_city,
                        'compare' => 'LIKE',
                    )
                )
            );
        } else {
            $meta_query = array(
                'relation' => 'AND',
//                    array(
//                        'key' => 'transport-status',
//                        'value' => 3,
//                        'compare' => '!=',
//                    ),
                array(
                    'key' => 'deadline-of-proposition-transport-offer',
                    'value' => $today,
                    'compare' => '>=',
                    'type' => 'DATE'
                ),
                array(
                    'key' => 'destination-country-transport-offer',
                    'value' => $destination_country,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => 'destination-state-transport-offer',
                    'value' => $destination_state,
                    'compare' => 'LIKE',
                ),
                array(
                    'key' => 'destination-city-transport-offer',
                    'value' => $destination_city,
                    'compare' => 'LIKE',
                )
            );
        }
        $args['meta_query'] = $meta_query;
    }

    return $args;
}

function load_cities_db($country_name) {
    require('php-excel-reader/excel_reader2.php');

    require('SpreadsheetReader.php');

    $Reader = new SpreadsheetReader(wp_normalize_path(WPMU_PLUGIN_DIR) . '/gpdeal-functions/cities.xlsx');
    $Sheets = $Reader->Sheets();
    $args = array(
        'post_type' => 'city',
        "post_status" => 'publish',
        'posts_per_page' => 1
    );
    foreach ($Sheets as $Index => $Name) {
        if ($Name == $country_name) {
            $Reader->ChangeSheet($Index);
            $i = 0;
            foreach ($Reader as $Row) {
                if ($i != 0) {
                    $args['title'] = esc_attr(trim($Row[0]));
                    $args['meta_query'] = array(
                        'relation' => 'AND',
                        array(
                            'key' => 'region',
                            'value' => esc_attr(trim($Row[1])),
                            'compare' => '=',
                        ),
                        array(
                            'key' => 'country',
                            'value' => esc_attr(trim($Row[2])),
                            'compare' => '=',
                        )
                    );
                    $cities = new WP_Query($args);
                    if (!$cities->have_posts()) {
                        saveCity($Row[0], $Row[1], $Row[2]);
                    }
                    wp_reset_postdata();
                }
                $i++;
            }
            break;
        }
    }
    echo 'Cities Database of ' . $country_name . " loaded successfully !";
}

//Function to save city loaded to xlsx files in database as post_type city
function saveCity($city, $region, $country) {
    $post_args = array(
        'post_title' => esc_attr(trim(($city))),
        'post_type' => 'city',
        'post_status' => 'publish',
        'meta_input' => array(
            'city' => esc_attr(trim(($city))),
            'region' => esc_attr(trim(($region))),
            'country' => esc_attr(trim(($country)))
        )
    );
    $post_id = wp_insert_post($post_args, true);

    if (is_wp_error($post_id)) {
        echo "Echec de l'enregistrement de " . $city . " " . $region . " " . $country;
        exit;
    }
}

//Function to url of attachment by it filename
function get_attachment_url_by_filename($filename) {
    $args = array(
        'posts_per_page' => 1,
        'post_type' => 'attachment',
        'name' => trim($filename),
    );
    $get_posts = new Wp_Query($args);

    $file = $get_posts ? array_pop($get_posts) : null;
    return $file ? wp_get_attachment_url($file->ID) : '';
}

function getRegionByCityAndCountry($city, $country) {
    $region = "";
    $args = array(
        'post_type' => 'city',
        "post_status" => 'publish',
        'posts_per_page' => 1,
        'title' => $city,
        'meta_query' => array(
            array(
                'key' => 'country',
                'value' => $country,
                'compare' => '='
            )
        )
    );
    $cities = new WP_Query($args);
    if ($cities->have_posts()) {
        while ($cities->have_posts()) {
            $cities->the_post();
            $region = get_post_meta(get_the_ID(), 'region', true);
        }
    }
    wp_reset_postdata();
    return $region;
}

function getRegionByCityAndCountry_tmp($city, $country) {
    $region = "";
    $args = array(
        'post_type' => 'city',
        "post_status" => 'publish',
        'posts_per_page' => 1,
        'meta_query' => array(
            'relation' => 'AND',
            array(
                'key' => 'city',
                'value' => $city,
                'compare' => '='
            ),
            array(
                'key' => 'country',
                'value' => $country,
                'compare' => '='
            )
        )
    );
    $cities = new WP_Query($args);
    if ($cities->have_posts()) {
        while ($cities->have_posts()) {
            $cities->the_post();
            $region = get_post_meta(get_the_ID(), 'region', true);
        }
    }
    wp_reset_postdata();
    return $region;
}

//Generate a array containing city, region, country from google places api city
function getCountryRegionCityInformations($locality) {
    $country_region_city = array();
    if ($locality && $locality != "") {
        $country = "";
        $state = "";
        $city = $locality;
//array containing city name, region name, and country name of start
        $localities = explode(", ", $city);
        if (count($localities) == 2) {
            $city = $localities[0];
            $country = $localities[1];
            $state = getRegionByCityAndCountry($city, $country);
        } elseif (count($localities) == 3) {
            $city = $localities[0];
            $state = $localities[1];
            $country = $localities[2];
        }
        $country_region_city = array(
            "country" => $country,
            "region" => $state,
            "city" => $city
        );
    }
    return $country_region_city;
}

/**
 *  Given a file, i.e. /css/base.css, replaces it with a string containing the
 *  file's mtime, i.e. /css/base.1221534296.css.
 *  
 *  @param $file  The file to be loaded.  Must be an absolute path (i.e.
 *                starting with slash).
 */
function auto_version($file) {
    if (strpos($file, '/') !== 0 || !file_exists($_SERVER['DOCUMENT_ROOT'] . $file))
        return $file;

    $mtime = filemtime($_SERVER['DOCUMENT_ROOT'] . $file);
    return preg_replace('{\\.([^./]+)$}', ".$mtime.\$1", $file);
}

function expire_session() {
    if (is_user_logged_in()) {
        if (!$_SESSION['REMEMBER_ME']) {
            // last request was more than 5 minutes ago
            if ((time() - $_SESSION['LAST_ACTIVITY'] > 60 * 60)) {
                unset($_SESSION['LAST_ACTIVITY']);     // unset $_SESSION variable for the run-time 
                //session_destroy();   // destroy session data in storage
                wp_logout();
                wp_safe_redirect(home_url('/'));
                exit;
            } else {
                $_SESSION['LAST_ACTIVITY'] = time(); // update last activity time stamp
            }
        }
    }
}

/* This function find all user of unsatisfied package and send mail to them when a new transport offer is added and it can satisfied him. The mail is send
  even if this user have his field <<receive-notification>> egal to yes */

function gpdeal_send_notification_unsatisfied_package_user($post_ID) {
    $type_package = wp_get_post_terms($post_ID, 'type_package', array("fields" => "ids"));
    $start_country = get_post_meta($post_ID, 'departure-country-transport-offer', true);
    $start_state = get_post_meta($post_ID, 'departure-state-transport-offer', true);
    $start_city = get_post_meta($post_ID, 'departure-city-transport-offer', true);
    $start_date = date('d-m-Y', strtotime(get_post_meta($post_ID, 'date-of-departure-transport-offer', true)));
    $destination_country = get_post_meta($post_ID, 'destination-country-transport-offer', true);
    $destination_state = get_post_meta($post_ID, 'destination-state-transport-offer', true);
    $destination_city = get_post_meta($post_ID, 'destination-city-transport-offer', true);
    $destination_date = date('d-m-Y', strtotime(get_post_meta($post_ID, 'arrival-date-transport-offer', true)));

    $search_data = array(
        "type_package" => $type_package,
        "start_country" => $start_country,
        "start_state" => $start_state,
        "start_city" => $start_city,
        "start_date" => $start_date,
        "destination_country" => $destination_country,
        "destination_state" => $destination_state,
        "destination_city" => $destination_city,
        "destination_date" => $destination_date
    );

    $packages_query = new WP_Query(getWPQueryArgsForUnsatifiedSendPackagesWithCanInterest($search_data));
    $exclude_ids = array();
    if ($packages_query->have_posts()) {
        $packages = $packages_query->posts;
        foreach ($packages as $package) {
            $exclude_ids[] = $package->ID;
            $user = get_user_by('id', get_post_field('post_author', $package->ID));
            //if (get_user_meta($user->ID, 'receive-notifications', true) == "yes" && get_post_meta(get_the_ID(), 'transport-offer-alert', true) == 2) {
            if (get_post_meta($package->ID, 'transport-offer-alert', true) == 2) {
                $headers[] = 'MIME-Version: 1.0';
                $headers[] = 'Content-Type: text/html; charset=UTF-8';
                $headers[] = 'From: Global Parcel Deal - Informations <infos@gpdeal.com>';
                $headers[] = 'Bcc:<erictonyelissouck@yahoo.fr>';

                $subject = "Global Parcel Deal - " . __("Alert transport offer(s)", "gpdealdomain");

                ob_start();
                ?>
                <div style="font-size: 12.8px;"><?php echo __("Hello", "gpdealdomain"); ?> <?php echo $user->data->user_login; ?> ! </div>
                <div><br></div>
                <div style="font-size: 12.8px;"><?php _e("We have a (or the) valid transport offer(s) that may be of interest to you for your shipment", "gpdealdomain"); ?> <?php _e("from", "gpdealdomain"); ?> <span ><?php echo get_post_meta($package->ID, 'departure-city-package', true); ?>(<?php echo date('d-m-Y', strtotime(get_post_meta($package->ID, 'date-of-departure-package', true))); ?>)</span> <?php _e("to", "gpdealdomain"); ?> <span><?php echo get_post_meta($package->ID, 'destination-city-package', true); ?>(<?php echo date('d-m-Y', strtotime(get_post_meta($package->ID, 'arrival-date-package', true))); ?>)</span>
                    N°<a href="<?php echo get_permalink($package->ID); ?>" ><?php echo get_post_field('post_title', $package->ID) ?></a>.</div>
                <div><br></div>
                <div style="font-size: 12.8px;">
                    <a href="<?php echo esc_url(add_query_arg(array('package-id' => $package->ID), get_permalink(get_page_by_path(__("select-transport-offers", "gpdealdomain"))->ID))); ?>" ><?php _e("Log_in_plural_second_person", "gpdealdomain"); ?></a> <?php _e("to search and select the offer that suits you", "gpdealdomain"); ?>.
                </div>
                <div><br></div>
                <div style="font-size: 12.8px;"><?php _e("Thank you for your loyalty", "gpdealdomain"); ?>.</div>
                <div><br></div>
                <div>
                    <p style="margin:0px;padding:0px;border:0px;font-size:12.8px;font-stretch:normal;line-height:normal;font-family:Tahoma;width:auto;height:auto;float:none;color:rgb(0,0,0)"><?php _e("Thank you for using GPDEAL", "gpdealdomain"); ?>,</p>
                    <p style="margin:0px 0px 1em;padding:0px;border:0px;font-size:12.8px;font-stretch:normal;line-height:normal;font-family:Tahoma;width:auto;height:auto;float:none;color:rgb(0,0,0)"><?php _e("The team", "gpdealdomain"); ?> Global Parcel Deal.</p>
                    <p><a href="<?php echo home_url('/'); ?>"><img src="<?php echo get_template_directory_uri() ?>/assets/images/logo_gpdeal.png" style="width: 115px;"></a></p>
                </div>

                <?php
                $body = ob_get_contents();
                ob_end_clean();
                wp_mail($user->data->user_email, $subject, $body, $headers);
            }
        }
    }
}

//This function update transport offer status and set it to 2 when the transport offer is expired (limit date is passed)
function updateStatusAllExpiredOffers() {
    $today = date('Y-m-d H:i:s', strtotime('today'));
    $transport_offers = new WP_Query(array('post_type' => 'transport-offer', 'posts_per_page' => -1, "post_status" => 'publish', 'meta_query' => array('relation' => 'AND', array('relation' => 'OR', array('key' => 'transport-status', 'value' => 1, 'compare' => '='), array('key' => 'transport-status', 'value' => -1, 'compare' => '='), array('key' => 'transport-status', 'value' => 3, 'compare' => '=')), array('key' => 'deadline-of-proposition-transport-offer', 'value' => $today, 'compare' => '<', 'type' => 'DATE'))));
    $i = 0;
    if ($transport_offers->have_posts()) {
        while ($transport_offers->have_posts()) {
            $transport_offers->the_post();
            update_post_meta(get_the_ID(), 'transport-status', 2);
            $i++;
        }
        wp_reset_postdata();
    }
    return $i . " transport offers expired had been updated";
}

//This function update transport offer status and set it to 4 when the transport offer is ended (destination date is passed)
function updateStatusAllEndedOffers() {
    $today = date('Y-m-d H:i:s', strtotime('today'));
    $transport_offers = new WP_Query(array('post_type' => 'transport-offer', 'posts_per_page' => -1, "post_status" => 'publish', 'meta_query' => array('relation' => 'AND', array('relation' => 'OR', array('key' => 'transport-status', 'value' => 2, 'compare' => '='), array('key' => 'transport-status', 'value' => 3, 'compare' => '=')), array('key' => 'arrival-date-transport-offer', 'value' => $today, 'compare' => '<', 'type' => 'DATE'))));
    $i = 0;
    if ($transport_offers->have_posts()) {
        while ($transport_offers->have_posts()) {
            $transport_offers->the_post();
            update_post_meta(get_the_ID(), 'transport-status', 4);
            $i++;
        }
        wp_reset_postdata();
    }
    return $i . " transport offers ended had been updated";
}

//Function for posting data using curl at a specific url
function postDataByCurl($postData, $url) {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    $response = curl_exec($ch);
    curl_close($ch);
    return $response;
}

//This function calculate difference between departure date and arrival date given as string and return number of day between this two dates
function gpdeal_date_diff($strStart, $strEnd) {
    $startDate = new DateTime($strStart);
    $endDate = new DateTime($strEnd);
    return $startDate->diff($endDate)->days;
}

//This function return an icon name of a specific label by his name
function getIconNameByLabelName($labelName) {
    if (__($labelName, "gpdealdomain") == __("Courrier", "gpdealdomain") && __($labelName, "gpdealdomain") == __("Letter", "gpdealdomain")) {
        return "mail outline";
    }
    if (__($labelName, "gpdealdomain") == __("Colis", "gpdealdomain") && __($labelName, "gpdealdomain") == __("Parcel", "gpdealdomain")) {
        return "travel";
    }
    if (__($labelName, "gpdealdomain") == __("Autre", "gpdealdomain") && __($labelName, "gpdealdomain") == __("Other", "gpdealdomain")) {
        return "cubes";
    }
}

function GetDistanceBetweenTwoCities($start_city, $destination_city, $transport_mode) {
    $url = "https://maps.googleapis.com/maps/api/distancematrix/json?origins=" . urlencode($start_city) . "&destinations=" . urlencode($destination_city) . "&mode=" . urlencode($transport_mode) . "&language=en-US&key=AIzaSyDSzKtRmgspnJ9NsO294SyVFZmXLSuLtVo";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_PROXYPORT, 3128);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);
    $response_a = json_decode($response, true);
    if (strtolower($response_a['status']) == "ok") {
        $dist = $response_a['rows'][0]['elements'][0]['distance']['value'];
        $time = $response_a['rows'][0]['elements'][0]['duration']['value'];
    } else {
        $dist = -1;
        $time = -1;
    }
    //return array('distance' => $dist, 'time' => $time);
    return $response_a;
}

function gpdealDistanceBetweenTwoCities($start_city, $destination_city) {
    $start_city_poi = \src\Gpdeal\POI::getPOIByAddress($start_city);
    $destination_city_poi = \src\Gpdeal\POI::getPOIByAddress($destination_city);
    return $start_city_poi->getDistanceInMetersTo($destination_city_poi);
}

function getCostOfTransportOffer($D_between_start_destination, $L, $l, $h, $weight, $transport_method, $coutkgkm, $currency) {
    //Get Volumetric Constante
    switch ($transport_method) {
        case "air":
            $VC = 167;
            break;
        case "earth":
            $VC = 333;
            break;
        case "water":
            $VC = 1000;
            break;
        default :
            $VC = 0;
            break;
    }
    //Calculate Volume of shipment
    if ($L && $l && $h) {
        $V = $L * $l * $h / 1000000;
    } else {
        $V = 0;
    }
    //Calculate Volumetric weight of shipment
    $VW = $VC * $V;
    //Compare Volumetric weight with weight
    if ($weight && $weight < $VW) {
        $weight = $VW;
    }

    $price = $coutkgkm * $D_between_start_destination * $weight;
    //% Paypal + % GPDeal
    //$perc = 20 / 100;
    //Static Paypal fees
    //$paypalSFees = 0.33;
    //New price of transport offer
    //$price = ($price + $paypalSFees) / (1 - $perc);
    if ($currency != "USD") {
        $currency_convetion = getLastCurrencyAmountFromUSD($currency);
        $price = $currency_convetion * $price;
    }
    //return $V."/".$VW."/".$weight."/".$price;
    //return $D_between_start_destination . "/" . $V . "/" . $VW . "/" . $weight . "/" . $currency_convetion. "/" . ceil($price);
    return ceil($price);
}

//Function use to convert amount from one currency to another
function getLastCurrencyAmountFromUSD($currency = null) {
    $service_url = 'https://openexchangerates.org/api/latest.json?app_id=929f707ddcb34335807f3f27d1ad789d';
    $curl = curl_init($service_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $curl_response = curl_exec($curl);
    if ($curl_response === false) {
        return null;
    }
    curl_close($curl);
    $convert_response = json_decode($curl_response, true);
    return floatval($convert_response["rates"]["$currency"]);
}

function ip_visitor_data() {
    $client = @$_SERVER['HTTP_CLIENT_IP'];
    $forward = @$_SERVER['HTTP_X_FORWARDED_FOR'];
    $remote = $_SERVER['REMOTE_ADDR'];
    $country = "Unknown";

    if (filter_var($client, FILTER_VALIDATE_IP)) {
        $ip = $client;
    } elseif (filter_var($forward, FILTER_VALIDATE_IP)) {
        $ip = $forward;
    } else {
        $ip = $remote;
    }
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://www.geoplugin.net/json.gp?ip=" . $ip);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    $ip_data_in = curl_exec($ch); // string
    curl_close($ch);

    $ip_data = json_decode($ip_data_in, true);
//    $ip_data = str_replace('&quot;', '"', $ip_data); // for PHP 5.2 see stackoverflow.com/questions/3110487/
//
//    if($ip_data && $ip_data['geoplugin_countryName'] != null) {
//        $country = $ip_data['geoplugin_countryName'];
//    }
//    return 'IP: '.$ip.' # Country: '.$country;
    return $ip_data;
}
