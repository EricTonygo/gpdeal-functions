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

$sitekey = '6LfoxhcUAAAAAL3L_vo5dnG1csXgdaYYf5APUTqn'; // votre clé publique

add_action('after_setup_theme', 'my_theme_supports');

add_action('init', 'my_custom_init');

function my_awesome_mail_content_type() {
    return "text/html";
}

add_filter("wp_mail_content_type", "my_awesome_mail_content_type");

function wpb_sender_email($original_email_address) {
    if ($original_email_address == 'wordpress@test.gpdeal.com') {
        return 'contact@test.gpdeal.com';
    } else {
        return $original_email_address;
    }
}

//This function Un-quotes a quoted string even if it is more than one
function removeslashes($string) {
    $string = implode("", explode("\\", $string));
    return stripslashes(trim($string));
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

function woocommerce_support() {
    add_theme_support('woocommerce');
}

function childtheme_formats() {
    //Enable a support thumbnail
    add_theme_support('post-thumbnails');
    add_theme_support('post-formats', array('aside', 'gallery', 'link'));
}

function my_theme_supports() {
    woocommerce_support();
    childtheme_formats();
    remove_theme_supports();
}

function remove_theme_supports() {
    //remove_post_type_support('package', 'editor');
    //remove_post_type_support('transport-offer', 'editor');
}

//Add additional role customer for every user because we want to use it in woocommerce
add_action('user_register', 'add_secondary_role', 10, 1);

function add_secondary_role($user_id) {

    $user = get_user_by('id', $user_id);
    $user->add_role('customer');
}

//Check whether a user has a specifique role
function get_user_roles_by_user_id($user_id) {
    $user = get_userdata($user_id);
    return empty($user) ? array() : $user->roles;
}

function get_user_role_by_user_id($user_id) {
    $user = get_userdata($user_id);
    $roles = $user->roles;
    if (in_array('particular', $roles)) {
        return __('Particulier', 'gpdealdomain');
    } elseif (in_array('professional', $roles)) {
        return __('Professionnel', 'gpdealdomain');
    } elseif (in_array('enterprise', $roles)) {
        return __('Entreprise', 'gpdealdomain');
    } else {
        return "";
    }
}

//Get a role of user (particular, professional or enterprise
function get_role_of_user($user_id) {
    $user = get_userdata($user_id);
    return empty($user) ? array() : $user->roles;
}

function post_type_transport_offer_init() {
    $labels = array(
        'name' => _x('Transport offers', 'post type general name', 'gpdealdomain'),
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

function my_custom_init() {
    add_role('particular', __('Particular', 'gpdealDomain'), array('read' => true, 'publish_posts' => true, 'edit_posts' => true));
    add_role('professional', __('Professional', 'gpdealDomain'), array('read' => true, 'publish_posts' => true, 'edit_posts' => true));
    add_role('enterprise', __('Enterprise', 'gpdealDomain'), array('read' => true, 'publish_posts' => true, 'edit_posts' => true));
    post_type_transport_offer_init();
    post_type_package_init();
    post_type_question_init();
    post_type_term_use_init();
    post_type_city_init();
    create_transport_offer_taxonomies();
    addUserCustomsField();
    add_my_featured_image_to_home();
}

function get_published_questions() {
    $posts = query_posts(array(
        'post_type' => 'question',
        'post_per_page' => -1,
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

function upload_user_file($file = array(), $parent_post_id = 0) {

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
                $json = array("message" => "Nous n'avons pas pu verifier votre code de sécurité. Verifiez le puis essayez à nouveau");
                return wp_send_json_error($json);
            }
            $user_login = removeslashes(esc_attr(trim($_POST['username'])));
            $user_email = removeslashes(esc_attr(trim($_POST['email'])));
            $unique_user_email = get_user_by('email', $user_email);
            $unique_user_login = get_user_by('login', $user_login);
            if ($unique_user_login) {
                $json = array("message" => "Un utilisateur avec ce pseudo existe déjà veuillez le modifier");
                return wp_send_json_error($json);
            } elseif ($unique_user_email) {
                $json = array("message" => "Un utilisateur avec cet email existe déjà veuillez le modifier");
                return wp_send_json_error($json);
            } else {
                $json = array("message" => "Ajout possible");
                return wp_send_json_success($json);
            }
        }
    } elseif ($_POST['g-recaptcha-response-register']) {
//        if (!verify_use_grecaptcha($_POST['g-recaptcha-response-register'])) {
//            // What happens when the CAPTCHA was entered incorrectly
//            $_SESSION['error_message'] = "Nous n'avons pas pu verifier votre code de sécurité. Verifiez le puis essayez à nouveau :".$_POST['g-recaptcha-response-register'];
//        } else {
        $role = removeslashes(esc_attr(trim($_POST['role'])));
        if ($role == "particular") {
            $user_login = removeslashes(esc_attr(trim($_POST['username'])));
            $user_pass = esc_attr($_POST['password']);
            $user_email = removeslashes(esc_attr(trim($_POST['email'])));
            $first_name = removeslashes(esc_attr(trim($_POST['first_name'])));
            $last_name = removeslashes(esc_attr(trim($_POST['last_name'])));
            $birthdate = removeslashes(esc_attr(trim($_POST['birthdate'])));
            $gender = removeslashes(esc_attr(trim($_POST['gender'])));
            $number_street = removeslashes(esc_attr(trim($_POST['number_street'])));
            $complement_address = removeslashes(esc_attr(trim($_POST['complement_address'])));
            $locality = removeslashes(esc_attr(trim($_POST['locality'])));
            $country_region_city = getCountryRegionCityInformations($locality);
            $mobile_phone_number = removeslashes(esc_attr(trim($_POST['mobile_phone_number'])));
            $test_question_ID = removeslashes(esc_attr(trim($_POST['test_question'])));
            $answer_test_question = removeslashes(esc_attr(trim($_POST['answer_test_question'])));
            $receive_notifications = removeslashes(esc_attr(trim($_POST['receive_notifications'])));



            $new_user_data = array(
                'user_login' => $user_login,
                'user_pass' => $user_pass,
                'user_email' => $user_email,
                'role' => $role,
                'first_name' => $first_name,
                'last_name' => $last_name
            );

            $user_id = wp_insert_user($new_user_data);
            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'plain-text-password', $user_pass);
                update_user_meta($user_id, 'birthdate', date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $birthdate))));
                update_user_meta($user_id, 'gender', $gender);
                update_user_meta($user_id, 'number-street', $number_street);
                update_user_meta($user_id, 'complement-address', $complement_address);
                update_user_meta($user_id, 'country', $country_region_city['country']);
                update_user_meta($user_id, 'region-province-state', $country_region_city['region']);
                update_user_meta($user_id, 'commune-city-locality', $country_region_city['city']);
                update_user_meta($user_id, 'mobile-phone-number', $mobile_phone_number);
                update_user_meta($user_id, 'test-question-ID', $test_question_ID);
                update_user_meta($user_id, 'answer-test-question', $answer_test_question);
                update_user_meta($user_id, 'identity-status', 0);
                if ($receive_notifications && $receive_notifications == 'on') {
                    update_user_meta($user_id, 'receive-notifications', 'yes');
                } else {
                    update_user_meta($user_id, 'receive-notifications', 'no');
                }
            }
        } elseif ($role == "professional" || $role == "enterprise") {
            $user_login_pro = removeslashes(esc_attr(trim($_POST['company_name'])));
            $user_pass_pro = esc_attr($_POST['password_pro']);
            $user_email_pro = removeslashes(esc_attr(trim($_POST['email_pro'])));
            $civility_represntative1_pro = removeslashes(esc_attr(trim($_POST['civility_representative1'])));
            $first_name_representative1_pro = removeslashes(esc_attr(trim($_POST['first_name_representative1'])));
            $last_name_representative1_pro = removeslashes(esc_attr(trim($_POST['last_name_representative1'])));
            $email_representative1_pro = removeslashes(esc_attr(trim($_POST['email_representative1'])));
            $function_representative1_pro = removeslashes(esc_attr(trim($_POST['function_representative1'])));
            $mobile_phone_number_representative1_pro = removeslashes(esc_attr(trim($_POST['mobile_phone_number_representative1'])));
            $civility_represntative2_pro = removeslashes(esc_attr(trim($_POST['civility_represntative2'])));
            $first_name_representative2_pro = removeslashes(esc_attr(trim($_POST['first_name_representative2'])));
            $last_name_representative2_pro = removeslashes(esc_attr(trim($_POST['last_name_representative2'])));
            $email_representative2_pro = removeslashes(esc_attr(trim($_POST['email_representative2'])));
            $function_representative2_pro = removeslashes(esc_attr(trim($_POST['function_representative2'])));
            $mobile_phone_number_representative2_pro = removeslashes(esc_attr(trim($_POST['mobile_phone_number_representative2'])));
            $company_name_pro = removeslashes(esc_attr(trim($_POST['company_name'])));
            $company_legal_form_pro = removeslashes(esc_attr(trim($_POST['company_legal_form'])));
            $company_identity_number_pro = removeslashes(esc_attr(trim($_POST['company_identity_number'])));
            $company_identity_tva_number_pro = removeslashes(esc_attr(trim($_POST['company_identity_tva_number'])));
            $number_street_pro = removeslashes(esc_attr(trim($_POST['number_street'])));
            $complement_address_pro = removeslashes(esc_attr(trim($_POST['complement_address'])));
            $locality_pro = removeslashes(esc_attr(trim($_POST['locality_pro'])));
            $country_region_city_pro = getCountryRegionCityInformations($locality_pro);
            $postal_code_pro = removeslashes(esc_attr(trim($_POST['postal_code'])));
            $home_phone_number_pro = removeslashes(esc_attr(trim($_POST['home_phone_number'])));
            $test_question_ID_pro = removeslashes(esc_attr(trim($_POST['test_question_pro'])));
            $answer_test_question_pro = removeslashes(esc_attr(trim($_POST['answer_test_question_pro'])));
            $receive_notifications_pro = removeslashes(esc_attr(trim($_POST['receive_notifications'])));


            $new_user_data = array(
                'user_login' => $user_login_pro,
                'user_pass' => $user_pass_pro,
                'user_email' => $user_email_pro,
                'role' => $role,
                'first_name' => "",
                'last_name' => $company_name_pro
            );

            $user_id = wp_insert_user($new_user_data);

            if (!is_wp_error($user_id)) {
                if (!empty($_FILES['company_logo'])) {
                    $logo_pro = $_FILES['company_logo'];
                    $attachment_id = upload_user_file($logo_pro);
                    update_user_meta($user_id, 'company_logo_ID', $attachment_id);
                }
                if (!empty($_FILES['company_attachments'])) {
                    $company_attachements = $_FILES['company_attachments'];
                    $company_attachements_ids = array();
                    foreach ($company_attachements as $company_attachement) {
                        $attachment_id = upload_user_file($company_attachement);
                        $company_attachements_ids[] = $attachment_id;
                    }
                    update_user_meta($user_id, 'company_attachements_IDs', $company_attachements_ids);
                }
                update_user_meta($user_id, 'plain-text-password', $user_pass_pro);
                update_user_meta($user_id, 'company-name', $company_name_pro);
                update_user_meta($user_id, 'company-legal-form', $company_legal_form_pro);
                update_user_meta($user_id, 'company-identity-number', $company_identity_number_pro);
                update_user_meta($user_id, 'company-identity-tva-number', $company_identity_tva_number_pro);
                update_user_meta($user_id, 'number-street', $number_street_pro);
                update_user_meta($user_id, 'complement-address', $complement_address_pro);
                update_user_meta($user_id, 'country', $country_region_city_pro['country']);
                update_user_meta($user_id, 'region-province-state', $country_region_city_pro['region']);
                update_user_meta($user_id, 'commune-city-locality', $country_region_city_pro['city']);
                update_user_meta($user_id, 'postal-code', $postal_code_pro);
                update_user_meta($user_id, 'home-phone-number', $home_phone_number_pro);
                update_user_meta($user_id, 'civility-representative1', $civility_represntative1_pro);
                update_user_meta($user_id, 'first-name-representative1', $first_name_representative1_pro);
                update_user_meta($user_id, 'last-name-representative1', $last_name_representative1_pro);
                update_user_meta($user_id, 'company-function-representative1', $function_representative1_pro);
                update_user_meta($user_id, 'mobile-phone-number-representative1', $mobile_phone_number_representative1_pro);
                update_user_meta($user_id, 'email-representative1', $email_representative1_pro);
                update_user_meta($user_id, 'civility-representative2', $civility_represntative2_pro);
                update_user_meta($user_id, 'first-name-representative2', $first_name_representative2_pro);
                update_user_meta($user_id, 'last-name-representative2', $last_name_representative2_pro);
                update_user_meta($user_id, 'company-function-representative2', $function_representative2_pro);
                update_user_meta($user_id, 'mobile-phone-number-representative1', $mobile_phone_number_representative2_pro);
                update_user_meta($user_id, 'email-representative2', $email_representative2_pro);
                update_user_meta($user_id, 'test-question-ID', $test_question_ID_pro);
                update_user_meta($user_id, 'answer-test-question', $answer_test_question_pro);
                update_user_meta($user_id, 'identity-status', 0);
                if ($receive_notifications_pro && $receive_notifications_pro == 'on') {
                    update_user_meta($user_id, 'receive-notifications', 'yes');
                } else {
                    update_user_meta($user_id, 'receive-notifications', 'no');
                }
            }
        }

        if (!is_wp_error($user_id)) {
            // Set the global user object
            $current_user = get_user_by('id', $user_id);

            // set the WP login cookie
            $secure_cookie = is_ssl() ? true : false;
            wp_set_auth_cookie($user_id, true, $secure_cookie);
            wp_safe_redirect(get_permalink(get_page_by_path(__('mon-compte', 'gpdealdomain'))));
            exit;
        } else {
            wp_safe_redirect(get_permalink(get_page_by_path(__('inscription', 'gpdealdomain'))));
            exit;
        }
//        }
    } else {
        $_SESSION['error_message'] = "Code de sécurité introuvable";
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
                $json = array("message" => "Un utilisateur avec ce pseudo existe déjà veuillez le modifier");
                return wp_send_json_error($json);
            } elseif ($unique_user_email && $unique_user_email->ID != $user_id) {
                $json = array("message" => "Un utilisateur avec cet email existe déjà veuillez le modifier");
                return wp_send_json_error($json);
            } else {
                $json = array("message" => "Modification possible");
                return wp_send_json_success($json);
            }
        }
    } else {
        $role = removeslashes(esc_attr(trim($_POST['role'])));
        if ($role == "particular") {
            $user_login = removeslashes(esc_attr(trim($_POST['username'])));
            //$user_pass = removeslashes(esc_attr($_POST['password']);
            $user_email = removeslashes(esc_attr(trim($_POST['email'])));
            $first_name = removeslashes(esc_attr(trim($_POST['first_name'])));
            $last_name = removeslashes(esc_attr(trim($_POST['last_name'])));
            $birthdate = removeslashes(esc_attr(trim($_POST['birthdate'])));
            $gender = removeslashes(esc_attr(trim($_POST['gender'])));
            $number_street = removeslashes(esc_attr(trim($_POST['number_street'])));
            $complement_address = removeslashes(esc_attr(trim($_POST['complement_address'])));
            $locality = removeslashes(esc_attr(trim($_POST['locality'])));
            $country_region_city = getCountryRegionCityInformations($locality);
            $mobile_phone_number = removeslashes(esc_attr(trim($_POST['mobile_phone_number'])));
            $test_question_ID = removeslashes(esc_attr(trim($_POST['test_question'])));
            $answer_test_question = removeslashes(esc_attr(trim($_POST['answer_test_question'])));
            $receive_notifications = removeslashes(esc_attr(trim($_POST['receive_notifications'])));


            $update_user_data = array(
                'ID' => $user_id,
                'user_login' => $user_login,
                //'user_pass' => $user_pass,
                'user_email' => $user_email,
                'role' => $role,
                'first_name' => $first_name,
                'last_name' => $last_name
            );

            $user_id = wp_update_user($update_user_data);
            //update_user_meta($user_id, 'plain-text-password', $user_pass);
            if (!is_wp_error($user_id)) {
                update_user_meta($user_id, 'birthdate', date('Y-m-d H:i:s', strtotime(str_replace('/', '.', $birthdate))));
                update_user_meta($user_id, 'gender', $gender);
                update_user_meta($user_id, 'number-street', $number_street);
                update_user_meta($user_id, 'complement-address', $complement_address);
                update_user_meta($user_id, 'country', $country_region_city['country']);
                update_user_meta($user_id, 'region-province-state', $country_region_city['region']);
                update_user_meta($user_id, 'commune-city-locality', $country_region_city['city']);
                update_user_meta($user_id, 'mobile-phone-number', $mobile_phone_number);
                update_user_meta($user_id, 'test-question-ID', $test_question_ID);
                update_user_meta($user_id, 'answer-test-question', $answer_test_question);
                if ($receive_notifications && $receive_notifications == 'on') {
                    update_user_meta($user_id, 'receive-notifications', 'yes');
                } else {
                    update_user_meta($user_id, 'receive-notifications', 'no');
                }
            }
        } elseif ($role == "professional" || $role == "enterprise") {
            $user_login_pro = removeslashes(esc_attr(trim($_POST['company_name'])));
            //$user_pass_pro = removeslashes(esc_attr($_POST['password']);
            $user_email_pro = removeslashes(esc_attr(trim($_POST['email_pro'])));
            $civility_represntative1_pro = removeslashes(esc_attr(trim($_POST['civility_representative1'])));
            $first_name_representative1_pro = removeslashes(esc_attr(trim($_POST['first_name_representative1'])));
            $last_name_representative1_pro = removeslashes(esc_attr(trim($_POST['last_name_representative1'])));
            $email_representative1_pro = removeslashes(esc_attr(trim($_POST['email_representative1'])));
            $function_representative1_pro = removeslashes(esc_attr(trim($_POST['function_representative1'])));
            $mobile_phone_number_representative1_pro = removeslashes(esc_attr(trim($_POST['mobile_phone_number_representative1'])));
            $civility_represntative2_pro = removeslashes(esc_attr(trim($_POST['civility_represntative2'])));
            $first_name_representative2_pro = removeslashes(esc_attr(trim($_POST['first_name_representative2'])));
            $last_name_representative2_pro = removeslashes(esc_attr(trim($_POST['last_name_representative2'])));
            $email_representative2_pro = removeslashes(esc_attr(trim($_POST['email_representative2'])));
            $function_representative2_pro = removeslashes(esc_attr(trim($_POST['function_representative2'])));
            $mobile_phone_number_representative2_pro = removeslashes(esc_attr(trim($_POST['mobile_phone_number_representative2'])));
            $company_name_pro = removeslashes(esc_attr(trim($_POST['company_name'])));
            $company_legal_form_pro = removeslashes(esc_attr(trim($_POST['company_legal_form'])));
            $company_identity_number_pro = removeslashes(esc_attr(trim($_POST['company_identity_number'])));
            $company_identity_tva_number_pro = removeslashes(esc_attr(trim($_POST['company_identity_tva_number'])));
            $number_street_pro = removeslashes(esc_attr(trim($_POST['number_street'])));
            $complement_address_pro = removeslashes(esc_attr(trim($_POST['complement_address'])));
            $locality_pro = removeslashes(esc_attr(trim($_POST['locality_pro'])));
            $country_region_city_pro = getCountryRegionCityInformations($locality_pro);
            $postal_code_pro = removeslashes(esc_attr(trim($_POST['postal_code'])));
            $home_phone_number_pro = removeslashes(esc_attr(trim($_POST['home_phone_number'])));
            $test_question_ID_pro = removeslashes(esc_attr(trim($_POST['test_question_pro'])));
            $answer_test_question_pro = removeslashes(esc_attr(trim($_POST['answer_test_question_pro'])));
            $receive_notifications_pro = removeslashes(esc_attr(trim($_POST['receive_notifications'])));

            $update_user_data = array(
                'ID' => $user_id,
                'user_login' => $user_login_pro,
                //'user_pass' => $user_pass_pro,
                'user_email' => $user_email_pro,
                'role' => $role,
                'first_name' => "",
                'last_name' => $company_name_pro
            );

            $user_id = wp_update_user($update_user_data);
            //update_user_meta($user_id, 'plain-text-password', $user_pass_pro);
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
                update_user_meta($user_id, 'company-legal-form', $company_legal_form_pro);
                update_user_meta($user_id, 'company-identity-number', $company_identity_number_pro);
                update_user_meta($user_id, 'company-identity-tva-number', $company_identity_tva_number_pro);
                update_user_meta($user_id, 'number-street', $number_street_pro);
                update_user_meta($user_id, 'complement-address', $complement_address_pro);
                update_user_meta($user_id, 'country', $country_region_city_pro['country']);
                update_user_meta($user_id, 'region-province-state', $country_region_city_pro['region']);
                update_user_meta($user_id, 'commune-city-locality', $country_region_city_pro['city']);
                update_user_meta($user_id, 'postal-code', $postal_code_pro);
                update_user_meta($user_id, 'home-phone-number', $home_phone_number_pro);
                update_user_meta($user_id, 'civility-representative1', $civility_represntative1_pro);
                update_user_meta($user_id, 'first-name-representative1', $first_name_representative1_pro);
                update_user_meta($user_id, 'last-name-representative1', $last_name_representative1_pro);
                update_user_meta($user_id, 'company-function-representative1', $function_representative1_pro);
                update_user_meta($user_id, 'mobile-phone-number-representative1', $mobile_phone_number_representative1_pro);
                update_user_meta($user_id, 'email-representative1', $email_representative1_pro);
                update_user_meta($user_id, 'civility-representative2', $civility_represntative2_pro);
                update_user_meta($user_id, 'first-name-representative2', $first_name_representative2_pro);
                update_user_meta($user_id, 'last-name-representative2', $last_name_representative2_pro);
                update_user_meta($user_id, 'company-function-representative2', $function_representative2_pro);
                update_user_meta($user_id, 'mobile-phone-number-representative1', $mobile_phone_number_representative2_pro);
                update_user_meta($user_id, 'email-representative2', $email_representative2_pro);
                update_user_meta($user_id, 'test-question-ID', $test_question_ID_pro);
                update_user_meta($user_id, 'answer-test-question', $answer_test_question_pro);
                if ($receive_notifications_pro && $receive_notifications_pro == 'on') {
                    update_user_meta($user_id, 'receive-notifications', 'yes');
                } else {
                    update_user_meta($user_id, 'receive-notifications', 'no');
                }
            }
        }

        if (!is_wp_error($user_id)) {
            // Set the global user object
            $current_user = get_user_by('id', $user_id);

            // set the WP login cookie
            $secure_cookie = is_ssl() ? true : false;
            wp_set_auth_cookie($user_id, true, $secure_cookie);
            wp_safe_redirect(get_permalink(get_page_by_path(__('mon-compte', 'gpdealdomain'))));
            exit;
        } else {
            wp_safe_redirect(get_permalink(get_page_by_path(__('inscription', 'gpdealdomain'))));
            exit;
        }
    }
}

//Function for getting forgot password of user
function get_password() {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        $user_email = removeslashes(esc_attr(trim($_POST['email'])));
        $test_question_ID = removeslashes(esc_attr(trim($_POST['test_question'])));
        $answer_test_question = removeslashes(esc_attr(trim($_POST['answer_test_question'])));
        $unique_user_email = get_user_by('email', $user_email);
        if ($unique_user_email != null) {
            $user_id = $unique_user_email->ID;
            $test_question_ID_user = get_user_meta($user_id, 'test-question-ID', true);
            $answer_test_question_user = get_user_meta($user_id, 'answer-test-question', true);
            if ($test_question_ID == $test_question_ID_user && $answer_test_question == $answer_test_question_user) {
                $json = array("message" => "Correct informations");
                return wp_send_json_success($json);
            } else {
                $json = array("message" => "Les informations saisies sont incorrectes (au moins une information est érronée, incomplète ou manquante). Veuillez recommencer votre saisie !!");
                return wp_send_json_error($json);
            }
        } else {
            $json = array("message" => "Utilisateur inexistant");
            return wp_send_json_error($json);
        }
    } else {
        $user_email = removeslashes(esc_attr(trim($_POST['email'])));
        $unique_user_email = get_user_by('email', $user_email);
        $plain_text_password = get_user_meta($user_id, 'plain-text-password', true);
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headers[] = 'From: GPDEAL INFOS <infos@gpdeal.com>';
        //$headers[] = 'Reply-To:' . Input::get('nom') . ' <' . $data['adress'] . '>';
        //$headers[] = 'Bcc:<apatchong@gmail.com>';
        $headers[] = 'Bcc:<erictonyelissouck@yahoo.fr>';

        $to = $user_email;

        $subject = "Mot de passe du compte";

        $body = $plain_text_password;
        wp_mail($to, $subject, $body, $headers);
    }
}

//Function of signing in gpdeal front-end website
function signin($username, $password, $remember = null, $redirect_to = null) {
    if ($remember && $remember == 'true') {
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
        $creds = array('user_login' => $user->data->user_login, 'user_password' => $password, 'remember' => $remember);
        $secure_cookie = is_ssl() ? true : false;
        $user = wp_signon($creds, $secure_cookie);
        if ($redirect_to) {
            wp_safe_redirect($redirect_to);
        } else {
            wp_safe_redirect(get_permalink(get_page_by_path(__('mon-compte', 'gpdealdomain'))));
        }
        exit;
    } else {
        $_SESSION['signin_error'] = __("Nom d'utilisateur ou mot de passe incorrect");
        wp_safe_redirect(get_permalink(get_page_by_path(__('connexion', 'gpdealdomain'))));
        exit;
    }
}

//Return a name of user custom role defined by gpdeal
function getUserRoleName($role) {
    switch ($role) {
        case 'particular':
            return __('Particulier', "gpdealdomain");
        case 'professional':
            return __("Professionnel", "gpdealdomain");
        case 'enterprise':
            return __('Entreprise', "gpdealdomain");
        default :
            return $role;
    }
}

//Return a gender of hold name
function getGenderHoldName($gender) {
    switch ($gender) {
        case 'M':
            return 'Masculin';
            break;
        case 'F':
            return "Feminin";
            break;
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
    $currencies = array(['code' => 'EU', 'name' => 'EURO'], ['code' => 'USD', 'name' => 'Dollard Americain'], ['code' => 'FCFA', 'name' => 'Franc CFA']
    );
    return $currencies;
}

//Fonction for sending a package
function sendPackage($package_data) {
    if ($package_data) {
        $type = $package_data['package_type'];
        $content = $package_data['portable_objects'];
        $length = $package_data['package_dimensions_length'];
        $width = $package_data['package_dimensions_width'];
        $height = $package_data['package_dimensions_height'];
        $weight = $package_data['package_weight'];
        $start_city = $package_data['start_city'];
        $start_date = $package_data['start_date'];
        $destination_city = $package_data['destination_city'];
        $destination_date = $package_data['destination_date'];

        $start_country = "";
        $start_state = "";
        //array containing city name, region name, and country name of start
        $start_localities = explode(", ", $start_city);
        if (count($start_localities) == 2) {
            $start_city = $start_localities[0];
            $start_country = $start_localities[1];
            $start_state = getRegionByCityAndCountry($start_city, $start_country);
        } elseif (count($start_localities) == 3) {
            $start_city = $start_localities[0];
            $start_state = $start_localities[1];
            $start_country = $start_localities[2];
        }


        $destination_country = "";
        $destination_state = "";
        //array containing city name, region name, and country name of destination
        $destination_localities = explode(", ", $destination_city);
        if (count($destination_localities) == 2) {
            $destination_city = $destination_localities[0];
            $destination_country = $destination_localities[1];
            $destination_state = getRegionByCityAndCountry($destination_city, $destination_country);
        } elseif (count($destination_localities) == 3) {
            $destination_city = $destination_localities[0];
            $destination_state = $destination_localities[1];
            $destination_country = $destination_localities[2];
        }

        $date = new DateTime('now');
        $post_title = "P-" . $date->format('Y-m-d H:i:s') . '-' . $date->getTimestamp();

        $post_args = array(
            'post_title' => wp_strip_all_tags($post_title),
            'post_type' => 'package',
            'post_author' => get_current_user_id(),
            //'post_content' => $post_title,
            'post_status' => 'publish',
            'tax_input' => array('type_package' => array(intval($type)), 'portable-object' => $content),
            'meta_input' => array(
                'length' => floatval($length),
                'width' => floatval($width),
                'height' => floatval($height),
                'weight' => floatval($weight),
                'package-number' => $post_title,
                'departure-country-package' => $start_country,
                'departure-state-package' => $start_state,
                'departure-city-package' => $start_city,
                'date-of-departure-package' => $start_date,
                'destination-country-package' => $destination_country,
                'destination-state-package' => $destination_state,
                'destination-city-package' => $destination_city,
                'arrival-date-package' => $destination_date,
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
        $content = $package_data['portable_objects'];
        $length = $package_data['package_dimensions_length'];
        $width = $package_data['package_dimensions_width'];
        $height = $package_data['package_dimensions_height'];
        $weight = $package_data['package_weight'];
        $start_city = $package_data['start_city'];
        $start_date = $package_data['start_date'];
        $destination_city = $package_data['destination_city'];
        $destination_date = $package_data['destination_date'];

        //$date = new DateTime('now');
        //$post_title = "P-".$date->format('Y-m-d H:i:s').'-'.$date->getTimestamp();
        $start_country = "";
        $start_state = "";
        //array containing city name, region name, and country name of start
        $start_localities = explode(", ", $start_city);
        if (count($start_localities) == 2) {
            $start_city = $start_localities[0];
            $start_country = $start_localities[1];
            $start_state = getRegionByCityAndCountry($start_city, $start_country);
        } elseif (count($start_localities) == 3) {
            $start_city = $start_localities[0];
            $start_state = $start_localities[1];
            $start_country = $start_localities[2];
        }


        $destination_country = "";
        $destination_state = "";
        //array containing city name, region name, and country name of destination
        $destination_localities = explode(", ", $destination_city);
        if (count($destination_localities) == 2) {
            $destination_city = $destination_localities[0];
            $destination_country = $destination_localities[1];
            $destination_state = getRegionByCityAndCountry($destination_city, $destination_country);
        } elseif (count($destination_localities) == 3) {
            $destination_city = $destination_localities[0];
            $destination_state = $destination_localities[1];
            $destination_country = $destination_localities[2];
        }
        $post_args = array(
            'ID' => $post_ID,
            //'post_title' => wp_strip_all_tags($post_title),
            //'post_name' => sanitize_title_with_dashes($post_title,'','save'),
            //'post_type' => 'package',
            //'post_author' => get_current_user_id(),
            //'post_content' => $post_title,
            //'post_status' => 'publish',
            'tax_input' => array('type_package' => array(intval($type)), 'portable-object' => $content),
            'meta_input' => array(
                'length' => floatval($length),
                'width' => floatval($width),
                'height' => floatval($height),
                'weight' => floatval($weight),
                //'package-number' => $post_title,
                'departure-country-package' => $start_country,
                'departure-state-package' => $start_state,
                'departure-city-package' => $start_city,
                'date-of-departure-package' => $start_date,
                'destination-country-package' => $destination_country,
                'destination-state-package' => $destination_state,
                'destination-city-package' => $destination_city,
                'arrival-date-package' => $destination_date,
            )
        );
        $package_id = wp_update_post($post_args, true);
        return $package_id;
    }
}

//Fonction for Saving a Transport offer
function saveTransportOffer($transport_offer_data) {
    if ($transport_offer_data) {
        $package_type = $transport_offer_data['transport_offer_package_type'];
        $transport_method = $transport_offer_data['transport_offer_transport_method'];
        $transport_offer_price = $transport_offer_data['transport_offer_price'];
        $transport_offer_currency = $transport_offer_data['transport_offer_currency'];
        $start_city = $transport_offer_data['start_city'];
        $start_date = $transport_offer_data['start_date'];
        $start_deadline = $transport_offer_data['start_deadline'];
        $destination_city = $transport_offer_data['destination_city'];
        $destination_date = $transport_offer_data['destination_date'];

        $date = new DateTime('now');
        $post_title = "TRFR-" . $date->format('Y-m-d H:i:s') . '-' . $date->getTimestamp();

        $start_country = "";
        $start_state = "";
        //array containing city name, region name, and country name of start
        $start_localities = explode(", ", $start_city);
        if (count($start_localities) == 2) {
            $start_city = $start_localities[0];
            $start_country = $start_localities[1];
            $start_state = getRegionByCityAndCountry($start_city, $start_country);
        } elseif (count($start_localities) == 3) {
            $start_city = $start_localities[0];
            $start_state = $start_localities[1];
            $start_country = $start_localities[2];
        }


        $destination_country = "";
        $destination_state = "";
        //array containing city name, region name, and country name of destination
        $destination_localities = explode(", ", $destination_city);
        if (count($destination_localities) == 2) {
            $destination_city = $destination_localities[0];
            $destination_country = $destination_localities[1];
            $destination_state = getRegionByCityAndCountry($destination_city, $destination_country);
        } elseif (count($destination_localities) == 3) {
            $destination_city = $destination_localities[0];
            $destination_state = $destination_localities[1];
            $destination_country = $destination_localities[2];
        }

        $post_args = array(
            'post_title' => wp_strip_all_tags($post_title),
            'post_type' => 'transport-offer',
            'post_author' => get_current_user_id(),
            //'post_content' => $post_title,
            'post_status' => 'publish',
            'tax_input' => array('type_package' => $package_type, 'transport-method' => array(intval($transport_method))),
            'meta_input' => array(
                'transport-offer-number' => $post_title,
                'price' => floatval($transport_offer_price),
                'currency' => $transport_offer_currency,
                'departure-country-transport-offer' => $start_country,
                'departure-state-transport-offer' => $start_state,
                'departure-city-transport-offer' => $start_city,
                'date-of-departure-transport-offer' => $start_date,
                'deadline-of-proposition-transport-offer' => $start_deadline,
                'destination-country-transport-offer' => $destination_country,
                'destination-state-transport-offer' => $destination_state,
                'destination-city-transport-offer' => $destination_city,
                'arrival-date-transport-offer' => $destination_date,
                'transport-status' => 1,
                'packages-IDs' => -1
            )
        );
        $transport_offer_id = wp_insert_post($post_args, true);
        return $transport_offer_id;
    }
}

//Fonction for Updating informations of Transport offer a package
function updateTransportOffer($post_ID, $transport_offer_data) {
    if ($transport_offer_data) {
        $package_type = $transport_offer_data['transport_offer_package_type'];
        $transport_method = $transport_offer_data['transport_offer_transport_method'];
        $transport_offer_price = $transport_offer_data['transport_offer_price'];
        $transport_offer_currency = $transport_offer_data['transport_offer_currency'];
        $start_city = $transport_offer_data['start_city'];
        $start_date = $transport_offer_data['start_date'];
        $start_deadline = $transport_offer_data['start_deadline'];
        $destination_city = $transport_offer_data['destination_city'];
        $destination_date = $transport_offer_data['destination_date'];

        //$date = new DateTime('now');
        //$post_title = "TRFR".$date->format('Y-m-d H:i:s').$date->getTimestamp();

        $start_country = "";
        $start_state = "";
        //array containing city name, region name, and country name of start
        $start_localities = explode(", ", $start_city);
        if (count($start_localities) == 2) {
            $start_city = $start_localities[0];
            $start_country = $start_localities[1];
            $start_state = getRegionByCityAndCountry($start_city, $start_country);
        } elseif (count($start_localities) == 3) {
            $start_city = $start_localities[0];
            $start_state = $start_localities[1];
            $start_country = $start_localities[2];
        }


        $destination_country = "";
        $destination_state = "";
        //array containing city name, region name, and country name of destination
        $destination_localities = explode(", ", $destination_city);
        if (count($destination_localities) == 2) {
            $destination_city = $destination_localities[0];
            $destination_country = $destination_localities[1];
            $destination_state = getRegionByCityAndCountry($destination_city, $destination_country);
        } elseif (count($destination_localities) == 3) {
            $destination_city = $destination_localities[0];
            $destination_state = $destination_localities[1];
            $destination_country = $destination_localities[2];
        }

        $post_args = array(
            'ID' => $post_ID,
            //'post_title' => wp_strip_all_tags($post_title),
            //'post_type' => 'transport-offer',
            //'post_author' => get_current_user_id(),
            //'post_content' => $post_title,
            //'post_status' => 'publish',
            'tax_input' => array('type_package' => $package_type, 'transport-method' => array(intval($transport_method))),
            'meta_input' => array(
                //'transport-offer-number'=>$post_title,
                'price' => floatval($transport_offer_price),
                'currency' => $transport_offer_currency,
                'departure-country-transport-offer' => $start_country,
                'departure-state-transport-offer' => $start_state,
                'departure-city-transport-offer' => $start_city,
                'date-of-departure-transport-offer' => $start_date,
                'deadline-of-proposition-transport-offer' => $start_deadline,
                'destination-country-transport-offer' => $destination_country,
                'destination-state-transport-offer' => $destination_state,
                'destination-city-transport-offer' => $destination_city,
                'arrival-date-transport-offer' => $destination_date,
            //'transport-status' => 1
            )
        );
        $transport_offer_id = wp_update_post($post_args, true);
        return $transport_offer_id;
    }
}

//Function for leaving a message in contact form on the website
function contactus() {
    if (is_user_logged_in()) {
        $current_user = wp_get_current_user();
        $social_reason = removeslashes(esc_attr(trim($_POST['social_reasons'])));
        $subject = removeslashes(esc_attr(trim($_POST['subject'])));
        $message = removeslashes(esc_attr(trim($_POST['message'])));
        $email = $current_user->user_email;
        if (is_user_in_role($current_user->ID, 'particular')) {
            $phone_number = get_user_meta($current_user->ID, 'phone-number', true);
            $role = getUserRoleName('particular');
            $sender_name = $current_user->user_firstname . " " . $current_user->user_lastname;
        } else {
            $phone_number = get_user_meta($current_user->ID, 'company-phone-number', true);
            $sender_name = get_user_meta($current_user->ID, 'company-name', true);
            if (is_user_in_role($current_user->ID, 'professional')) {
                $role = getUserRoleName('professional');
            } elseif (is_user_in_role($current_user->ID, 'enterprise')) {
                $role = getUserRoleName('enterprise');
            }
        }
    } elseif (isset($_POST['member']) && $_POST['member'] = 'yes') {
        $email = removeslashes(esc_attr(trim($_POST['email'])));
        $social_reason = removeslashes(esc_attr(trim($_POST['social_reasons'])));
        $message = removeslashes(esc_attr(trim($_POST['message'])));
        $user = get_user_by('email', $email);
        if ($user == null || is_wp_error($user)) {
            $json = array("message" => __("Utilisateur inexistant", 'gpdealdomain') . ".");
            return wp_send_json_error($json);
        }
        if (is_user_in_role($user->ID, 'particular')) {
            $phone_number = get_user_meta($user->ID, 'phone-number', true);
            $role = getUserRoleName('particular');
            $sender_name = $user->user_firstname . " " . $user->user_lastname;
        } else {
            $phone_number = get_user_meta($user->ID, 'company-phone-number', true);
            $sender_name = get_user_meta($user->ID, 'company-name', true);
            if (is_user_in_role($user->ID, 'professional')) {
                $role = getUserRoleName('professional');
            } elseif (is_user_in_role($user->ID, 'enterprise')) {
                $role = getUserRoleName('enterprise');
            }
        }
    } else {
        $email = removeslashes(esc_attr(trim($_POST['email'])));
        $firstname = removeslashes(esc_attr(trim($_POST['firstname'])));
        $lastname = removeslashes(esc_attr(trim($_POST['lastname'])));
        $social_reason = removeslashes(esc_attr(trim($_POST['social_reasons'])));
        $subject = removeslashes(esc_attr(trim($_POST['subject'])));
        $message = removeslashes(esc_attr(trim($_POST['message'])));
        $country_code = removeslashes(esc_attr(trim($_POST['country_code'])));
        $phone_number = $country_code . removeslashes(esc_attr(trim($_POST['phone_number'])));
        $function = removeslashes(esc_attr(trim($_POST['function'])));
        $civility = removeslashes(esc_attr(trim($_POST['civility'])));
        $role = removeslashes(esc_attr(trim($_POST['role'])));
        $company_identity_number = removeslashes(esc_attr(trim($_POST['company_identity_number'])));
        $sender_name = $firstname . " " . $lastname;
    }
    $headers[] = 'Content-Type: text/html; charset=UTF-8';
    $headers[] = 'From: ' . $sender_name . ' <' . $email . '>';
    //$headers[] = 'Reply-To:' . Input::get('nom') . ' <' . $data['adress'] . '>';
    $headers[] = 'Bcc:<erictonyelissouck@yahoo.fr>';

    $to = get_bloginfo('admin_email');

    $subject = $subject;

    $body = $message;
    if (wp_mail($to, $subject, $body, $headers)) {
        $json = array("message" => __("Votre message a été envoyé avec succès", 'si-ogivedomain'));
        return wp_send_json_success($json);
    } else {
        $json = array("message" => __("Erreur lors de l'envoi. Verifier les informations puis réessayer à nouveau", 'si-ogivedomain'));
        return wp_send_json_error($json);
    }
}

function getPackageStatus($status) {
    switch ($status) {
        case -1:
            return "Recherche transporteur";
        case 1:
            return "Recherche transporteur";
        case 2:
            return "Transaction en cours";
        case 3:
            return "Transaction validée";
        case 4:
            return "Evaluée/cloturée";
        case 5:
            return "Expirée";
        case 6:
            return "Annulée";
        default :
            return "Recherche transporteur";
    }
}

function getTransportStatus($status) {
    switch ($status) {
        case -1:
            return "En cours";
        case 1:
            return "En cours";
        case 2:
            return "Expirée";
        case 3:
            return "Annulée";
//        case 4:
//            return "Evaluée/cloturée";
//        case 5:
//            return "Expirée";
//        case 6:
//            return "Annulée";
        default :
            return "En cours";
    }
}

// This Function return arguments of a query for finding transport offers
function getWPQueryArgsForCarrierSearch($search_data) {
    $today = date('Y-m-d H:i:s', strtotime('today'));
    $args = array(
        'post_type' => 'transport-offer',
        "post_status" => 'publish'
    );
    if ($search_data) {
        $package_type = $search_data['package_type'];
        $start_city = $search_data['start_city'];
        $start_date = $search_data['start_date'];
        $destination_city = $search_data['destination_city'];
        $destination_date = $search_data['destination_date'];

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
//            array(
//                'key' => 'transport-status',
//                'value' => 3,
//                'compare' => '!=',
//            )
        );


        if ($start_city && $start_city != "") {
            $start_country = "";
            $start_state = "";
            //array containing city name, region name, and country name of start
            $start_localities = explode(", ", $start_city);
            if (count($start_localities) == 2) {
                $start_city = $start_localities[0];
                $start_country = $start_localities[1];
                //$start_state = getRegionByCityAndCountry($start_state, $start_country);
            } elseif (count($start_localities) == 3) {
                $start_city = $start_localities[0];
                $start_state = $start_localities[1];
                $start_country = $start_localities[2];
            }
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

        if ($destination_city && $destination_city != "") {
            $destination_country = "";
            $destination_state = "";
            //array containing city name, region name, and country name of destination
            $destination_localities = explode(", ", $destination_city);
            if (count($destination_localities) == 2) {
                $destination_city = $destination_localities[0];
                $destination_country = $destination_localities[1];
                //$destination_state = getRegionByCityAndCountry($destination_city, $destination_country);
            } elseif (count($destination_localities) == 3) {
                $destination_city = $destination_localities[0];
                $destination_state = $destination_localities[1];
                $destination_country = $destination_localities[2];
            }
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
        "post_status" => 'publish'
    );
    if ($search_data) {
        $package_type = $search_data['package_type'];
        $start_city = $search_data['start_city'];
        $start_date = $search_data['start_date'];
        $destination_city = $search_data['destination_city'];
        $destination_date = $search_data['destination_date'];

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


        if ($start_city && $start_city != "") {
            $start_country = "";
            $start_state = "";
            //array containing city name, region name, and country name of start
            $start_localities = explode(", ", $start_city);
            if (count($start_localities) == 2) {
                $start_city = $start_localities[0];
                $start_country = $start_localities[1];
                //$start_state = getRegionByCityAndCountry($start_state, $start_country);
            } elseif (count($start_localities) == 3) {
                $start_city = $start_localities[0];
                //$start_state = $start_localities[1];
                $start_country = $start_localities[2];
            }
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


        if ($destination_city && $destination_city != "") {
            $destination_country = "";
            $destination_state = "";
            //array containing city name, region name, and country name of destination
            $destination_localities = explode(", ", $destination_city);
            if (count($destination_localities) == 2) {
                $destination_city = $destination_localities[0];
                $destination_country = $destination_localities[1];
                //$destination_state = getRegionByCityAndCountry($destination_city, $destination_country);
            } elseif (count($destination_localities) == 3) {
                $destination_city = $destination_localities[0];
                //$destination_state = $destination_localities[1];
                $destination_country = $destination_localities[2];
            }
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
        $start_city = $search_data['start_city'];
        $start_date = $search_data['start_date'];
        $destination_city = $search_data['destination_city'];
        $destination_date = $search_data['destination_date'];
        if ($start_city || $destination_city) {
            $args = array(
                "post_type" => "transport-offer",
                "post_status" => 'publish',
                "post__not_in" => $exclude_ids
            );
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


            if ($start_city && $start_city != "") {
                $start_country = "";
                $start_state = "";
                //array containing city name, region name, and country name of start
                $start_localities = explode(", ", $start_city);
                if (count($start_localities) == 2) {
                    $start_city = $start_localities[0];
                    $start_country = $start_localities[1];
                    $start_state = getRegionByCityAndCountry($start_city, $start_country);
                } elseif (count($start_localities) == 3) {
                    $start_city = $start_localities[0];
                    $start_state = $start_localities[1];
                    $start_country = $start_localities[2];
                }

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

            if ($destination_city && $destination_city != "") {
                $destination_country = "";
                $destination_state = "";
                //array containing city name, region name, and country name of destination
                $destination_localities = explode(", ", $destination_city);
                if (count($destination_localities) == 2) {
                    $destination_city = $destination_localities[0];
                    $destination_country = $destination_localities[1];
                    $destination_state = getRegionByCityAndCountry($destination_city, $destination_country);
                } elseif (count($destination_localities) == 3) {
                    $destination_city = $destination_localities[0];
                    $destination_state = $destination_localities[1];
                    $destination_country = $destination_localities[2];
                }
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
        "post__not_in" => $exclude_ids
    );
    if ($search_data) {
        $package_type = $search_data['package_type'];
        $start_city = $search_data['start_city'];
        $start_date = $search_data['start_date'];
        $destination_city = $search_data['destination_city'];
        $destination_date = $search_data['destination_date'];

        if ($start_city || $destination_city) {
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
                $start_country = "";
                $start_state = "";
                //array containing city name, region name, and country name of start
                $start_localities = explode(", ", $start_city);
                if (count($start_localities) == 2) {
                    $start_city = $start_localities[0];
                    $start_country = $start_localities[1];
                    $start_state = getRegionByCityAndCountry($start_city, $start_country);
                } elseif (count($start_localities) == 3) {
                    $start_city = $start_localities[0];
                    $start_state = $start_localities[1];
                    $start_country = $start_localities[2];
                }

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
                $destination_country = "";
                $destination_state = "";
                //array containing city name, region name, and country name of destination
                $destination_localities = explode(", ", $destination_city);
                if (count($destination_localities) == 2) {
                    $destination_city = $destination_localities[0];
                    $destination_country = $destination_localities[1];
                    $destination_state = getRegionByCityAndCountry($destination_city, $destination_country);
                } elseif (count($destination_localities) == 3) {
                    $destination_city = $destination_localities[0];
                    $destination_state = $destination_localities[1];
                    $destination_country = $destination_localities[2];
                }
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
function getWPQueryArgsForMainCarrierSearchWithStartParameters() {
    $today = date('Y-m-d H:i:s', strtotime('today'));
    $args = array(
        'post_type' => 'transport-offer',
        "post_status" => 'publish'
    );
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['s'])) {

        $search_query = removeslashes(esc_attr(trim($_GET['s'])));
        if ($search_query) {
            $start_country = "";
            $start_state = "";
            $start_city = $search_query;
            //array containing city name, region name, and country name of start
            $start_localities = explode(", ", $start_city);
            if (count($start_localities) == 2) {
                $start_city = $start_localities[0];
                $start_country = $start_localities[1];
                $start_state = getRegionByCityAndCountry($start_city, $start_country);
            } elseif (count($start_localities) == 3) {
                $start_city = $start_localities[0];
                $start_state = $start_localities[1];
                $start_country = $start_localities[2];
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
    }
    return $args;
}

// This Function return arguments of a query for main searching transport offers with destination parameters
function getWPQueryArgsForMainCarrierSearchWithDestinationParameters() {
    $today = date('Y-m-d H:i:s', strtotime('today'));
    $args = array(
        'post_type' => 'transport-offer',
        "post_status" => 'publish'
    );
    if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['s'])) {

        $search_query = removeslashes(esc_attr(trim($_GET['s'])));
        if ($search_query) {
            $destination_country = "";
            $destination_state = "";
            $destination_city = $search_query;
            //array containing city name, region name, and country name of destination
            $destination_localities = explode(", ", $destination_city);
            if (count($destination_localities) == 2) {
                $destination_city = $destination_localities[0];
                $destination_country = $destination_localities[1];
                $destination_state = getRegionByCityAndCountry($destination_city, $destination_country);
            } elseif (count($destination_localities) == 3) {
                $destination_city = $destination_localities[0];
                $destination_state = $destination_localities[1];
                $destination_country = $destination_localities[2];
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
        'post_per_page' => 1
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
        'post_per_page' => 1,
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
        'post_per_page' => 1,
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
