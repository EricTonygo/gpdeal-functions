<?php

namespace src\Gpdeal;

/**
 * Description of TransportOffer
 *
 * @author Eric TONYE
 */
class TransportOffer {

    private $id;
    private $start_city;
    private $start_state;
    private $start_country;
    private $destination_city;
    private $destination_country;
    private $start_date;
    private $destination_date;
    private $deadline;
    private $package_types;
    private $max_length;
    private $max_width;
    private $max_height;
    private $max_weight;
    private $transport_offer_price;
    private $transport_offer_currency;
    private $transport_offer_price_type;
    private $transport_method;

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

    public function getDeadline() {
        return $this->deadline;
    }

    public function getPackage_types() {
        return $this->package_types;
    }

    public function getMax_length() {
        return $this->max_length;
    }

    public function getMax_width() {
        return $this->max_width;
    }

    public function getMax_height() {
        return $this->max_height;
    }

    public function getMax_weight() {
        return $this->max_weight;
    }

    public function getTransport_offer_price() {
        return $this->transport_offer_price;
    }

    public function getTransport_offer_currency() {
        return $this->transport_offer_currency;
    }

    public function getTransport_offer_price_type() {
        return $this->transport_offer_price_type;
    }

    public function getTransport_method() {
        return $this->transport_method;
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

    public function setDeadline($deadline) {
        $this->deadline = $deadline;
    }

    public function setPackage_types($package_types) {
        $this->package_types = $package_types;
    }

    public function setMax_length($max_length) {
        $this->max_length = $max_length;
    }

    public function setMax_width($max_width) {
        $this->max_width = $max_width;
    }

    public function setMax_height($max_height) {
        $this->max_height = $max_height;
    }

    public function setMax_weight($max_weight) {
        $this->max_weight = $max_weight;
    }

    public function setTransport_offer_price($transport_offer_price) {
        $this->transport_offer_price = $transport_offer_price;
    }

    public function setTransport_offer_currency($transport_offer_currency) {
        $this->transport_offer_currency = $transport_offer_currency;
    }

    public function setTransport_offer_price_type($transport_offer_price_type) {
        $this->transport_offer_price_type = $transport_offer_price_type;
    }

    public function setTransport_method($transport_method) {
        $this->transport_method = $transport_method;
    }

    public static function initTransportOfferCPT() {
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

    public function saveTransportOffer() {
        $date = new DateTime('now');
        $post_title = str_replace(":", "", str_replace("-", "", str_replace(" ", "", "TRFR" . $date->format('Y-m-d H:i:s') . $date->getTimestamp())));

        $post_args = array(
            'post_title' => wp_strip_all_tags($post_title),
            'post_type' => 'transport-offer',
            'post_author' => get_current_user_id(),
            'post_status' => 'publish',
            'tax_input' => array('type_package' => $this->package_types, 'transport-method' => array(intval($this->transport_method))),
            'meta_input' => array(
                'transport-offer-number' => $post_title,
                'price' => floatval($this->transport_offer_price),
                'currency' => $this->transport_offer_currency,
                'price-type' => intval($this->transport_offer_price_type),
                'package-length-max' => floatval($this->max_length),
                'package-width-max' => floatval($this->max_width),
                'package-height-max' => floatval($this->max_height),
                'package-weight-max' => floatval($this->max_weight),
                'departure-country-transport-offer' => $this->start_country,
                'departure-state-transport-offer' => $this->start_state,
                'departure-city-transport-offer' => $this->start_city,
                'date-of-departure-transport-offer' => $this->start_date,
                'deadline-of-proposition-transport-offer' => $this->deadline,
                'destination-country-transport-offer' => $this->destination_country,
                'destination-state-transport-offer' => $this->destination_state,
                'destination-city-transport-offer' => $this->destination_city,
                'arrival-date-transport-offer' => $this->destination_date,
                'transport-status' => 1,
                'packages-IDs' => -1
            )
        );
        $transport_offer_id = wp_insert_post($post_args, true);
        return $transport_offer_id;
    }

    public function updateTransportOffer() {
        $post_args = array(
            'ID' => $this->id,
            'tax_input' => array('type_package' => $this->package_types, 'transport-method' => array(intval($this->transport_method))),
            'meta_input' => array(
                'price' => floatval($this->transport_offer_price),
                'currency' => $this->transport_offer_currency,
                'price-type' => intval($this->transport_offer_price_type),
                'package-length-max' => floatval($this->max_length),
                'package-width-max' => floatval($this->max_width),
                'package-height-max' => floatval($this->max_height),
                'package-weight-max' => floatval($this->max_weight),
                'departure-country-transport-offer' => $this->start_country,
                'departure-state-transport-offer' => $this->start_state,
                'departure-city-transport-offer' => $this->start_city,
                'date-of-departure-transport-offer' => $this->start_date,
                'deadline-of-proposition-transport-offer' => $this->deadline,
                'destination-country-transport-offer' => $this->destination_country,
                'destination-state-transport-offer' => $this->destination_state,
                'destination-city-transport-offer' => $this->destination_city,
                'arrival-date-transport-offer' => $this->destination_date
            )
        );
        $transport_offer_id = wp_update_post($post_args, true);
        $today = new \DateTime('today');
        $start_deadline_datetime = new \DateTime($this->deadline);
        if ($today <= $start_deadline_datetime) {
            update_post_meta($transport_offer_id, 'transport-status', 1);
        }
        return $transport_offer_id;
    }

}

