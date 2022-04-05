<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class BT_EDD_Control
{

    /**
     * Get things started
     *
     * @since 1.0.0
     *
     * @access public
     * @return void
     */
    public function __construct() {
        add_action('edd_complete_purchase', array($this, 'complete_purchase'), 10);
        add_action('edd_post_refund_payment', array($this, 'refund_complete'), 10);
        add_action('wp_enqueue_scripts', array($this, 'apply_tags_on_view'));
    }

	/**
	 * Triggered when an order is completed. Updates contact record (or creates it) and applies tags
	 *
	 * @param $payment_id
	 * @param bool $doing_async
	 * @param bool $force
	 *
	 * @return void|bool
	 * @since 1.0.0
	 * @access public
	 */
    public function complete_purchase($payment_id, $doing_async = false, $force = false) {

        $payment = new EDD_Payment($payment_id);
        // Prevents the API calls being sent multiple times for the same order
        $bt_edd_complete = $payment->get_meta('bt_edd_complete', true);

        if (!empty($bt_edd_complete) && $force == false) {
            return true;
        }

        // Get user info
        $payment_meta = $payment->get_meta();
        $user_meta = array(
            'user_email' => $payment_meta['email'],
            'first_name' => $payment_meta['user_info']['first_name'],
            'last_name' => $payment_meta['user_info']['last_name'],
        );
        // Address fields
        if (!empty($payment_meta['user_info']['address'])) {
            $user_meta['billing_address_1'] = $payment_meta['user_info']['address']['line1'];
            $user_meta['billing_address_2'] = $payment_meta['user_info']['address']['line2'];
            $user_meta['billing_city'] = $payment_meta['user_info']['address']['city'];
            $user_meta['billing_state'] = $payment_meta['user_info']['address']['state'];
            $user_meta['billing_country'] = $payment_meta['user_info']['address']['country'];
            $user_meta['billing_postcode'] = $payment_meta['user_info']['address']['zip'];
        }
        // See if the user already exists locally
        $user_id = $payment->user_id;
        // Make sure user exists
        $user = get_userdata($user_id);

        if ($user === false) {
            $user_id = 0;
        }

        if ((int) $user_id < 1) {
            // Guest checkouts
            $contact_id = bt_edd_mailjet()->api->get_contact_id($user_meta['user_email']);
            if (empty($contact_id)) {

                bt_edd_mailjet_log(
					'info', 0, sprintf(__('New EDD guest checkout. Order <a href="%s">#%d</a> ', 'bt-edd-mailjet' ), admin_url( '/edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $payment_id ), $payment_id ) , array(
						'meta_array' => $user_meta,
						'source'     => 'edd',
					)
				);

                // Create contact and add note
                $user_meta = bt_edd_mailjet_map_meta_fields($user_meta);
                $contact_id = bt_edd_mailjet()->api->add_contact($user_meta);
                if (is_wp_error($contact_id)) {
                    bt_edd_mailjet_log( 'error', 0, sprintf(__('Error creating contact in Mailjet: %s', 'bt-edd-mailjet' ), $contact_id->get_error_message() ) );
                    return false;
                } else {
                    //$payment->add_note('EDD Mailjet contact ID ' . $contact_id . ' created via guest checkout.');
                }
            } else {
                // Existing contact
                bt_edd_mailjet_log(
					'info', 0, sprintf(__('New EDD guest checkout. Order <a href="%s">#%d</a> ', 'bt-edd-mailjet' ), admin_url( '/edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $payment_id ), $payment_id ) , array(
						'meta_array' => $user_meta,
						'source'     => 'edd',
					)
				);
                $user_meta = bt_edd_mailjet_map_meta_fields($user_meta);
                bt_edd_mailjet()->api->update_contact($contact_id, $user_meta);
            }
        } else {
            // Registered user checkouts
            bt_edd_mailjet_log(
                'info', $user_id, sprintf(__('New EDD order <a href="%s">#%d</a> ', 'bt-edd-mailjet' ), admin_url( '/edit.php?post_type=download&page=edd-payment-history&view=view-order-details&id=' . $payment_id ), $payment_id ) , array(
                    'source'     => 'edd',
                )
            );

            $contact_id = bt_edd_mailjet_get_contact_id($user_id);
            $user = new BT_EDD_User( $user_id );
            $user_meta = $user->get_user_meta();
            // Contact needs to be created
            if ($contact_id == false) {
                // Create contact and add note
                $user_meta = bt_edd_mailjet_map_meta_fields($user_meta);
                $contact_id = bt_edd_mailjet()->api->add_contact($user_meta);
                if (is_wp_error($contact_id)) {
                    bt_edd_mailjet_log( 'error', $user_id, 'Error creating contact in mailjet: ' . $contact_id->get_error_message() );
                    return false;
                } else {
                    //$payment->add_note('EDD Mailjet contact ID ' . $contact_id . ' created.');
                }
            } else {
                // If contact is found for user, update their info
                bt_edd_mailjet_push_user_meta( $user_id, $user_meta );
            }
        }

        // Store the contact ID for future operations
        $payment->update_meta(bt_edd_mailjet()->settings->slug . 'contact_id', $contact_id);

        // Apply tags
        $apply_tags = array();
        foreach ($payment_meta['cart_details'] as $item) {
            $apply_tags = get_post_meta($item['id'], 'bt_edd_purchase_tag_list', true);
            if (empty($apply_tags)) {
                continue;
            }
        }
        $apply_tags = apply_filters('bt_edd_edd_apply_tags_checkout', $apply_tags, $payment);

        // Guest checkout
        if ((int) $user_id < 1) {
            // Logging
			bt_edd_mailjet_log(
				'info', 0, 'EDD guest checkout applying tags: ', array(
					'tag_array' => $apply_tags,
					'source'    => 'edd',
				)
			);
            bt_edd_mailjet()->api->apply_tags($apply_tags, $contact_id);
        } else {
            bt_edd_mailjet_apply_tags($apply_tags, $user_id);
        }

        // Denotes that the EDD actions have already run for this payment
        $payment->update_meta('bt_edd_complete', true);
        // Run payment complete action
        do_action('bt_edd_edd_payment_complete', $payment_id, $contact_id);
    }

	/**
	 * Triggered when an order is refunded. Updates contact record and removes original purchase tags / applies refund tags if applicable
	 *
	 * @param $payment
	 *
	 * @return void
	 * @since 1.0.0
	 * @access public
	 */
    public function refund_complete($payment) {
        $remove_tags = array();
        $apply_tags_refunded = array();
        $payment_meta = $payment->get_meta();
        foreach ($payment_meta['cart_details'] as $item) {
            $remove_tags = get_post_meta($item['id'], 'bt_edd_refund_tag_list', true);
            if (empty($remove_tags)) {
                continue;
            }
        }
        $user_id = $payment->user_id;
        // Guest checkout
        if ((int) $user_id < 1) {
            $contact_id = $payment->get_meta(bt_edd_mailjet()->settings->slug . 'contact_id', true);
            if (empty($contact_id)) {
                $user_email = $payment_meta['email'];
                $contact_id = bt_edd_mailjet()->api->get_contact_id($user_email);
            }

            if (!is_wp_error($contact_id) && !empty($contact_id)) {
                if (!empty($remove_tags)) {
                    bt_edd_mailjet()->api->remove_tags($remove_tags, $contact_id);
                }
                if (!empty($apply_tags_refunded)) {
                    bt_edd_mailjet()->api->apply_tags($apply_tags_refunded, $contact_id);
                }
            }
        } else {
            
            if (!empty($remove_tags)) {
                bt_edd_mailjet_remove_tags($remove_tags, $user_id);
            }
            if (!empty($apply_tags_refunded)) {
                bt_edd_mailjet_apply_tags($apply_tags_refunded, $user_id);
            }
        }

    }

    /**
     * Applies tags when a page is viewed
     *
     * @since 1.0.0
     * @access public
     * @return void
     */
    public function apply_tags_on_view() {

        if (is_admin() || !is_singular() || !bt_edd_is_user_logged_in()) {
            return;
        }
        global $post;

        if (false == apply_filters('bt_edd_apply_tags_on_view', true, $post->ID)) {
            return;
        }
        $apply_list = get_post_meta($post->ID, 'bt_edd_apply_list_download', true);
        $remove_list = get_post_meta($post->ID, 'bt_edd_remove_list_download', true);
        $delay = get_post_meta($post->ID, 'bt_edd_delay_before_lists', true);
        if (!empty($apply_list) || !empty($remove_list)) {
            if (empty($delay)) {
                if (!empty($apply_list)) {
                    bt_edd_mailjet_apply_tags($apply_list);
                }
                if (!empty($remove_list)) {
                    bt_edd_mailjet_remove_tags($remove_list);
                }
            } else {
                wp_enqueue_script('bt-edd-apply-tags', BT_EDD_MAILJET_PLUGIN_ASSETS . '/js/bt-edd-apply-tags.js', array('jquery'), BT_EDD_MAILJET_VERSION, true);
                if (!isset($apply_list)) {
                    $apply_list = null;
                }
                if (!isset($remove_list)) {
                    $remove_list = null;
                }
                $localize_data = array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'tags' => $apply_list,
                    'remove' => $remove_list,
                    'delay' => $delay,
                );
                wp_localize_script('bt-edd-apply-tags', 'bt_edd_ajax', $localize_data);
            }
        }

    }

}

new BT_EDD_Control();
