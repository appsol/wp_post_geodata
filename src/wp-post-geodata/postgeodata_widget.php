<?php
/**
 * PostGeoDataWidget
 * 
 * @package wp_post_geodata
 * @author Stuart Laverick
 */
namespace PostGeoData;

defined('ABSPATH') or die( 'No script kiddies please!' );

 class PostGeoDataWidget extends \WP_Widget
 {

  /**
  * Constructor
  *
  * @return void
  * @author Stuart Laverick
  */
  function __construct()
  {
    parent::__construct(
        'postgeodata',
        __('Post GeoData', 'wp_post_geodata'),
        ['description' => __('Displays a static or dynamic Google map showing the location associated with the post')]
      );
  }

  /**
   * Display the form for the widget settings
   *
   * @return void
   * @author Stuart Laverick
   **/
  function form($instance)
  {

  }

  /**
     * Update the settings for this instance of the widget
     *
     * @return Array the updated settings array
     * @author Stuart Laverick
     **/
    function update($new_instance, $old_instance)
    {

    }

    /**
     * Display the widget
     *
     * @return void
     * @author Stuart Laverick
     **/
    function widget($args, $instance)
    {
        extract($args);
    }
}