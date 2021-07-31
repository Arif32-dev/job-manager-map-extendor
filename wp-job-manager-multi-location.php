<?php
/**
 * Plugin Name: Listify Multi Location for WP Job Manager
 * Plugin URI:  https://plugins.keendevs.com/listify-wp-job-manager-multi-location
 * Description: Enable adding multiple locations for a single listing for admin. This plugin also shows in the multiple locations on the frontend search and single listing page location map.
 * Author:      Azizul Haque
 * Author URI:  https://keendevs.com
 * Version:     1.0
 * Text Domain: multi-location
 * Domain Path: /languages
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class Keendevs_Multi_Location_WP_JOB_M {

    /**
     * @var $instance
     */
    private static $instance;

    /**
     * Make sure only one instance is only running.
     */
    public static function instance() {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Start things up.
     *
     * @since 1.0
     */
    public function __construct() {
        $this->version = '1.0';
        $this->file = __FILE__;
        $this->basename = plugin_basename($this->file);
        $this->plugin_dir = plugin_dir_path($this->file);
        $this->plugin_url = set_url_scheme(plugin_dir_url($this->file), is_ssl() ? 'https' : 'http');
        $this->lang_dir = trailingslashit($this->plugin_dir.'languages');
        $this->domain = 'multi-location';
        $this->setup_actions();
    }

    /**
     * Setup the default hooks and actions
     *
     * @since 1.0
     *
     * @return void
     */
    private function setup_actions() {

        /* Register Scripts */
        add_action('wp_enqueue_scripts', array($this, 'register_scripts'), 99);
        add_action('admin_enqueue_scripts', array($this, 'register_scripts'), 99);
        /* frontend job edit, submit page */
        // add_action('submit_job_form_end', array($this, 'front_end_job_edit_submit'));
        /* frontend preview listing */
        // add_action('preview_job_form_end', array($this, 'preview_page_marker_listings'));

        /* load text domain */
        // add_action('plugins_loaded', array($this, 'load_textdomain'));

        /* Save Geo for New Post */
        add_action('job_manager_save_job_listing', array($this, 'save_post_location'), 31, 2);
        add_action('resume_manager_save_resume', array($this, 'save_post_location'), 31, 2);
        add_action('wpjm_events_save_event', array($this, 'save_post_location'), 30, 2);

        /* Save Geo on Update Post */
        add_action('job_manager_update_job_data', array($this, 'save_post_location'), 26, 2);
        add_action('resume_manager_update_resume_data', array($this, 'save_post_location'), 25, 2);
        add_action('wpjm_events_update_event_data', array($this, 'save_post_location'), 26, 2);

        // multi-location widgets
        // add_action('widgets_init', array($this, 'widgets_init'), 20);

        // explore page facets locations ids
        // add_filter('facetwp_filtered_post_ids', array($this, 'localize_explore_page_results_ids'), 10, 2);
    }

    public function localize_scripts_data() {
        // localize script data
        global $post;
        $listing_id = $post->ID;

        $defaultLatLng = array(
            'lat' => esc_attr(get_option('wpjmel_start_geo_lat', 40.712784)),
            'lng' => esc_attr(get_option('wpjmel_start_geo_long', -74.005941))
        );
        if ($listing_id) {
            $locations = get_post_meta($listing_id, '_additionallocations', true);
            if (is_array($locations)) {
                $extraMarkers = array_filter($locations, array($this, 'secure_location_data'));
            }
        }
        $this->local = array(
            'defaultLatLng'       => $defaultLatLng,
            'additionallocations' => $extraMarkers
        );
    }

    /**
     * @param  $location
     * @return mixed
     */
    public function secure_location_data($location) {
        $allowedKeys = ['address', 'lat', 'lng'];
        if (!is_array($location)) {
            return false;
        }
        foreach ($location as $k => $v) {
            if (in_array($k, $allowedKeys, true)) {
                if ('address' === $k) {
                    $location[$k] = sanitize_text_field($v);
                } else {
                    $location[$k] = floatval(filter_var($v, FILTER_SANITIZE_NUMBER_FLOAT));
                }
            } else {
                return;
            }
        }
        return $location;
    }

    /**
     * @param  $post_ids
     * @param  $class
     * @return mixed
     */
    public function localize_explore_page_results_ids(
        $post_ids,
        $class
    ) {
        $per_page = get_option('job_manager_per_page');
        $locations = array_slice($post_ids, 0, $per_page);
        $extraMarkers = [];
        foreach ($locations as $id) {
            $latLng = get_post_meta($id, '_additionallocations', true);
            if (is_array($latLng)) {
                foreach ($latLng as $lt) {
                    $extraMarkers[] = array(
                        'id'       => $id,
                        'location' => $lt
                    );
                }
            }
        }
        // load script for explore page work
        wp_enqueue_script('multi-location-explore', $this->plugin_url.'assets/js/multilocation-explore.js', array('jquery', 'listify', 'wp-util', 'listify-map'), $this->version, true);
        wp_localize_script('multi-location-explore', 'expPgAddiLoc', $extraMarkers);
        return $post_ids;
    }

    public function register_scripts() {
        $this->localize_scripts_data();
        wp_enqueue_style('multi-location-css', $this->plugin_url.'assets/css/multilocation.css');
        if (is_singular('job_listing')) {
            wp_enqueue_script('single-listing', $this->plugin_url.'assets/js/single-listing.js', array('jquery', 'listify', 'wp-util', 'listify-map', 'mapify'), $this->version, true);
            wp_localize_script('single-listing', 'mapSettings', array_merge($this->local['latLng'], $this->local['listMapData']));
            wp_localize_script('single-listing', 'additionallocations', $this->local['additionallocations']);
        }
        if (is_admin() && (isset($_GET['post']) && 'job_listing' === get_post_type($_GET['post']))) {
            wp_enqueue_script('admin-script', $this->plugin_url.'assets/js/admin-script.js', array('jquery', 'mapify'), $this->version, true);
            wp_localize_script('admin-script', 'additionallocations', $this->local['additionallocations']);
        }
    }

    public function preview_page_marker_listings() {
        wp_enqueue_script('preview-listing', $this->plugin_url.'assets/js/single-listing.js', array('jquery', 'listify', 'wp-util', 'listify-map', 'mapify'), $this->version, true);
        wp_localize_script('preview-listing', 'mapSettings', array_merge($this->local['defaultLatLng'], $this->local['listMapData']));
        wp_localize_script('preview-listing', 'additionallocations', $this->local['additionallocations']);
    }

    public function front_end_job_edit_submit() {
        wp_enqueue_script('frontend-script', $this->plugin_url.'assets/js/frontend-script.js', array('jquery', 'mapify'), $this->version, true);
        wp_localize_script('frontend-script', 'additionallocations', $this->local['additionallocations']);
    }

    /**
     * @param $post_id
     * @param $values
     */
    function save_post_location(
        $post_id,
        $values
    ) {
        $post_type = get_post_type($post_id);
        /* save / update the locations */
        if ('job_listing' == $post_type) {
            // sanitize the data before saving
            $extraMarkers = array_filter($_POST['additionallocation'], array($this, 'secure_location_data'));
            update_post_meta($post_id, '_additionallocations', $extraMarkers);
        }
    }

}

/**
 * Start things up.
 *
 * Use this function instead of a global.
 *
 * @since 1.0
 */
function wp_job_manager_multi_location() {

    // deactivate the plugin if dependency plugins not active
    $required = array('WP_Job_Manager', 'WP_Job_Manager_Extended_Location');
    foreach ($required as $class) {
        if (!class_exists($class)) {
            // Deactivate the plugin.
            deactivate_plugins(plugin_basename(__FILE__));
            return;
        }
    }
    return Keendevs_Multi_Location_WP_JOB_M::instance();
}

add_action('plugins_loaded', 'wp_job_manager_multi_location', 99);