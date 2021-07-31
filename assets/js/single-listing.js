(function( window, undefined ){

		/**
		 * Constructor.
		 */
		function MultiLocationMap() {

			// Get vars needed.
			this.options = mapSettings;
			this.additionallocations = additionallocations || [];
			this.canvas  = 'multi-location-listing-contact-map';
			this.allLatLng = [[mapSettings.lat, mapSettings.lng]]; // collect the default map lat lng to include with additionallocations

			if ( ! document.getElementById( this.canvas ) ) {
				return;
			}
			
			this.additionallocations.forEach(function(location){
				if( location.lat && location.lng ){
					this.allLatLng.push([location.lat, location.lng]);
				}
			}, this);

			console.log(this.allLatLng)

			// Setup map based on selected map provider.
			if ( 'googlemaps' === this.options.provider ) {
				return this.setupGoogleMaps();
			} else if ( 'mapbox' === this.options.provider ) {
				return this.setupMapBox();
			}
		}

		/**
		 * Google Maps Setup.
		 */
		MultiLocationMap.prototype.setupGoogleMaps = function() {

			// Var:
			this.latlng = new google.maps.LatLng( this.options.lat, this.options.lng );
			// create map bounds object
			this.bounds = new google.maps.LatLngBounds();

			// Set Map:
			this.map = new google.maps.Map( document.getElementById( this.canvas ), {
				zoom: parseInt( this.options.mapOptions.zoom ),
				center: this.latlng,
				scrollwheel: false,
				styles: this.options.mapOptions.styles,
				streetViewControl: false,
			} );

			// Remove other businesses.
			this.map.setOptions( { styles: [
				{
					featureType: "poi",
					stylers: [
						{
							visibility: "off",
						}
					],
				}
			] } );

			this.allLatLng.forEach(function(l){
				var position = new google.maps.LatLng(l[0], l[1]);
				this.bounds.extend( position );

				// var marker = new google.maps.Marker({
				// 	animation: google.maps.Animation.DROP,
				// 	map: this.map,
				// 	position: position,
				// });
				
				// Set Marker (using RichMarker Library):
				this.marker = new RichMarker({
					position: position,
					flat: true,
					draggable: false,
					content: '<div class="map-marker marker-color-' + this.options.term + ' type-' + this.options.term + '"><i class="' + this.options.icon + '"></i></div>'
				});

				this.marker.setMap( this.map );

			}, this);

			this.map.fitBounds( this.bounds );

		};

		/**
		 * MapBox Setup (Using Leaflet Library)
		 */
		MultiLocationMap.prototype.setupMapBox = function() {
			L.mapbox.accessToken = this.options.mapOptions.mapboxTileUrl;

			// Load Map:
			this.map = L.mapbox.map( this.canvas, 'mapbox.streets' ).setView( [
				this.options.lat,
				this.options.lng
			], parseInt( this.options.mapOptions.zoom ) );

			// Marker data:
			this.markerTemplate = wp.template( 'pinTemplate' ); // Loaded in footer.
			this.markerTemplateData = {
				mapMarker: {
					term: this.options.term,
					icon: this.options.icon,
				},
				status: {
					featured: false,
				}
			}
			this.markerIcon = L.divIcon( {
				iconSize: [30, 45],
				iconAnchor: [15, 45],
				className: '',
				html: this.markerTemplate( this.markerTemplateData ),
			} );

			// Add marker to map:
			// console.log(this.additionallocations)
			this.allLatLng.forEach(location => {
				this.marker = L.marker(location, { icon: this.markerIcon } ).addTo( this.map );
			});

			this.map.fitBounds(this.allLatLng);

		};

	// // Define Map.
	var MultiLocationMapInit = function() {
		return new MultiLocationMap();
	};

	// Load Map.
	MultiLocationMapInit();
	
})(window);