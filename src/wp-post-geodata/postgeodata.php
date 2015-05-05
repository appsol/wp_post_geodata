<?php
/**
 * Plugin Name: Post Geo Data
 * Plugin URI: http://www.appropriatesolutions.co.uk/
 * Description: Allows geographic data to be attached to Posts. Provides widgets and shortcodes that allow posts to be displayed on maps.
 * Version: 0.1.0
 * Author: Stuart Laverick
 * Author URI: http://www.appropriatesolutions.co.uk/
 * Text Domain: Optional. wp_post_geodata
 * License: GPL2
 *
 * @package wp_post_geodata
 */
/*  Copyright 2015  Stuart Laverick  (email : stuart@appropriatesolutions.co.uk)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
namespace PostGeoData;

defined('ABSPATH') or die( 'No script kiddies please!' );

// require_once 'vendor/autoload.php';
require_once 'postgeodata_widget.php';
require_once 'postgeodata_options.php';

class PostGeoData
{

    /**
     * Singleton class instance
     *
     * @var object VideoPlaylists
     **/
    private static $instance = null;

    /**
     * Holds the lst error message from the API or empty if none
     *
     * @var string
     **/
    public $lastError;

    /**
     * Constructor for PostGeoData
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function __construct()
    {
        add_action("widgets_init", [$this, 'register']);
        add_shortcode('posts_geodata_map', [$this, 'shortcodeHandler']);
        add_action('wp_enqueue_scripts', [$this, 'actionEnqueueAssets']);
        add_action('admin_enqueue_scripts', [$this, 'actionEnqueueAdminAssets']);

        if (is_admin()) {
            $optionsPage = new PostGeoDataOptions();
        }
    }

    /**
     * Creates or returns an instance of this class
     *
     * @return A single instance of this class
     * @author Stuart Laverick
     **/
    public static function getInstance()
    {
        if (null == self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    /**
     * Register the Widget
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function register()
    {
        register_widget('PostGeoData\PostGeoDataWidget');
    }

    /**
     * Load any scripts and styles needed on the front
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function actionEnqueueAssets()
    {
        $options = get_option('wp_post_geodata');

        if ($options['load_css'] == 'yes') {
            wp_register_style('wp-post-geodata', plugin_dir_url(__FILE__) . 'assets/css/style.css');
            wp_enqueue_style('wp-post-geodata');
        }
        $key = empty($options['googlemaps_simple_key'])? $options['googlemaps_simple_key'] : '';
        wp_enqueue_script('wp-post-geodata-googlemaps', 'https://maps.googleapis.com/maps/api/js?key=' . $key, null, null, true);
        wp_enqueue_script('wp-post-geodata-main', plugin_dir_url(__FILE__) . 'assets/js/main.js', ['jquery', 'wp-post-geodata-googlemaps'], '0.3.0', true);
    }

    /**
     * Load any scripts and styles needed on the admin pages
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function actionEnqueueAdminAssets()
    {
        $options = get_option('wp_post_geodata');

        wp_register_style('wp-post-geodata-admin', plugin_dir_url(__FILE__) . 'assets/css/admin.css');
        wp_enqueue_style('wp-post-geodata-admin');

        $key = empty($options['googlemaps_simple_key'])? $options['googlemaps_simple_key'] : '';
        wp_enqueue_script('wp-post-geodata-googlemaps', 'https://maps.googleapis.com/maps/api/js?key=' . $key, null, null, true);
        wp_enqueue_script('wp-post-geodata-admin', plugin_dir_url(__FILE__) . 'assets/js/admin.js', ['jquery', 'wp-post-geodata-googlemaps'], '0.3.0', true);
    }

    /**
     * Handler for shortcode calls
     *
     * Options:
     *
     * @return string HTML of the map
     * @author Stuart Laverick
     **/
    public function shortcodeHandler($attributes)
    {
        global $post;
        extract(shortcode_atts(array(
            'regions' => 'regions',
            'categories' => '',
            'height' => 426,
            'width' => 700
                        ), $attributes));
        $categories = array_map('trim', explode(',', $categories));
        update_option('appsol_geodata_categories_' . $post->ID, $categories);
        update_option('appsol_geodata_regions_cat_' . $post->ID, $regions);
        $this->showMap($post->ID, $height, $width, $regions);
    }

    /**
     * undocumented function
     *
     * @return void
     * @author 
     **/
    public function getPostMap($postid, $height, $width, $regions) {
        global $post;
        $the_post = $post;
        $q = array(
            'meta_key' => 'region',
            'tax_query' => array(
                'taxonomy' => 'category',
                'field' => 'slug',
                'terms' => 'regions'
            )
        );
        $query = new WP_Query($q);
        ?>
        <div id="map" data-map-id="<?php echo $postid; ?>" style="width: <?php echo $width; ?>px; height: <?php echo $height; ?>px;"></div>
        <?php if ($query->have_posts()): ?>
            <div id="regions" class="nav">
                <div class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">Regions<b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <?php while ($query->have_posts()): $query->the_post(); ?>
                            <li><a class="region" data-region-id="<?php echo get_post_meta($post->ID, "region", true) ?>" href="#"><?php echo get_the_title($post->ID); ?></a></li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
            <?php
        endif;
        $post = $the_post;
    }
}

$postGeoData = PostGeoData::getInstance();

/*
  Plugin Name: Post Geo Data
  Plugin URI: http://www.appropriatesolutions.co.uk/
  Description: Allows the geographic context of each post to be recorded and provides widgets and shortcodes to display that data in map form.
  Author: Stuart Laverick
  Version: 0.1
  Author URI: http://www.appropriatesolutions.co.uk/
 */

class appsolPostGeoData {

    function __construct() {
        parent::__construct(
                'appsol_post_geodata', 'Post Geo Data', array('description' => __('Displays a static or dynamic Google map showing the location associated with the post')));
        add_action("save_post", array('appsolPostGeoData', 'delete_widget_transient'));
        add_action("trash_post", array('appsolPostGeoData', 'delete_widget_transient'));
    }

    public static function init() {
        global $post;
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            register_widget('appsolPostGeoData');
            add_shortcode('geodata-map', array('appsolPostGeoData', 'mapShortcodeHandler'));
            add_shortcode('geodata-copy', array('appsolPostGeoData', 'copyShortcodeHandler'));
            $ajax_nonce = wp_create_nonce('appsol_post_geodata');
            add_action('wp_ajax_get_locations', array('appsolPostGeoData', 'get_geoposts'));
            add_action('wp_ajax_nopriv_get_locations', array('appsolPostGeoData', 'get_geoposts'));
            add_action('wp_ajax_get_geopost', array('appsolPostGeoData', 'get_geopost'));
            add_action('wp_ajax_nopriv_get_geopost', array('appsolPostGeoData', 'get_geopost'));
            if (is_admin()) {
                add_action('admin_init', array('appsolPostGeoData', 'add_geographic_meta_box'));
                add_action('save_post', array('appsolPostGeoData', 'save_geography_meta'));
                wp_register_script('google-maps', 'http://maps.google.com/maps/api/js?sensor=false');
                wp_enqueue_script('admin_maps', plugins_url('js/admin_maps.js', __FILE__), array('jquery', 'google-maps'), '1.0', false);
                wp_enqueue_style('admin_maps_style', plugins_url('css/admin_maps.css', __FILE__));
            } else {
                wp_enqueue_script('map_page', plugins_url('js/map_page.js', __FILE__), array('jquery'), '1.0', true);
                wp_localize_script('map_page', 'appsolPostGeoData', array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'pluginurl' => plugin_dir_url(__FILE__),
                    'themeurl' => get_stylesheet_directory_uri(),
                    'ajax_nonce' => $ajax_nonce));
            }
        }
    }

    function form($instance) {
        $defaults = array('title' => 'Post Geo Map');
        $instance = wp_parse_args((array) $instance, $defaults);
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = $new_instance['title'];

        return $instance;
    }

    function widget($args, $instance) {
        extract($args);
    }

    public static function mapShortcodeHandler($attributes) {
        global $post;
        extract(shortcode_atts(array(
            'regions' => 'regions',
            'categories' => '',
            'height' => 426,
            'width' => 700
                        ), $attributes));
        $categories = array_map('trim', explode(',', $categories));
        update_option('appsol_geodata_categories_' . $post->ID, $categories);
        update_option('appsol_geodata_regions_cat_' . $post->ID, $regions);
        self::showMap($post->ID, $height, $width, $regions);
    }

    public static function showMap($postid, $height, $width, $regions) {
        global $post;
        $the_post = $post;
        $q = array(
            'meta_key' => 'region',
            'tax_query' => array(
                'taxonomy' => 'category',
                'field' => 'slug',
                'terms' => 'regions'
            )
        );
        $query = new WP_Query($q);
        ?>
        <div id="map" data-map-id="<?php echo $postid; ?>" style="width: <?php echo $width; ?>px; height: <?php echo $height; ?>px;"></div>
        <?php if ($query->have_posts()): ?>
            <div id="regions" class="nav">
                <div class="dropdown">
                    <a class="dropdown-toggle" data-toggle="dropdown" href="#">Regions<b class="caret"></b></a>
                    <ul class="dropdown-menu">
                        <?php while ($query->have_posts()): $query->the_post(); ?>
                            <li><a class="region" data-region-id="<?php echo get_post_meta($post->ID, "region", true) ?>" href="#"><?php echo get_the_title($post->ID); ?></a></li>
                        <?php endwhile; ?>
                    </ul>
                </div>
            </div>
            <?php
        endif;
        $post = $the_post;
    }

    public static function copyShortcodeHandler() {
        ?>
        <div id="map_copy"></div>
        <?php
    }

    public static function add_geographic_meta_box() {
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        add_meta_box('geographic', 'Tag Post with Location', array('appsolPostGeoData', 'geographic_meta_box'), 'post');
//        add_meta_box('region', 'Set the Region', array('appsolPostGeoData', 'region_meta_box'), 'post');
        if (is_plugin_active('the-events-calendar/the-events-calendar.php'))
            add_meta_box('tribe_event_geographic', 'Tag Event With Location', array('appsolPostGeoData', 'tribe_geographic_meta_box'), 'tribe_events');
    }

    public static function geographic_meta_box() {
        global $post;

        echo '<input type="hidden" name="geographic_meta_box_nonce" value="' . wp_create_nonce(basename(__FILE__)) . '" />';
        ?> <p>To tag this post with a geographic location, enter the lat-long below.</p>
        <p>If you do not know the lat-lng, enter as much of the address as you can and press the geocode button.
            This will give you an approximate location based on what you've entered. You can then drag the pin on the map to the precise location.</p>
        <div class="location">
            <table class="form-table">
                <tbody>
                    <tr>
                        <th scope="row">
                            <label for="lat_lng">Lat-Lng</label>
                        </th>
                        <td>
                            <input type="text" name="lat_lng" id="lat_lng" value="<?php echo get_post_meta($post->ID, "lat_lng", true); ?>"/>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="street_address1">Street Address 1</label>
                        </th>
                        <td>
                            <input type="text" name="street_address1" id="street_address1" class="address-field" value="<?php echo get_post_meta($post->ID, "street_address1", true) ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="street_address2">Street Address 2</label>
                        </th>
                        <td>
                            <input type="text" name="street_address2" id="street_address2" class="address-field" value="<?php echo get_post_meta($post->ID, "street_address2", true) ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="city">City</label>
                        </th>
                        <td>
                            <input type="text" name="city" class="address-field" id="city" value="<?php echo get_post_meta($post->ID, "city", true) ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="province">State / Province</label>
                        </th>
                        <td>
                            <input type="text" name="province" class="address-field" id="province" value="<?php echo get_post_meta($post->ID, "province", true) ?>" />
                            <input type="hidden" name="region" id="region" value="<?php echo get_post_meta($post->ID, "region", true) ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="postal_code">Postal / Zip Code</label>
                        </th>
                        <td>
                            <input type="text" name="postal_code" class="address-field" id="postal_code" value="<?php echo get_post_meta($post->ID, "postal_code", true) ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="country">Country</label>
                        </th>
                        <td>
                            <input type="text" name="country" class="address-field" id="country" value="<?php echo get_post_meta($post->ID, "country", true) ?>" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="iso3166">ISO 3166</label>
                        </th>
                        <td>
                            <input type="text" name="iso3166" class="address-field" id="iso3166" value="<?php echo get_post_meta($post->ID, "iso3166", true); ?>"/>
                        </td>
                    </tr>
                </tbody>
            </table>
            <p><button type="button" id="geocode_button">Geocode</button></p>
        </div>
        <div id="admin_map_container" class="map-container"></div>
        <?
    }

    public static function tribe_geographic_meta_box() {
        global $post;
        wp_enqueue_script('admin_maps', get_stylesheet_directory_uri() . '/js/admin_maps.js', array('jquery', 'google-maps'), '1.0', false);
        wp_enqueue_style('admin_maps_style', get_stylesheet_directory_uri() . '/admin_maps.css');
        echo '<input type="hidden" name="geographic_meta_box_nonce" value="' . wp_create_nonce(basename(__FILE__)) . '" />';
        ?>
        <div class="location">
            <div class="instructions">
                <p>The location of this event will be displayed on maps based on the address entered above.</p>
                <p>If you want a more precise location, press the geocode button then drag the pin on the map to the right place.
                </p>
                <button type="button" id="geocode_button">Geocode</button>
            </div>
            <div class="lat-lng">
                <label>Lat-Lng</label>
                <input type="text" name="lat_lng" id="lat_lng" value="<?php echo get_post_meta($post->ID, "lat_lng", true); ?>"/>
            </div>
        </div>
        <div id="admin_map_container" class="map-container"></div>
        <?php
    }

    public static function region_meta_box() {
        global $post;
        ?>
        <input type="hidden" name="region_meta_box_nonce" value="<?php wp_create_nonce(basename(__FILE__)); ?>" />';
        <p>Set the region this post</p>

        <?php
    }

    public static function save_geography_meta($post_id) {
        if (!wp_verify_nonce($_POST['geographic_meta_box_nonce'], basename(__FILE__))) {
            return $post_id;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return $post_id;
        }
        if ('page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return $post_id;
            }
        } elseif (!current_user_can('edit_post', $post_id)) {
            return $post_id;
        }
        $post_meta_fields = Array(
            "lat_lng",
            "street_address1",
            "street_address2",
            "city",
            "province",
            "region",
            "postal_code",
            "country",
            "iso3166"
        );
        foreach ($post_meta_fields as $post_meta_field) {
            $old = get_post_meta($post_id, $post_meta_field, true);
            $new = $_POST[$post_meta_field];
            if ($new && $new != $old) {
                update_post_meta($post_id, $post_meta_field, $new);
            } elseif ($new == '') {
                delete_post_meta($post_id, $post_meta_field);
            }
        }
    }

    public static function get_geopost() {
        
        check_ajax_referer('appsol_post_geodata', 'appsol_ajax_nonce');
        global $post;
        if (!isset($_POST['post']))
            die();
        $q = array();
        if (is_numeric($_POST['post'])) {
            $q['p'] = $_POST['post'];
        } else {
            if (isset($_POST['type']) && $_POST['type'] == 'region') {
                $q['meta_key'] = 'region';
                $q['meta_value'] = $_POST['post'];
                $q['tax_query'] = array(
                    array(
                    'taxonomy' => 'category',
                    'field' => 'slug',
                    'terms' => 'regions'
                        )
                );
            } else {
                $q['name'] = $_POST['post'];
            }
        }
        $query = new WP_Query($q);
        while ($query->have_posts()): $query->the_post();
            ?>
            <div class="<?php echo implode(' ', get_post_class('post geopost', $post->ID)); ?>" id="post-<?php echo $post->ID; ?>">
                <div class="hd">
                    <h3><a href="<?php echo get_permalink($post->ID) ?>" rel="bookmark" title="Permanent Link to <?php echo strip_tags(get_the_title($post->ID)); ?>"><?php echo get_the_title($post->ID); ?></a></h3>
                    <p class="date"><span class="post-date"><?php echo get_the_time('F jS, Y', $post) ?></span> by <span class="post-author"><?php echo get_the_author($post->ID) ?></span></p>
                </div>
                <div class="bd">
                    <div class="copy">
                        <?php
                        $content = get_the_content('Read the rest of this entry &raquo;');
                        $content = apply_filters('the_content', $content);
                        $content = str_replace(']]>', ']]&gt;', $content);
                        echo $content;
                        ?>
                    </div>
                </div>
                <div class="ft">
                    <p class="postmetadata">
                        <?php if ($tags = get_the_tags($post->ID)): ?>
                            Tags: <?php
                            foreach ($tags as $tag)
                                echo $tag->name . ',';
                            ?>
                        <?php endif; ?> Posted in <?php
                        $categories = get_the_category($post->ID);
                        foreach ($categories as $category):
                            ?>
                            <a href="<?php echo get_category_link($category->term_id); ?>" title="<?php echo esc_attr(sprintf(__("View all posts in %s"), $category->name)); ?>"><?php echo $category->cat_name; ?></a>,
                        <?php endforeach; ?>
            <?php if (comments_open() && get_comments_number()): ?> <a href="<?php echo get_comments_link($post->ID); ?>">Comments</a><?php endif; ?></p>
                </div>
            </div>
            <?php
        endwhile;
    }

    public static function get_geoposts() {

        check_ajax_referer('appsol_post_geodata', 'appsol_ajax_nonce');
        include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
        global $post;
        $categories = get_option('appsol_geodata_categories_' . $_POST['map_id']);
        // query for posts with location tagging, load into array
        $q = array(
            'meta_key' => 'lat_lng',
            'posts_per_page' => -1
        );
        if ($categories) {
            if (!is_array($categories))
                $categories = array($categories);
            $q['tax_query'] = array(
                'relation' => 'OR',
                array(
                    'taxonomy' => 'category',
                    'field' => 'slug',
                    'terms' => $categories
                )
            );
        }
        
        if (isset($_POST['posts'])) {
            $ids = explode(',', $_POST['posts']);
            if (is_array($ids))
                $q['post__in'] = $ids;
        }
        
        $geo_query = new WP_Query($q);
        $data = Array();
        $locations = array();
        
        while ($geo_query->have_posts()) {
            $geo_query->the_post();
            $lat_lng = get_post_meta(get_the_id(), 'lat_lng', true);
            if (has_post_thumbnail($post->ID)) {
                $post_thumbnail_id = get_post_thumbnail_id($post->ID);
                $post_thumbnail_url = wp_get_attachment_url($post_thumbnail_id);
            }
            $category = self::lastCategory(get_the_category());
            $post_data = array(
                'id' => get_the_id(),
                'title' => get_the_title(),
                'lat_lng' => $lat_lng,
                'link' => get_permalink(),
                'image' => $post_thumbnail_url,
                'category' => $category->slug
            );
            $data[get_the_id()] = $post_data;
//            $locations[] = $lat_lng;
        }

        if (is_plugin_active('the-events-calendar/the-events-calendar.php')) {
// same process for events
            $event_query = new WP_Query(array(
                'post_type' => 'tribe_events',
                'meta_query' => array(array(
                        'key' => '_EventStartDate',
                        'value' => date('Y-m-d'),
                        'compare' => '>',
                        'type' => 'DATE'
                    )),
                'posts_per_page' => -1
            ));
            while ($event_query->have_posts()) {
                $event_query->the_post();
                $event_latlng = get_post_meta(get_the_id(), "lat_lng", true);
                if (!$event_latlng) {
                    require(dirname(__FILE__) . '/GoogleGeocode.php');
                    $geo = new GoogleGeoCode('');

                    $result = $geo->geocode(appsol_tribe_get_full_address(get_the_ID()));
                    $event_latlng = $result['Placemarks'][0]['Latitude'] . ',' . $result['Placemarks'][0]['Longitude'];
                }
                $post_data = array(
                    'title' => get_the_title(),
                    'lat_lng' => $event_latlng,
                    'categories' => wotw_category_sort(get_the_category()),
                    'link' => get_permalink());
                $data[get_the_id()] = $post_data;
                $locations[] = $event_latlng;
            }
        }

        echo json_encode($data);
        die();
    }

    public static function lastCategory($categories) {
        if (count($categories) == 1)
            return $categories[0];
        $last = 0;
        foreach ($categories as $key => $category) {
            if ($category->parent != 0) {
                $last = $key;
            }
        }
        if ($last)
            return $categories[$last];
        return array_pop($categories);
    }

    public static function get_location($post_id) {
        $city = get_post_meta($post_id, 'city', true);
        $province = get_post_meta($post_id, 'province', true);
        $country = get_post_meta($post_id, 'country', true);

        if ($city || $province)
            $location[] = ($city) ? $city : $province;
        if ($country)
            $location[] = $country;

        if (count($location) > 1) {
            return implode(', ', $location);
        } elseif (count($location) == 1) {
            return $location[0];
        } else {
            return null;
        }
    }

    public static function make_static_map($post_id, $class, $icon, $width=200, $height=100, $zoom=3) {
        $icon = ($icon) ? 'icon:' . $icon . '|' : 'size:tiny|';
        $class = ($class) ? 'class="' . $class . '"' : '';
        $location = self::get_location($post_id);
        $latlng = get_post_meta($post_id, "lat_lng", true);
        if ($latlng) {
            return '<img src="http://maps.googleapis.com/maps/api/staticmap?center=' . $latlng . '&zoom=' . $zoom . '&size=' . $width . 'x' . $height . '&sensor=false&markers=' . $icon . $latlng . '" alt="Map showing ' . $location . '"' . $class . '/>';
        } else {
            return null;
        }
    }

    /**
     * Deletes all transients associated with this plugin when a post is saved
     * Prevents transients holding out of date data
     */
    function delete_widget_transient() {
        $results = array();
        foreach (wp_get_sidebars_widgets() as $array) {
            $results = preg_grep('/^appsolPostGeoData.*/', $array);
            foreach ($results as $result) {
                delete_transient('post_geodata_' . $result);
            }
        }
    }

    public static function log($message = '') {
        if (WP_DEBUG === true) {
            $trace = debug_backtrace();
            $caller = $trace[1];
            error_log(isset($caller['class']) ? $caller['class'] . '::' . $caller['function'] : $caller['function']);
            if ($message)
                error_log(is_array($message) || is_object($message) ? print_r($message, true) : $message);
        }
    }

}

// add_action("widgets_init", array('appsolPostGeoData', 'init'));