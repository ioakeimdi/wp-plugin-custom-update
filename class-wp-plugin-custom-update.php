<?php
defined( 'ABSPATH' ) || exit;

class WP_Plugin_Custom_Update {

	private $plugin_slug;
	private $update_info_json;
	private $info_json;
	private $current_version;
	private $new_version;
	private $update_path;
	private $slug;
	private $domain;

    public function __construct( $plugin_slug, $update_info_json ) {
		$this->domain = 'wp-plugin-custom-update';
		$this->plugin_slug = $plugin_slug;
		$this->current_version = $this->get_current_version( $this->plugin_slug );

		if ( is_wp_error( $this->current_version ) ) {
			return;
		}

		$this->slug = basename( $plugin_slug, '.php' );
		$this->update_info_json = $update_info_json;
		$this->info_json = $this->get_remote(); // uses $update_info_json

		if ( is_wp_error( $this->info_json ) ) {
			return;
		}

		$this->new_version = $this->get_info( $this->info_json, 'version' );
		$this->update_path = $this->get_info( $this->info_json, 'download_url' );

		// define the alternative API for updating checking
		add_filter( 'site_transient_update_plugins', array( $this, 'check_update' ) );

		// Define the alternative response for information checking
		add_filter( 'plugins_api', array( $this, 'check_info' ), 10, 3 );
    }

	public function check_update( $transient ) {
		if ( empty( $transient->checked ) ) {
			return $transient;
		}
		
		if ( version_compare( $this->current_version, $this->new_version, '<' ) ) {
			$obj = new stdClass();
			$obj->slug = $this->slug;
			$obj->new_version = $this->new_version;
			$obj->plugin = $this->plugin_slug;
			$obj->package = $this->update_path;
			$transient->response[$this->plugin_slug] = $obj;
		}

		return $transient;
	}

	public function check_info( $res, $action, $args ) {
		if ( 'plugin_information' !== $action ) {
			return $res;
		}

		if ( $args->slug !== $this->slug ) {
			return $res;
		}

		$res = new stdClass();
		$res->name = $this->info_json['name'];
		$res->slug = $this->slug;
		$res->version = $this->new_version;
		$res->author = $this->info_json['author'];
		$res->download_link = $this->update_path;
		
		if ( isset( $this->info_json['sections']['description'] ) ) {
			$res->sections = array(
				'description' => $this->info_json['sections']['description'],
			);
		}
		
		return $res;
	}

	public function get_current_version() {
		$plugin_file = WP_PLUGIN_DIR . '/' . $this->plugin_slug;
		
		if ( ! file_exists( $plugin_file ) ) {
 		   return new WP_Error( 'plugin_not_found', __( 'Plugin file does not exist.', $this->domain ) );
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';

		$plugin_data = get_plugin_data( $plugin_file );
		if ( empty( $plugin_data['Version'] ) ) {
    		return new WP_Error( 'invalid_plugin', __( 'Could not retrieve plugin version.', $this->domain ) );
		}

		return $plugin_data['Version'];
	}

	public function get_remote() {
		$response = wp_remote_get( $this->update_info_json );
		
		if ( is_wp_error( $response ) ) {
			return new WP_Error(
				'http_request_failed',
				sprintf(
					__( 'Request failed: %s', $this->domain ),
					$response->get_error_message()
				)
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true ); // true = associative array

		if ( json_last_error() !== JSON_ERROR_NONE ) {
			return new WP_Error(
				'json_parse_error',
				sprintf(
					__( 'Invalid JSON: %s', $this->domain ),
					json_last_error_msg()
				)
			);
		}

		return $data;
	}

	public function get_info( $data, $action = '' ) {
		switch ( $action ) {
			case 'version':
				if ( isset( $data['version'] ) ) {
					return $data['version'];
				} else {
					return new WP_Error(
						'version_missing',
						__( 'The "version" field is missing in the JSON file.', $this->domain )
					);
				}
				break;
			case 'download_url':
				if ( isset( $data['download_url'] ) ) {
					return $data['download_url'];
				} else {
					return new WP_Error(
						'download_url_missing',
						__( 'The "download_url" field is missing in the JSON file.', $this->domain )
					);
				}
				break;
		}
	}

}