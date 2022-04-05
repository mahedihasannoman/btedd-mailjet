<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BT_EDD_Actions {

    /**
	 * BT_EDD_User_Actions constructor.
	 * @since 1.0.0
	 */
	public function __construct() {
		// Main user hooks
		add_action( 'user_register', array( $this, 'user_register' ), 20 ); // 20 so usermeta added by other plugins is saved
		add_action( 'profile_update', array( $this, 'profile_update' ), 10, 2 );
		add_action( 'wp_ajax_bt_edd_get_customers', array( $this, 'get_customers' ) );
		add_action( 'wp_ajax_bt_edd_sync_customers', array( $this, 'sync_customers' ) );
		add_filter( 'cron_schedules', array( $this, 'custom_cron_schedule' ) );
		//Schedule an action if it's not already scheduled
		if ( ! wp_next_scheduled( 'bt_edd_mailjet_cron_hook' ) ) {
			wp_schedule_event( time(), 'every_six_hours', 'bt_edd_mailjet_cron_hook' );
		}
		///Hook into that action that'll fire every six hours
		add_action( 'bt_edd_mailjet_cron_hook', array( $this, 'cron_function' ) );
		
    }

    /**
	 * Gets the current user ID
	 *
	 * @since 1.0.0
	 * @access public
	 * @return int User ID
	 */
	public function get_current_user_id() {
		if ( is_user_logged_in() ) {
			return get_current_user_id();
		}
		return 0;
    }

	/**
	 * Triggered when a new user is registered. Creates the user in the CRM and stores contact ID
	 *
	 * @param $user_id
	 * @param bool $post_data
	 * @param bool $force
	 *
	 * @return bool|int
	 * @since 1.0.0
	 * @access public
	 *
	 */
	public function user_register( $user_id, $post_data = false, $force = false ) {

        $user = new BT_EDD_User($user_id);
		$post_data = $user->get_user_meta();
		// Allow outside modification of this data
		$post_data = apply_filters( 'bt_edd_user_register', $post_data, $user_id );
		// Allows for cancelling of registration via filter
		if ( $post_data == null || empty( $post_data['user_email'] ) ) {
			bt_edd_mailjet_log( 'notice', $user_id, __('User registration not synced to Mailjet because email address wasn\'t detected in the submitted data.', 'bt-edd-mailjet') );
			return false;
		}
		// Check if contact already exists in Mailjet
		$contact_id = bt_edd_mailjet_get_contact_id( $user_id, true );

		if ( edd_get_option( 'bt_edd_mailjet_create_contacts' ) != true && $force == false && $contact_id == false ) {
			bt_edd_mailjet_log( 'notice', $user_id, __('User registration not synced to Mailjet because "Create Contacts" is disabled in the EDD Mailjet settings. You will not be able to apply tags to this user.', 'bt-edd-mailjet' ) );
			return false;

		}

		if ( $contact_id == false ) {
			$post_data = bt_edd_mailjet_map_meta_fields($post_data);
			bt_edd_mailjet_log( 'info', $user_id, __( 'New user registration. Adding contact to mailjet:', 'bt-edd-mailjet' ), array( 'meta_array' => $post_data ) );
			$contact_id = bt_edd_mailjet()->api->add_contact( $post_data );
			// Error logging
			if ( is_wp_error( $contact_id ) ) {
				bt_edd_mailjet_log( $contact_id->get_error_code(), $user_id, sprintf( __( 'Error adding contact to mailjet: %s', 'bt-edd-mailjet' ),
				$contact_id->get_error_message() ) );
				return false;
			}
			update_user_meta( $user_id, bt_edd_mailjet()->settings->slug . 'contact_id', $contact_id );
		} else {

			bt_edd_mailjet_log( 'info', $user_id, sprintf(__('New user registration. Updating contact ID %d in mailjet: ', 'bt-edd-mailjet' ), $contact_id ), array( 'meta_array' => $post_data ) );
            // If contact exists, update data and pull down anything new from the CRM
            $post_data = bt_edd_mailjet_map_meta_fields($post_data);
			$result = bt_edd_mailjet()->api->update_contact( $contact_id, $post_data );
			if ( is_wp_error( $result ) ) {
				bt_edd_mailjet_log( $result->get_error_code(), $user_id, sprintf(__('Error updating contact: %s', 'bt-edd-mailjet' ), $result->get_error_message() ) );
				return false;
			}
			bt_edd_mailjet_get_tags( $user_id, true, false );
		}
		// Assign any tags specified in the EDD settings page
		$assign_tags = edd_get_option( 'bt_edd_mailjet_contacts_list' );
		if ( ! empty( $assign_tags ) ) {
			bt_edd_mailjet_apply_tags( $assign_tags, $user_id );
		}
		do_action( 'bt_edd_user_created', $user_id, $contact_id, $post_data );
		return $contact_id;
    }
    
    /**
	 * Triggered when profile updated
	 *
	 * @since 1.0.0
	 * @access public
	 * @return void
	 */
	public function profile_update( $user_id, $old_user_data ) {
		// This doesn't need to run twice on a page load
		remove_action( 'profile_update', array( $this, 'profile_update' ), 10 );
		bt_edd_mailjet_push_user_meta( $user_id );
    }
    
    /**
	 * contact list by rule ajax callback
	 *
	 * @since 1.0.0
	 * 
	 * @return void
	 */
	public function get_customers() {
		
		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'bt-edd-contact-sync-nonce' ) ) {
			$response['message'] = __( 'Nonce is not valid', 'bt-edd-mailjet' );
			wp_send_json($response);
			die(0);
		}
		$list = sanitize_text_field( $_POST['list'] );
		$data = array(
			'total_customer' 	=> bt_edd_mailjet_customer_count(),
			'offset'			=> 0,
			'per_request'		=> 1,
			'list'				=> $list,
		);
		set_transient( 'bt_edd_customer_sync', $data, 5 * HOUR_IN_SECONDS  );
		wp_send_json($data);
		die(0);
    }
    
    /**
	 * Sync customer
	 *
	 * @since 1.0.0
	 * 
	 * @return bool
	 */
	public function sync_customers() {

		if ( ! wp_verify_nonce( $_REQUEST['nonce'], 'bt-edd-contact-sync-nonce' ) ) {
			$response['message'] = __( 'Nonce is not valid', 'bt-edd-mailjet' );
			wp_send_json($response);
			die(0);
		}
		global $wpdb;
		$data = get_transient( 'bt_edd_customer_sync' );
		if( empty( $data ) ){
			return false;
		}
		$start	= $data['offset'] * $data['per_request'];
		if( $start < $data['total_customer'] ){
			$end	= ( $data['offset'] + 1 ) * $data['per_request'];
			$table_name = "{$wpdb->prefix}edd_customers";
			$query = $wpdb->prepare( "SELECT id, name, email FROM `$table_name` LIMIT %d, %d", $start, $end );
			$customers = $wpdb->get_results( $query );
			if( empty( $customers ) ) {
				return false;
			}
			foreach( $customers as $customer ) {
	
				$edd_customer = new EDD_Customer( $customer->id );
				if( $edd_customer->user_id > 0 ){
					bt_edd_mailjet_apply_tags( [$data['list']], $edd_customer->user_id );
				}else{
					bt_edd_mailjet_apply_tags_customer( $edd_customer, [$data['list']] );
				}
			}
			$data['offset'] = $data['offset'] + 1;
			set_transient( 'bt_edd_customer_sync', $data, 5 * HOUR_IN_SECONDS  );
			
		}else{
			delete_transient( 'bt_edd_customer_sync' );
		}

		wp_send_json( $data );
		die(0);

	}

	/**
	 * Custom cron schedule
	 *
	 * @since 1.0.0
	 * 
	 * @param array $schedules
	 * 
	 * @return array $schedules
	 */
	public function custom_cron_schedule( $schedules ) {
		$schedules['every_six_hours'] = array(
			'interval' => 21600, // Every 6 hours
			'display'  => __( 'Every 6 hours', 'bt-edd-mailjet' ),
		);
		return $schedules;
	}

	/**
	 * Cron callback function
	 * 
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function cron_function() {
		if( bt_edd_mailjet()->api->connect() ){
			bt_edd_mailjet()->api->get_all_tags();
			bt_edd_mailjet()->api->get_fields();
		}
	}
}

new BT_EDD_Actions();