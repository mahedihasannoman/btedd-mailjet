<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BT_EDD_User {

	protected $user_id;

	/**
	 * BT_EDD_User constructor.
	 *
	 * @param int $user_id
	 *
	 * @since 1.0.0
	 */
	public function __construct( $user_id = 0 ) {
		if( $user_id != 0 ){
			$this->user_id = $user_id;
		}
    }

	/**
	 * Used by create user to map post data for PHP versions less than 5.3
	 *
	 * @param $a
	 *
	 * @return mixed
	 * @since 1.0.0
	 * @access public
	 */
	public function map_user_meta( $a ) {
		return maybe_unserialize( $a[0] );
    }

    /**
	 * Get all the available metadata from the database for a user
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array
	 */
	public function get_user_meta() {
		$user_meta = array_map( array( $this, 'map_user_meta' ), get_user_meta( $this->user_id ) );
		$userdata  = get_userdata( $this->user_id );
		$user_meta['user_id']         = $this->user_id;
		$user_meta['user_login']      = $userdata->user_login;
		$user_meta['user_email']      = $userdata->user_email;
		$user_meta['user_registered'] = $userdata->user_registered;
		$user_meta['user_nicename']   = $userdata->user_nicename;
		$user_meta['user_url']        = $userdata->user_url;
		$user_meta['display_name']    = $userdata->display_name;
		if ( is_array( $userdata->roles ) ) {
			$user_meta['role'] = $userdata->roles[0];
		}
		return $user_meta;
    }
    
}
