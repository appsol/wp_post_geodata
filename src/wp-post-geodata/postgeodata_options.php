<?php
/**
 * PostGeoDataOptions
 * 
 * @package wp_post_geodata
 * @author Stuart Laverick
 */
namespace PostGeoData;

defined('ABSPATH') or die( 'No script kiddies please!' );

class PostGeoDataOptions
{
    /**
     * Holds the values to be used in the fields callbacks
     *
     * @var string
     **/
    private $options;

    /**
     * The fields used in an address and their human readable titles
     *
     * @var array
     **/
    private $addressFields = [
            'lat_lng' => 'Lat,Lng',
            'street_address1' => 'Street Address 1',
            'street_address2' => 'Street Address 2',
            'city' => 'City',
            'province' => 'State / Province',
            'postal_code' => 'Postal Code',
            'country' => 'Country',
            'iso3166' => 'ISO3166'
        ];

    /**
     * Constructor
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function __construct()
    {
        add_action( 'admin_menu', [$this, 'addPluginPage'] );
        add_action( 'admin_init', [$this, 'pageInit'] );
        add_action( 'load-post.php', [$this, 'addMetaBoxes'] );
        add_action( 'load-post-new.php', [$this, 'addMetaBoxes'] );
    }

    /**
     * Adds the Settings menu menu item
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function addPluginPage()
    {
        // This page will be under "Settings"
        add_options_page(
            'Post GeoData Options', 
            'Post GeoData', 
            'manage_options', 
            'postgeodata-admin', 
            array( $this, 'createAdminPage' )
        );
    }

    /**
     * Callback for options page
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function createAdminPage()
    {
        // Set class property
        $this->options = get_option( 'wp_post_geodata' );
        ?>
        <div class="wrap">
            <h2>Post GeoData Settings</h2>
            <form method="post" action="options.php">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'wp_post_geodata_option_group' );
                do_settings_sections( 'postgeodata-setting-admin' );
                submit_button();
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function pageInit()
    {
        register_setting(
            'wp_post_geodata_option_group', // Option group
            'wp_post_geodata', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'google_maps_api', // ID
            'Google Maps API', // Title
            array( $this, 'printGoogleMapsApiInfo' ), // Callback
            'postgeodata-setting-admin' // Page
        );

        add_settings_field(
            'googlemaps_simple_key', // ID
            'Google Maps Simple API Key', // Title 
            array( $this, 'googleMapsSimpleKeyCallback' ), // Callback
            'postgeodata-setting-admin', // Page
            'google_maps_api' // Section
        );

        // add_settings_field(
        //     'load_css', // ID
        //     'Load Plugin CSS', // Title 
        //     array( $this, 'loadCssCallback' ), // Callback
        //     'postgeodata-setting-admin', // Page
        //     'google_maps_api' // Section
        // );

        // add_settings_field(
        //     'load_js', // ID
        //     'Load Plugin Javascript', // Title 
        //     array( $this, 'loadJsCallback' ), // Callback
        //     'postgeodata-setting-admin', // Page
        //     'google_maps_api' // Section
        // );
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     * @return void
     * @author Stuart Laverick
     **/
    public function sanitize($input)
    {
        $newInput = array();

        if (isset( $input['googlemaps_simple_key'] )) {
            $newInput['googlemaps_simple_key'] = sanitize_text_field($input['googlemaps_simple_key']);
        }

        // if (isset( $input['load_css'] ) && $input['load_css'] === 'yes') {
        //     $newInput['load_css'] = 'yes';
        // }

        // if (isset( $input['load_js'] ) && $input['load_js'] === 'yes') {
        //     $newInput['load_js'] = 'yes';
        // }

        return $newInput;
    }

    /**
     * Print the section text for the Google Maps API section
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function printGoogleMapsApiInfo()
    {
        print "Enter your API keys and Authentication details";
    }

    /**
     * Prints the input field for youtube_simple_key
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function googleMapsSimpleKeyCallback()
    {
        printf(
            '<input type="text" id="googlemaps_simple_key" name="wp_post_geodata[googlemaps_simple_key]" value="%s" />',
            isset( $this->options['googlemaps_simple_key'] ) ? esc_attr( $this->options['googlemaps_simple_key']) : ''
        );
    }

    /**
     * Prints the checkbox field for load_css
     *
     * @return void
     * @author Stuart Laverick
     **/
    // public function loadCssCallback()
    // {
    //     printf(
    //         '<input type="checkbox" id="load_css" name="wp_post_geodata[load_css]" value="yes" %s/>',
    //         isset( $this->options['load_css'] ) ? 'checked ' : ''
    //     );
    // }

    /**
     * Prints the checkbox field for load_js
     *
     * @return void
     * @author Stuart Laverick
     **/
    // public function loadJsCallback()
    // {
    //     printf(
    //         '<input type="checkbox" id="load_js" name="wp_post_geodata[load_js]" value="yes" %s/>',
    //         isset( $this->options['load_js'] ) ? 'checked ' : ''
    //     );
    // }

    /**
     * Add the listeners to show and save Meta Boxes on post edit pages
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function addMetaBoxes()
    {
        add_action( 'add_meta_boxes', array( $this, 'addGeoDataMetaBox' ) );
        add_action( 'save_post', array( $this, 'saveGeoDataMetaBox' ) );
    }

    /**
     * Adds the GeoData Meta Box to the post edit page
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function addGeoDataMetaBox($post_type)
    {
        $post_types = array('post', 'page');     //limit meta box to certain post types
            if ( in_array( $post_type, $post_types )) {
                add_meta_box(
                    'post_geodata',
                    __( 'Tag this Post with a location', 'wp_post_geodata' ),
                    [$this, 'showGeoDataMetaBox'],
                    $post_type,
                    'advanced',
                    'high'
                );
            }
    }

    /**
     * Prints the GeoData Meta Box HTML
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function showGeoDataMetaBox($postType)
    {
        global $post;
        $postGeoData = $this->getPostMeta($post->ID);
        $html = ['<p>' . __('To tag this post with a geographic location, enter the lat-long below.') . '</p>'];
        $html[] = '<p>' . __('If you do not know the lat-lng, enter as much of the address as you can and press the geocode button. This will give you an approximate location based on what you\'ve entered. You can then drag the pin on the map to the precise location.') . '</p>';
        $html[] = '<div class="location">';
        $html[] = '<input type="hidden" name="post_geodata_nonce" value="' . wp_create_nonce(basename(__FILE__)) . '" />';
        $html[] = '<table class="form-table">';
        $html[] = '<tbody>';
        foreach ($this->addressFields as $key => $value) {
            $row = [
                '<tr>',
                '<th scope="row">',
                '<label for="' . $key . '">' . $value . '</label>',
                '</th>',
                '<td>',
                '<input type="text" name="post_geodata[' . $key . ']" id="' . $key . '" value="' . $postGeoData[$key] . '"/>',
                '</td>',
                '</tr>'
            ];
            $html = array_merge($html, $row);
        }

        $html[] = '</tbody>';
        $html[] = '</table>';
        $html[] = '<p><button type="button" id="geocode_button">Geocode</button></p>';
        $html[] = '</div><!-- /.location -->';
        $html[] = '<div id="wp_post_geodata_map" class="map-container"></div>';

        echo implode("\n", $html);
    }

    /**
     * Save the Post Geo Data meta data
     *
     * @return void
     * @author Stuart Laverick
     **/
    public function saveGeoDataMetaBox($postId)
    {
        if (!$this->canSave($postId, 'post_geodata_nonce')) {
            return $postId;
        }

        // Sanitize the user input.
        $postGeoData = $this->sanitizeGeoData($_POST['post_geodata']);

        // Update the meta field.
        if (empty($postGeoData)) {
            delete_post_meta($postId, '_post_geodata');
        } else {
            update_post_meta($postId, '_post_geodata', $postGeoData);
        }

    }

    /**
     * Verify this came from the admin page and with proper authorization
     *
     * @return bool allowed to save the metadata or not
     * @author Stuart Laverick
     **/
    private function canSave($postId, $nonceName)
    {
        $canSave = true;
        // Check if our nonce is set.
        if ( ! isset( $_POST[$nonceName] ) ) {
            $canSave = false;
        }
        $nonce = $_POST[$nonceName];

        // Verify that the nonce is valid.
        if (! wp_verify_nonce($nonce, basename(__FILE__))) {
            $canSave = false;
        }
        // If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            $canSave = false;
        }
        // Check the user's permissions.
        if ( 'page' == $_POST['post_type'] ) {
            if ( ! current_user_can( 'edit_page', $postId ) ) {
                $canSave = false;
            }
        } else {
            if ( ! current_user_can( 'edit_post', $postId ) ) {
                $canSave = false;
            }
        }
        return $canSave;
    }

    /**
     * Sanitize the data submitted in the Post Geo data form
     *
     * @return array the sanitized data
     * @author Stuart Laverick
     **/
    private function sanitizeGeoData($input)
    {
        $newInput = [];
        foreach (array_keys($this->addressFields) as $key) {
            if (isset($input[$key])) {
                $newInput[$key] = sanitize_text_field($input[$key]);
            }
        }
        return $newInput;
    }

    /**
     * The geodata post meta is stored in a different format to the past version of this plugin.
     * This allows the past data to be used and incrementally updated to the new format
     *
     * @param Int the Post ID to use in fetching the meta data
     * @return Array geodata post meta
     * @author Stuart Laverick
     **/
    private function getPostMeta ($postId)
    {
        $postMeta = get_post_meta($postId, '_post_geodata', true);
        error_log(print_r($postMeta, true));
        if (!$postMeta) {
            $legacyPostMetaFields = [
                "lat_lng",
                "street_address1",
                "street_address2",
                "city",
                "province",
                "region",
                "postal_code",
                "country",
                "iso3166"
            ];
            $legacyPostMeta = [];
            foreach ($legacyPostMetaFields as $field) {
                $legacyPostMeta[$field] = get_post_meta($postId, 'post_geodata_' . $field, true);
            }
            $postMeta = $this->sanitizeGeoData($legacyPostMeta);
        }
        return $postMeta;
    }
}