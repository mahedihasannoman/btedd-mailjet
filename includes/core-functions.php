<?php
defined('ABSPATH') || exit();

/**
 * retrieving mailjet tags from api
 * 
 * @since 1.0.0
 * @return array
 */
function bt_edd_get_mailjet_contact_list() {
	$options      = array();
	$contact_list = bt_edd_mailjet()->api->get_all_tags();
	if (!is_wp_error($contact_list)) {
		$options = $contact_list;
	}
	return $options;
}

/**
 * Getting all active user roles as option
 * @since 1.0.0
 * @return html
 */
function bt_edd_get_all_user_roles_as_option() {
    global $wp_roles;
    $all_roles = $wp_roles->roles;
    $editable_roles = apply_filters( 'bt_edd_editable_roles', $all_roles );
	$html = '';
	foreach( $all_roles as $role => $data ){
		$html .= '<option value="' . esc_attr($role) . '">' . ( isset( $data['name'] ) ? esc_html($data['name']) : '' ) . '</option>';
	}
	return $html;
}

/**
 * Callback function for bt_contact_fields settings field
 *
 * @since 1.0.0
 * @param array $args
 * @return void
 */
function edd_bt_contact_fields_callback($args) {
	global $edd_options;

	$field_types = apply_filters('bt_edd_field_types', array(
		'text',
		'date',
		'multiselect',
		'capabilities',
		'checkbox',
		'state',
		'country',
		'int',
	));

	$wp_fields = bt_edd_get_default_wp_fields();
	$field_group = array();
	// Sets field types and labels for all built in fields
	foreach ($wp_fields as $key => $data) {
		if (!isset($data['group'])) {
			$data['group'] = 'wordpress';
		}
		$field_group[$key] = array(
			'label' => $data['label'],
			'type'  => $data['type'],
			'group'	=> $data['group']
		);
	}

	$field_group = apply_filters('bt_edd_field_group', $field_group);
	$mailjet_fields = bt_edd_mailjet()->settings->get('fields');
	$html = '<table class="wp-list-table widefat table table-hover bt_edd_mailjet_field_map">';
	$html .= '<thead>';
	$html .= '<tr>';
	$html .= '<th>' . __('Name', 'bt-edd-mailjet') . '</th>';
	$html .= '<th>' . __('Meta Field', 'bt-edd-mailjet') . '</th>';
	$html .= '<th>' . __('Type', 'bt-edd-mailjet') . '</th>';
	$html .= '<th>' . __('Mailjet Field', 'bt-edd-mailjet') . '</th>';
	$html .= '</tr>';
	$html .= '</thead>';
	$html .= '<tbody>';

	foreach ($field_group as $key => $value) {
		$html .= '<tr>';
		$html .= '<td> ' . esc_html($value['label']) . ' </td>';
		$html .= '<td> ' . esc_html($key) . ' </td>';
		//for wordpress fields
		$html .= '<td>';
		$html .= '<select class="bt_edd_select" name="edd_settings[' . $args['id'] . '][' . $key . '][type]" id="edd_settings[' . $args['id'] . '][' . $key . '][type]">';
		foreach ($field_types as $field_type) {
			$html .= '<option value="' . esc_attr($field_type) . '" ' . selected(isset($edd_options[$args['id']][$key]['type']) ? $edd_options[$args['id']][$key]['type'] : '', $field_type, false) . ' >  ' . esc_html($field_type) . ' </option>';
		}
		$html .= '</select>';
		$html .= '</td>';
		//for mailjet fields
		$html .= '<td>';
		$html .= '<select class="bt_edd_select" name="edd_settings[' . $args['id'] . '][' . $key . '][fields]" id="edd_settings[' . $args['id'] . '][' . $key . '][fields]" >';
		$html .= '<option value=""> ' . __('Select a Field', 'bt-edd-mailjet') . ' </option>';
		if( ! empty($mailjet_fields) ){
			foreach ($mailjet_fields as $fkey => $fvalue) {
				$html .= '<optgroup label="' . esc_attr($fkey) . '">';
				if( ! empty( $fvalue ) ){
					foreach ($fvalue as $mkey => $mvalue) {
						$html .= '<option value="' . esc_attr($mkey) . '" ' . selected(isset($edd_options[$args['id']][$key]['fields']) ? $edd_options[$args['id']][$key]['fields'] : '', $mkey, false) . ' >  ' . esc_html($mvalue) . ' </option>';
					}
				}
				$html .= '</optgroup>';
			}
		}
		$html .= '</select>';
		$html .= '</td>';
		$html .= '</tr>';
	}
	$html .= '</tr>';
	$html .= '</tbody>';
	$html .= '</table>';
	echo $html;
}

/**
 * Checks if user is logged in, with support for auto-logged-in users
 *
 * @since 1.0.0
 * @return bool Logged In
 */
if (!function_exists('bt_edd_is_user_logged_in')) {
	function bt_edd_is_user_logged_in()
	{
		return is_user_logged_in();
	}
}

/**
 * function for returning default WP fields
 *
 * @since 1.0.0
 * @return array
 */
function bt_edd_get_default_wp_fields() {

	$fields = array(
		'first_name'		=> array(
			'type'  => 'text',
			'label' => __('First Name', 'bt-edd-mailjet')
		),
		'last_name'			=> array(
			'type'  => 'text',
			'label' => __('Last Name', 'bt-edd-mailjet')
		),
		'user_email' 		=> array(
			'type'  => 'text',
			'label' => __('E-mail Address', 'bt-edd-mailjet')
		),
		'display_name'		=> array(
			'type'  => 'text',
			'label' => __('Profile Display Name', 'bt-edd-mailjet')
		),
		'nickname' 			=> array(
			'type'  => 'text',
			'label' => __('Nickname', 'bt-edd-mailjet')
		),
		'user_login'		=> array(
			'type'  => 'text',
			'label' => __('Username', 'bt-edd-mailjet')
		),
		'user_id'			=> array(
			'type'  => 'integer',
			'label' => __('User ID', 'bt-edd-mailjet')
		),
		'locale'			=> array(
			'type'  => 'text',
			'label' => __('Language', 'bt-edd-mailjet')
		),
		'role'				=> array(
			'type'  => 'text',
			'label' => __('User Role', 'bt-edd-mailjet')
		),
		'user_registered'	=> array(
			'type'  => 'date',
			'label' => __('User Registered', 'bt-edd-mailjet')
		),
		'description'		=> array(
			'type'  => 'textarea',
			'label' => __('Biography', 'bt-edd-mailjet'),
		),
		'facebook'			=> array(
			'type'  => 'text',
			'label' => __('Facebook Page', 'bt-edd-mailjet')
		),
		'twitter'			=> array(
			'type'  => 'text',
			'label' => __('Twitter', 'bt-edd-mailjet')
		),
		'google_plus'		=> array(
			'type'  => 'text',
			'label' => __('Google+', 'bt-edd-mailjet')
		),
		'user_url'			=> array(
			'type'  => 'text',
			'label' => __('Website (URL)', 'bt-edd-mailjet')
		),
		'job_title'			=> array(
			'type'  => 'text',
			'label' => __('Job Title', 'bt-edd-mailjet')
		)
	);
	return apply_filters('bt_edd_field_types', $fields);
}

/**
 * Removes an array of tags from a given user ID
 *
 * @param array tags
 * @param bool $user_id
 *
 * @return bool|void
 * @since 1.0.0
 *
 */
function bt_edd_mailjet_remove_tags( $tags, $user_id = false ) {

	if ( empty( $tags ) || ! is_array( $tags ) ) {
		return;
	}

	if ( false == $user_id ) {
		$user_id = get_current_user_id();
	}

	if( $user_id == 0 ){
		return false;
	}

	/**
	 * Triggers before tags are removed from the user
	 *
	 * @since 1.0.0
	 * 
	 * @param int   $user_id ID of the user being updated
	 * @param array $tags    Tags to be removed from the user
	 */
	do_action( 'bt_edd_remove_tags_start', $user_id, $tags );

	/**
	 * Filters the tags to be removed from the user
	 *
	 * @param array $tags    Tags to be removed from the user
	 * @param int   $user_id ID of the user being updated
	 */
	$tags = apply_filters( 'bt_edd_remove_tags', $tags, $user_id );
	$contact_id = bt_edd_mailjet_get_contact_id( $user_id );
	// If no contact ID, don't try applying tags
	if ( false == $contact_id ) {
		return false;
	}
	$user_tags = bt_edd_mailjet_get_tags( $user_id );
	$tags = array_intersect( (array) $tags, $user_tags );
	// Maybe quit early if user doesn't have the tag anyway
	if ( empty( $tags ) ) {
		return true;
	}
	$result = bt_edd_mailjet()->api->remove_tags( $tags, $contact_id );
	if ( is_wp_error( $result ) ) {
		return false;
	}
	// Save to the database
	$user_tags = array_unique( array_diff( $user_tags, $tags ) );
	update_user_meta( $user_id, bt_edd_mailjet()->settings->slug . 'tags', $user_tags );

	/**
	 * Triggers after tags are removed from the user, contains just the tags that were removed
	 *
	 * @param int   $user_id ID of the user that was updated
	 * @param array $tags    Tags that were removed from the user
	 */
	do_action( 'bt_edd_tags_removed', $user_id, $tags );

	/**
	 * Triggers after tags are updated for a user, contains all of the user's tags
	 *
	 * @param int   $user_id   ID of the user that was updated
	 * @param array $user_tags The user's CRM tags
	 */
	do_action( 'bt_edd_tags_modified', $user_id, $user_tags );
	return true;
}

/**
 * Gets contact ID from user ID
 *
 * @param bool $user_id
 * @param bool $force_update
 *
 * @return int|bool
 * @since 1.0.0
 *
 */
function bt_edd_mailjet_get_contact_id( $user_id = false, $force_update = false ) {

	if ( false == $user_id ) {
		$user_id = get_current_user_id();
	}

	if ( $user_id == 0 ) {
		return false;
	}

	$user = get_user_by( 'id', $user_id );
	if ( empty( $user ) ) {
		$user             = new stdClass();
		$user->user_email = get_user_meta( $user_id, 'user_email', true );
	}

	$contact_id = get_user_meta( $user_id, bt_edd_mailjet()->settings->slug . 'contact_id', true );

	if( ! empty( $contact_id ) ){
		return apply_filters( 'bt_edd_contact_id', $contact_id, $user->user_email );
	}

	$contact_id = bt_edd_mailjet()->api->get_contact_id( $user->user_email );

	if ( is_wp_error( $contact_id ) ) {
		bt_edd_mailjet_log( $contact_id->get_error_code(), $user_id, sprintf( __( 'Error getting contact ID for <strong>%s</strong>: %s', 'bt-edd-mailjet' ),
		$user->user_email, $contact_id->get_error_message() ) );
		return false;
	}

	$contact_id = apply_filters( 'bt_edd_contact_id', $contact_id, $user_id );
	update_user_meta( $user_id, bt_edd_mailjet()->settings->slug . 'contact_id', $contact_id );
	do_action( 'bt_edd_got_contact_id', $user_id, $contact_id );
	return $contact_id;
}

/**
 * Gets all tags currently applied to the user
 *
 * @param bool $user_id
 * @param bool $force_update
 * @param bool $lookup_cid
 *
 * @return array|WP_Error
 * @since 1.0.0
 *
 */
function bt_edd_mailjet_get_tags( $user_id = false, $force_update = false, $lookup_cid = true ) {
	if ( false == $user_id ) {
		$user_id = get_current_user_id();
	}

	if( $user_id == 0 ){
		return array();
	}

	do_action( 'bt_edd_get_tags_start', $user_id );
	$user_tags = get_user_meta( $user_id, bt_edd_mailjet()->settings->slug . 'tags', true );
	if ( is_array( $user_tags ) && $force_update == false ) {
		return apply_filters( 'bt_edd_user_tags', $user_tags, $user_id );
	}
	// If no tags
	if ( empty( $user_tags ) && $force_update == false ) {
		return apply_filters( 'bt_edd_user_tags', array(), $user_id );
	}
	if ( empty( $user_tags ) ) {
		$user_tags = array();
	}
	// Don't get the CID again if the request came from a webhook
	if ( $lookup_cid == false ) {
		$force_update = false;
	}
	$contact_id = bt_edd_mailjet_get_contact_id( $user_id, $force_update );
	// If contact doesn't exist
	if ( $contact_id == false ) {
		update_user_meta( $user_id, bt_edd_mailjet()->settings->slug . 'tags', false );
		return array();
	}
	$tags = bt_edd_mailjet()->api->get_tags( $contact_id );
	if ( is_wp_error( $tags ) ) {
		bt_edd_mailjet_log( $tags->get_error_code(), $user_id, sprintf( __( 'Failed loading tags: %s', 'bt-edd-mailjet' ),
		$tags->get_error_message() ) );
		return $user_tags;
	} elseif ( $tags == $user_tags ) {
		// Doing the action here so that automated enrollments are triggered
		do_action( 'bt_edd_tags_modified', $user_id, $user_tags );
		// If nothing changed
		return apply_filters( 'bt_edd_user_tags', $user_tags, $user_id );
	}
	// Check if tags were added
	$tags_applied = array_diff( $tags, $user_tags );
	// Check if tags were removed
	$tags_removed = array_diff( $user_tags, $tags );
	$user_tags = (array) $tags;
	bt_edd_mailjet_log( 'info', $user_id, __( 'Loaded tag(s)', 'bt-edd-mailjet' ) . ': ', array( 'tag_array' => $user_tags ) );
	// Check and see if new tags have been pulled, and if so, resync the available tags list
	if ( is_admin() ) {
		$sync_needed    = false;
		$available_tags = bt_edd_mailjet()->settings->get( 'available_tags' );
		foreach ( (array) $user_tags as $tag ) {
			if ( ! isset( $available_tags[ $tag ] ) ) {
				$sync_needed = true;
			}
		}
		if ( $sync_needed == true ) {
			bt_edd_mailjet()->api->get_all_tags();
		}
	}
	update_user_meta( $user_id, bt_edd_mailjet()->settings->slug . 'tags', $user_tags );
	if ( ! empty( $tags_applied ) ) {
		/**
		 * Triggers after tags are loaded for the user, contains just the new tags that were applied
		 *
		 * @param int   $user_id      ID of the user that was updated
		 * @param array $tags_applied Tags that were applied to the user
		 */
		do_action( 'bt_edd_tags_applied', $user_id, $tags_applied );
	}
	if ( ! empty( $tags_removed ) ) {
		/**
		 * Triggers after tags are loaded for the user, contains just the tags that no longer are present
		 *
		 * @param int   $user_id      ID of the user that was updated
		 * @param array $tags_removed Tags that were removed from the user
		 */
		do_action( 'bt_edd_tags_removed', $user_id, $tags_removed );
	}
	/**
	 * Triggers after tags are loaded for a user, contains all of the user's tags
	 *
	 * @param int   $user_id   ID of the user that was updated
	 * @param array $user_tags The user's CRM tags
	 */
	do_action( 'bt_edd_tags_modified', $user_id, $user_tags );
	return apply_filters( 'bt_edd_user_tags', $user_tags, $user_id );
}

/**
 * Sends updated user meta to Mailjet
 *
 * @param int
 * @param bool $user_meta
 *
 * @return bool|void
 * @since 1.0.0
 *
 */
function bt_edd_mailjet_push_user_meta( $user_id, $user_meta = false ) {
	do_action( 'bt_edd_push_user_meta_start', $user_id, $user_meta );
	// If nothing's been supplied, get the latest from the DB
	if ( false === $user_meta ) {
		$user = new BT_EDD_User($user_id);
		$user_meta = $user->get_user_meta();
	}
	$user_meta = apply_filters( 'bt_edd_user_update', $user_meta, $user_id );
	$contact_id = bt_edd_mailjet_get_contact_id( $user_id );
	if ( empty( $user_meta ) || false == $contact_id ) {
		return;
	}
	bt_edd_mailjet_log( 'info', $user_id, 'Pushing meta data to mailjet: ', array( 'meta_array' => $user_meta ) );

	$result = bt_edd_mailjet()->api->update_contact( $contact_id, $user_meta );
	if ( is_wp_error( $result ) ) {

		bt_edd_mailjet_log( $result->get_error_code(), $user_id, sprintf(__('Error while updating meta data: %s', 'bt-edd-mailjet' ), $result->get_error_message() ) );
		return false;

	} elseif ( false == $result ) {
		// If nothing was updated
		return false;
	}
	do_action( 'bt_edd_pushed_user_meta', $user_id, $contact_id, $user_meta );
	return true;
}

/**
 * Applies an array of tags to a given user ID
 *
 * @param array tags
 * @param bool $user_id
 *
 * @return bool|void
 * @since 1.0.0
 *
 */
function bt_edd_mailjet_apply_tags( $tags, $user_id = false ) {

	if ( empty( $tags ) || ! is_array( $tags ) ) {
		return;
	}

	if ( false == $user_id ) {
		$user_id = get_current_user_id();
	}

	if( $user_id == 0 ){
		return false;
	}

	/**
	 * Triggers before tags are applied to the user
	 *
	 * @param int   $user_id ID of the user being updated
	 * @param array $tags    Tags to be applied to the user
	 */
	do_action( 'bt_edd_apply_tags_start', $user_id, $tags );

	/**
	 * Filters the tags to be applied to the user
	 *
	 * @since 1.0.0
	 * 
	 * @param array $tags    Tags to be applied to the user
	 * @param int   $user_id ID of the user being updated
	 */
	$tags = apply_filters( 'bt_edd_apply_tags', $tags, $user_id );
	$contact_id = bt_edd_mailjet_get_contact_id( $user_id );

	// If no contact ID, don't try applying tags
	if ( false == $contact_id ) {
		bt_edd_mailjet_log( 'notice', $user_id, __( 'No contact ID for user. Failed to apply tag(s)', 'bt-edd-mailjet' ) . ': ', array( 'tag_array' => $tags ) );
		return false;
	}
	$user_tags = bt_edd_mailjet_get_tags( $user_id );
	// Maybe quit early if user already has the tag
	$diff = array_diff( (array) $tags, $user_tags );

	if ( empty( $diff ) ) {
		return true;
	}

	// If we're only applying tags the user doesn't have already
	$tags = $diff;

	bt_edd_mailjet_log( 'info', $user_id, __( 'Applying tag(s)', 'bt-edd-mailjet' ) . ': ', array( 'tag_array' => $diff ) );
	
	$result = bt_edd_mailjet()->api->apply_tags( $tags, $contact_id );
	if ( is_wp_error( $result ) ) {
		bt_edd_mailjet_log( $result->get_error_code(), $user_id, sprintf(__('Error while applying tags: %s', 'bt-edd-mailjet' ), $result->get_error_message() ) );
		return false;
	}
	// Save to the database
	$user_tags = array_unique( array_merge( $user_tags, $tags ) );
	update_user_meta( $user_id, bt_edd_mailjet()->settings->slug . 'tags', $user_tags );

	/**
	 * Triggers after tags are applied to the user, contains just the tags that were applied
	 *
	 * @param int   $user_id ID of the user that was updated
	 * @param array $tags    Tags that were applied to the user
	 */
	do_action( 'bt_edd_tags_applied', $user_id, $tags );

	/**
	 * Triggers after tags are updated for a user, contains all of the user's tags
	 *
	 * @param int   $user_id   ID of the user that was updated
	 * @param array $user_tags The user's CRM tags
	 */
	do_action( 'bt_edd_tags_modified', $user_id, $user_tags );
	return true;
}

/**
 * Maps local fields to Mailjet field names
 *
 * @since 1.0.0
 * 
 * @param array user_meta
 * 
 * @return array|bool
 */
function bt_edd_mailjet_map_meta_fields( $user_data ) {

	if( ! is_array( $user_data ) ) {
		return false;
	}

	$update_data = array();
	$map = edd_get_option('bt_contact_fields_map');
	foreach ( (array) bt_edd_mailjet()->settings->get( 'fields' ) as $field => $field_data ) {
		foreach($field_data as $k => $v){
			foreach($map as $mfield=>$details){
				if( $k == $details['fields'] ){
					$update_data[$k] = isset( $user_data[$mfield] ) ? $user_data[$mfield] : '';
					break;
				}
			}
		}
	}

	if( ! isset( $update_data['Email'] ) ) {
		$update_data['Email'] = $user_data['user_email'];
	}

	if( ! isset( $update_data['Name'] ) ) {
		$update_data['Name'] = isset( $user_data['nickname'] ) ? $user_data['nickname']: '';
	}

	$update_data = apply_filters( 'bt_edd_map_meta_fields', $update_data );
	return $update_data;
}

/**
 * Count all existing customer
 * 
 * @since 1.0.0
 *
 * @return int
 */
function bt_edd_mailjet_customer_count() {
	return edd_count_total_customers();
}

/**
 * apply tags to customer
 *
 * @param $customer
 * @param $tags
 *
 * @return void|bool
 * @since 1.0.0
 *
 */
function bt_edd_mailjet_apply_tags_customer($customer, $tags) {
	if ( empty( $tags ) || ! is_array( $tags ) ) {
		return;
	}

	$user_id = get_current_user_id();
	
	$user_meta = array(
		'user_email' => $customer->email,
		'first_name' => $customer->name,
		'last_name' => $customer->name,
	);
	$contact_id = bt_edd_mailjet()->api->get_contact_id( $customer->email );
	if (empty($contact_id)) {
		// Create contact and add note
		$user_meta = bt_edd_mailjet_map_meta_fields($user_meta);
		$contact_id = bt_edd_mailjet()->api->add_contact($user_meta);
		if (is_wp_error($contact_id)) {
			//$payment->add_note('Error creating contact in EDD Mailjet: ' . $contact_id->get_error_message());
			bt_edd_mailjet_log( $contact_id->get_error_code(), $user_id, sprintf( __( 'Error creating contact in Mailjet: %s', 'bt-edd-mailjet' ), $contact_id->get_error_message() ) );
			return false;
		} else {
			//$payment->add_note('EDD Mailjet contact ID ' . $contact_id . ' created via guest checkout.');
			bt_edd_mailjet_log( 'info', $user_id, __( 'Mailjet contact ID created', 'bt-edd-mailjet' ), array( 'meta_array' => $user_meta ) );
			
		}
	} else {
		// Existing contact
		$user_meta = bt_edd_mailjet_map_meta_fields($user_meta);
		$result = bt_edd_mailjet()->api->update_contact($contact_id, $user_meta);
		if ( is_wp_error( $result ) ) {
			bt_edd_mailjet_log( $result->get_error_code(), $user_id, sprintf(__('Error updating contact: %s', 'bt-edd-mailjet' ), $result->get_error_message() ) );
		}
	}

	// If no contact ID, don't try applying tags
	if ( false == $contact_id ) {
		return false;
	}
	$result = bt_edd_mailjet()->api->apply_tags($tags, $contact_id);
	if ( is_wp_error( $result ) ) {
		bt_edd_mailjet_log( $result->get_error_code(), $user_id, sprintf(__('Error while applying tags: %s', 'bt-edd-mailjet' ), $result->get_error_message() ) );
		return false;
	}
	return true;
}

/**
 * Handle a log entry.
 *
 * @param int $timestamp Log timestamp.
 * @param string $level emergency|alert|critical|error|warning|notice|info|debug
 * @param string $message Log message.
 * @param array $context {
 *     Additional information for log handlers.
 *
 *     @type string $source Optional. Source will be available in log table.
 *                  If no source is provided, attempt to provide sensible default.
 * }
 *
 * @see BT_EDD_Log_Handler::get_log_source() for default source.
 *
 * @return bool False if value was not handled and true if value was handled.
 */

if ( ! function_exists( 'bt_edd_mailjet_log' ) ) {
	function bt_edd_mailjet_log( $level, $user, $message, $context = array() ) {
		return bt_edd_mailjet()->logger->handle( $level, $user, $message, $context );
	}
}

/**
 * Gets the display label for a given tag ID
 * 
 * @since 1.0.0
 *
 * @access public
 * @return string Label for given tag
 */
function bt_edd_mailjet_get_tag_label( $tag_id ) {

	$available_tags = bt_edd_mailjet()->settings->get( 'available_tags' );
	if ( ! isset( $available_tags[ $tag_id ] ) ) {
		return '(Unknown Tag: ' . $tag_id . ')';
	} elseif ( is_array( $available_tags[ $tag_id ] ) ) {
		return $available_tags[ $tag_id ]['label'];
	} else {
		return $available_tags[ $tag_id ];
	}

}



