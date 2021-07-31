<?php 

class Multi_Location_Listify_Widget_Listing_Map extends Listify_Widget_Listing_Map {
    function __construct(){
        parent::__construct();
	}
	
	private function get_map_url($coordinates) {
		$base = 'http://maps.google.com/maps';
		$args = array(
			'daddr' => urlencode( $coordinates ),
		);
		return esc_url( add_query_arg( $args, $base ) );
	}

	private function multiLocation_get_direction($lat, $long){
		$attr = array(
			'href'   => esc_url( $this->get_map_url($lat . ',' . $long) ),
			'rel'    => 'nofollow',
			'target' => '_blank',
			'class'  => 'js-toggle-directions',
			'id'     => 'get-directions',
		);
	
		$attr_str = '';
	
		foreach ( $attr as $name => $value ) {
			$attr_str .= false !== $value ? sprintf( ' %s="%s"', esc_html( $name ), esc_attr( $value ) ) : esc_html( " {$name}" );
		}
		?>
		<div class="job_listing-directions">
			<a <?php echo $attr_str; // WPCS: XSS ok. ?>><?php esc_html_e( 'Get Directions', 'listify' ); ?></a>
		</div>
		<?php
	}

    function widget( $args, $instance ) {
		global $job_preview;

		if ( ! is_singular( 'job_listing' ) && ! $job_preview ) {
			echo $this->widget_areas_notice(); // WPCS: XSS ok.
			return false;
		}

		extract( $args );

		$listing = listify_get_listing();

		$fields = array( 'map', 'address', 'phone', 'email', 'web', 'directions' );

		foreach ( $fields as $field ) {
			$$field = ( isset( $instance[ $field ] ) && 1 == $instance[ $field ] ) || ! isset( $instance[ $field ] ) ? true : false;
		}

		// map also needs location data
		$map                  = $map && $listing->get_lat();
		$map_behavior_api_key = listify_get_google_maps_api_key();

		// figure out split
		$just_directions = $directions && ! ( $web || $address || $phone || $email );
		$split           = $map && ! $just_directions && ( $phone || $web || $address || $directions || $email ) ? 'map-widget-section--split' : '';

		/* Check if data available */
		$_email    = $listing->get_email();
		$_url      = $listing->get_url();
		$_location = $listing->get_location( 'raw' );
		$_phone    = $listing->get_telephone();

		$email      = $_email ? $email : false;
		$web        = $_url ? $web : false;
		$address    = $_location ? $address : false;
		$directions = $_location ? $directions : false;
		$phone      = $_phone ? $phone : false;

		ob_start();

		/* Only load if data exists */
		if ( ( $map && $map_behavior_api_key ) || $phone || $web || $address || $directions ) {
			echo $before_widget;
			?>

		<div class="map-widget-sections">

			<?php if ( $map && $map_behavior_api_key ) : ?>
			<div class="map-widget-section <?php echo esc_attr( $split ); ?>">
				<div id="multi-location-listing-contact-map"></div>
			</div>
			<div class="map-widget-section">
				<h4><?php esc_html_e('Locations', 'multi-location'); ?></h4>
				<?php
				$defaultLocation = get_post_meta(get_the_ID(), 'geolocation_formatted_address', true);
				printf('<p class="multi-location">%s</p>', sanitize_text_field($defaultLocation));
				if ( $directions ) :
					listify_the_listing_directions_form();
				endif;

				$locations = get_post_meta(get_the_ID(), '_additionallocations', true);
				if(is_array($locations) && !empty($locations)){
					foreach($locations as $l){
						$addr = WP_Job_Manager_Geocode::get_location_data($l['address']);
						printf('<p class="multi-location">%s</p>', sanitize_text_field($addr['formatted_address']));
						$this->multiLocation_get_direction($addr['lat'],$addr['long']);
					}
				}
				?>
			</div>
			<?php endif; ?>

			<?php if ( $phone || $web || $address || $directions ) : ?>
			<div class="map-widget-section <?php echo esc_attr( $split ); ?>">

						<?php
						do_action( 'listify_widget_job_listing_map_before' );

						if ( $address ) :
							listify_the_listing_location();
					endif;

						if ( $phone ) :
							listify_the_listing_telephone();
					endif;

						if ( $email ) :
							listify_the_listing_email();
					endif;

						if ( $web ) :
							listify_the_listing_url();
					endif;

						do_action( 'listify_widget_job_listing_map_after' );
						?>

			</div>
			<?php endif; ?>

		</div>

			<?php
			echo $after_widget;
		} // End if().

		$content = ob_get_clean();
		echo apply_filters( $this->widget_id, $content );

		add_filter( 'listify_page_needs_map', '__return_false' );
	}
}