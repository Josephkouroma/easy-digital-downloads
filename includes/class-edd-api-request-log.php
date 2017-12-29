<?php
/**
 * API Request Log Object.
 *
 * @package     EDD
 * @subpackage  Classes/Logs
 * @copyright   Copyright (c) 2018, Pippin Williamson
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       3.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * EDD_API_Request_Log Class.
 *
 * @since 3.0
 */
class EDD_API_Request_Log {

	/**
	 * API request log ID.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    int
	 */
	protected $id;

	/**
	 * User ID of the user making the API request.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    int
	 */
	protected $user_id;

	/**
	 * API key.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string
	 */
	protected $api_key;

	/**
	 * API token.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string
	 */
	protected $token;

	/**
	 * API version.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string
	 */
	protected $version;

	/**
	 * API request.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string
	 */
	protected $request;

	/**
	 * IP address of the client making the API request.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string
	 */
	protected $ip;

	/**
	 * Speed of the API request.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    float
	 */
	protected $time;

	/**
	 * Date log was created.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    string
	 */
	protected $date_created;

	/**
	 * Database abstraction.
	 *
	 * @since  3.0
	 * @access protected
	 * @var    EDD_DB_Logs
	 */
	protected $db;

	/**
	 * Constructor.
	 *
	 * @since  3.0
	 * @access protected
	 *
	 * @param int $log_id Log ID.
	 */
	public function __construct( $log_id ) {
		$log = $this->db->get( $log_id );

		if ( $log ) {
			foreach ( get_object_vars( $log ) as $key => $value ) {
				$this->{$key} = $value;
			}
		}
	}

	/**
	 * Magic __get method to dispatch a call to retrieve a protected property.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param mixed $key
	 * @return mixed
	 */
	public function __get( $key ) {
		$key = sanitize_key( $key );

		if ( method_exists( $this, 'get_' . $key ) ) {
			return call_user_func( array( $this, 'get_' . $key ) );
		} elseif ( property_exists( $this, $key ) ) {
			return apply_filters( 'edd_api_request_log_' . $key, $this->{$key}, $this->id );
		}
	}

	/**
	 * Magic __set method to dispatch a call to update a protected property.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $key   Property name.
	 * @param mixed  $value Property value.
	 *
	 * @return mixed False if property doesn't exist, or returns the value from the dispatched method.
	 */
	public function __set( $key, $value ) {
		$key = sanitize_key( $key );

		// Only real properties can be saved.
		$keys = array_keys( get_class_vars( get_called_class() ) );

		if ( ! in_array( $key, $keys ) ) {
			return false;
		}

		// Dispatch to setter method if value needs to be sanitized
		if ( method_exists( $this, 'set_' . $key ) ) {
			return call_user_func( array( $this, 'set_' . $key ), $key, $value );
		} else {
			$this->{$key} = $value;
		}
	}

	/**
	 * Magic __isset method to allow empty checks on protected elements
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param string $key The attribute to get
	 * @return boolean If the item is set or not
	 */
	public function __isset( $key ) {
		if ( property_exists( $this, $key ) ) {
			return false === empty( $this->{$key} );
		} else {
			return null;
		}
	}

	/**
	 * Converts the instance of the EDD_Discount object into an array for special cases.
	 *
	 * @since 2.7
	 * @access public
	 *
	 * @return array EDD_Discount object as an array.
	 */
	public function array_convert() {
		return get_object_vars( $this );
	}

	/**
	 * Create a new log.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param array $args {
	 *      Log attributed.
	 * }
	 *
	 * @return int Newly created log ID.
	 */
	public function create( $args = array() ) {
		/**
		 * Filters the arguments before being inserted into the database.
		 *
		 * @since 3.0
		 *
		 * @param array $args Discount args.
		 */
		$args = apply_filters( 'edd_insert_api_request_log', $args );

		$args = $this->sanitize_columns( $args );

		/**
		 * Fires before a log has been inserted into the database.
		 *
		 * @since 3.0
		 *
		 * @param array $args Discount args.
		 */
		do_action( 'edd_pre_insert_api_request_log', $args );

		$id = $this->db->insert( $args );

		if ( $id ) {
			$this->id = $id;

			foreach ( $args as $key => $value ) {
				$this->{$key} = $value;
			}
		}

		/**
		 * Fires after a log has been inserted into the database.
		 *
		 * @since 3.0
		 *
		 * @param array $args Log args.
		 * @param int   $id   Log ID.
		 */
		do_action( 'edd_post_insert_api_request_log', $args, $this->id );
	}

	/**
	 * Update an existing log.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @param array $args {
	 *      Log attributes.
	 * }
	 *
	 * * @return bool True on success, false otherwise.
	 */
	public function update( $args = array() ) {
		return $this->db->update( $this->id, $args );
	}

	/**
	 * Delete log.
	 *
	 * @since 3.0
	 * @access public
	 *
	 * @return bool True if deleted, false otherwise.
	 */
	public function delete() {
		return $this->db->delete( $this->id );
	}

	/**
	 * Sanitize the data for update/create.
	 *
	 * @access public
	 * @since 3.0
	 *
	 * @param array $data The data to sanitize.
	 *
	 * @return array $data The sanitized data, based off column defaults.
	 */
	private function sanitize_columns( $data ) {
		$columns        = $this->db->get_columns();
		$default_values = $this->db->get_column_defaults();

		foreach ( $columns as $key => $type ) {
			// Only sanitize data that we were provided
			if ( ! array_key_exists( $key, $data ) ) {
				continue;
			}

			switch ( $type ) {
				case '%s':
					$data[ $key ] = sanitize_text_field( $data[ $key ] );
					break;

				case '%d':
					if ( ! is_numeric( $data[ $key ] ) || absint( $data[ $key ] ) !== (int) $data[ $key ] ) {
						$data[ $key ] = $default_values[ $key ];
					} else {
						$data[ $key ] = absint( $data[ $key ] );
					}
					break;

				case '%f':
					$value = floatval( $data[ $key ] );

					if ( ! is_float( $value ) ) {
						$data[ $key ] = $default_values[ $key ];
					} else {
						$data[ $key ] = $value;
					}
					break;

				default:
					$data[ $key ] = sanitize_text_field( $data[ $key ] );
					break;
			}
		}

		return $data;
	}
}
