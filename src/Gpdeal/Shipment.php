<?php

namespace src\Gpdeal;

/**
 * Description of Shipment
 *
 * @author Eric TONYE
 */
class Shipment {

    private $id;
    private $start_city;
    private $start_state;
    private $start_country;
    private $destination_city;
    private $destination_country;
    private $start_date;
    private $destination_date;
    private $package_type;
    private $package_content;
    private $length;
    private $width;
    private $height;
    private $weight;
    private $package_picture_id;

    public function getId() {
        return $this->id;
    }

    public function getStart_city() {
        return $this->start_city;
    }

    public function getStart_state() {
        return $this->start_state;
    }

    public function getStart_country() {
        return $this->start_country;
    }

    public function getDestination_city() {
        return $this->destination_city;
    }

    public function getDestination_country() {
        return $this->destination_country;
    }

    public function getStart_date() {
        return $this->start_date;
    }

    public function getDestination_date() {
        return $this->destination_date;
    }

    public function getPackage_type() {
        return $this->package_type;
    }

    public function getPackage_content() {
        return $this->package_content;
    }

    public function getLength() {
        return $this->length;
    }

    public function getWidth() {
        return $this->width;
    }

    public function getHeight() {
        return $this->height;
    }

    public function getWeight() {
        return $this->weight;
    }

    public function getPackage_picture_id() {
        return $this->package_picture_id;
    }

    public function setId($id) {
        $this->id = $id;
    }

    public function setStart_city($start_city) {
        $this->start_city = $start_city;
    }

    public function setStart_state($start_state) {
        $this->start_state = $start_state;
    }

    public function setStart_country($start_country) {
        $this->start_country = $start_country;
    }

    public function setDestination_city($destination_city) {
        $this->destination_city = $destination_city;
    }

    public function setDestination_country($destination_country) {
        $this->destination_country = $destination_country;
    }

    public function setStart_date($start_date) {
        $this->start_date = $start_date;
    }

    public function setDestination_date($destination_date) {
        $this->destination_date = $destination_date;
    }

    public function setPackage_type($package_type) {
        $this->package_type = $package_type;
    }

    public function setPackage_content($package_content) {
        $this->package_content = $package_content;
    }

    public function setLength($length) {
        $this->length = $length;
    }

    public function setWidth($width) {
        $this->width = $width;
    }

    public function setHeight($height) {
        $this->height = $height;
    }

    public function setWeight($weight) {
        $this->weight = $weight;
    }

    public function setPackage_picture_id($package_picture_id) {
        $this->package_picture_id = $package_picture_id;
    }

    public static function initShipmentCPT() {
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

    public function saveShipment() {
        $date = new DateTime('now');
        $post_title = str_replace(":", "", str_replace("-", "", str_replace(" ", "", "P" . $date->format('Y-m-d H:i:s') . $date->getTimestamp())));
        $post_args = array(
            'post_title' => wp_strip_all_tags($post_title),
            'post_type' => 'package',
            'post_author' => get_current_user_id(),
            'post_status' => 'publish',
            'tax_input' => array('type_package' => array(intval($this->package_type))),
            'meta_input' => array(
                'length' => floatval($this->length),
                'width' => floatval($this->width),
                'height' => floatval($this->height),
                'weight' => floatval($this->weight),
                'package-number' => $post_title,
                'package-content' => $this->package_content,
                'departure-country-package' => $this->start_country,
                'departure-state-package' => $this->start_state,
                'departure-city-package' => $this->start_city,
                'date-of-departure-package' => $this->start_date,
                'destination-country-package' => $this->destination_country,
                'destination-state-package' => $this->destination_state,
                'destination-city-package' => $this->destination_city,
                'arrival-date-package' => $this->destination_date,
                'package-picture-ID' => $this->package_picture_id,
                'carrier-ID' => -1,
                'package-status' => 1
            )
        );
        $package_id = wp_insert_post($post_args, true);
        return $package_id;
    }

    public function updateShipment() {
        $post_args = array(
            'ID' => $this->id,
            'tax_input' => array('type_package' => array(intval($this->package_type))),
            'meta_input' => array(
                'package-content' => $this->package_content,
                'length' => floatval($this->length),
                'width' => floatval($this->width),
                'height' => floatval($this->height),
                'weight' => floatval($this->weight),
                'departure-country-package' => $this->start_country,
                'departure-state-package' => $this->start_state,
                'departure-city-package' => $this->start_city,
                'date-of-departure-package' => $this->start_date,
                'destination-country-package' => $this->destination_country,
                'destination-state-package' => $this->destination_state,
                'destination-city-package' => $this->destination_city,
                'arrival-date-package' => $this->destination_date,
                'package-picture-ID' => $this->package_picture_id,
            )
        );
        $package_id = wp_update_post($post_args, true);
        return $package_id;
    }

}
