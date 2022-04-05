<?php
defined( 'ABSPATH' ) || exit();

class BT_EDD_MailJet {

	/**
	 * Contains API params
	 * @since 1.0.0
	 */
	public $params;

	/**
	 * Gets params for API calls
	 *
	 * @param null $mailjet_username
	 * @param null $mailjet_password
	 *
	 * @return  array
	 * @since 1.0.0
	 * @access  public
	 */
	public function get_params( $mailjet_username = null, $mailjet_password = null ) {
		// Get saved data from DB
		if ( empty( $mailjet_username ) || empty($mailjet_password) ) {
			$mailjet_username = edd_get_option( 'bt_edd_mailjet_api_key' );
			$mailjet_password = edd_get_option( 'bt_edd_mailjet_secret_key' );
		}
		$auth_key = base64_encode($mailjet_username . ':' . $mailjet_password);
		$this->params = array(
			'timeout'     => 30,
			'httpversion' => '1.1',
			'User-Agent'  => 'bt-edd-mailjet; ' . home_url(),
			'headers'     => array(
				'Authorization' => 'Basic ' . $auth_key,
				'Content-Type'  => 'application/json'
			)
		);
		return $this->params;
	}

	/**
	 * Initialize connection
	 *
	 * @param null $mailjet_username
	 * @param null $mailjet_password
	 * @param bool $test
	 *
	 * @return  bool|WP_Error
	 * @since 1.0.0
	 * @access  public
	 */
	public function connect( $mailjet_username = null, $mailjet_password = null, $test = false ) {
		if ( $test == false ) {
			return true;
		}
		if ( ! $this->params ) {
			$this->get_params( $mailjet_username, $mailjet_password );
		}
		$request  = 'https://api.mailjet.com/v3/REST/contactslist';
		$response = wp_remote_get( $request, $this->params );
		if( is_wp_error( $response ) ) {
			return $response;
		}
		return true;
	}

	/**
	 * Gets all available tags and saves them to options
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array|WP_Error 
	 */
	public function get_all_tags() {
		if ( ! $this->params ) {
			$this->get_params();
		}
		$available_tags = array();
		$request  = 'https://api.mailjet.com/v3/REST/contactslist';
		$response = wp_remote_get( $request, $this->params );
		if( is_wp_error( $response ) ) {
			return $response;
		}
		$body_json = json_decode( $response['body'], true );
		if( ! empty( $body_json['Data'] ) ){
			foreach ( $body_json['Data'] as $row ) {
				$available_tags[ $row['ID'] ] = $row['Name'];
			}
		}
		bt_edd_mailjet()->settings->set( 'available_tags', $available_tags );
		return $available_tags;
	}

	/**
	 * Loads all custom fields from CRM and merges with local list
	 *
	 * @since 1.0.0
	 * @access public
	 * @return array|WP_Error
	 */
	public function get_fields() {
		if ( ! $this->params ) {
			$this->get_params();
		}
		$contact_fields = array( 'Email' => 'Email Address', 'Name' => 'Name' );
		$request    = "https://api.mailjet.com/v3/REST/contactmetadata";
		$response   = wp_remote_get( $request, $this->params );
		if( is_wp_error( $response ) ) {
			return $response;
		}
		$body_json = json_decode( $response['body'], true );
		foreach ( $body_json['Data'] as $field_data ) {
			$crm_meta_fields[$field_data['Name']] =  ucwords( str_replace( '_', ' ', $field_data[ 'Name' ] ) );
		}
		$crm_fields = array( 'Standard Fields' => $contact_fields, 'Custom Fields' => $crm_meta_fields );
		asort( $crm_fields );
		bt_edd_mailjet()->settings->set( 'fields', $crm_fields );
		return $crm_fields;
	}

	/**
	 * Gets contact ID for a user based on email address
	 *
	 * @param $email_address
	 *
	 * @return int|bool|WP_Error
	 * @since 1.0.0
	 * @access public
	 */
	public function get_contact_id( $email_address ) {
		if ( ! $this->params ) {
			$this->get_params();
		}
		$contact_info = array();
		$request      = 'https://api.mailjet.com/v3/REST/contact/' . urlencode( $email_address );
		$response     = wp_remote_get( $request, $this->params );
		if( is_wp_error( $response ) && $response->get_error_message() == 'Not Found' ) {
			return false;
		} elseif( is_wp_error( $response ) ) {
			return $response;
		}
		$body_json    = json_decode( $response['body'], true );
		if ( empty( $body_json['Data'][0]['Email'] ) ) {
			return false;
		}
		return $body_json['Data'][0]['ID'];
	}

	/**
	 * Adds a new contact
	 *
	 * @param $data
	 * @param bool $map_meta_fields
	 *
	 * @return int|null|WP_Errpr
	 * @since 1.0.0
	 * @access public
	 */
	public function add_contact( $data, $map_meta_fields = true ) {
		if ( ! $this->params ) {
			$this->get_params();
		}
		if ( empty( $data ) ) {
			return null;
		}
		$post_data = array();
		$post_data['IsExcludedFromCampaigns'] = false;
		$post_data['Name']				  	  = $data['Name'];
		$post_data['Email'] 				  = $data['Email'];
		$url              = 'https://api.mailjet.com/v3/REST/contact';
		$params           = $this->params;
		$params['body']   = json_encode( $post_data );
		$response = wp_remote_post( $url, $params );
		if( is_wp_error( $response ) ) {
			return $response;
		}
		$body = json_decode( wp_remote_retrieve_body( $response ) );
		unset($data['Name']);
		unset($data['Email']);
		if( ! empty( $data ) ) {
			foreach ($data as $key => $value) {
				$meta[] = array (  'Name' => $key, 'Value' => $value );
			}
			$meta_data['ContactID'] = $body->Data[0]->ID;
			$meta_data['Data'] = $meta; 
			$url               = 'https://api.mailjet.com/v3/REST/contactdata/' . $body->Data[0]->ID;
			$params            = $this->params;
			$params['method']  = 'PUT';
			$params['body']    = json_encode($meta_data);
			$response = wp_remote_post( $url, $params );
			if( is_wp_error( $response ) ) {
				return $response;
			}
		}
		return $body->Data[0]->ID;
	}


	/**
	 * Update contact
	 *
	 * @param $contact_id
	 * @param $data
	 * @param bool $map_meta_fields
	 *
	 * @return bool|WP_Error
	 * @since 1.0.0
	 * @access public
	 */
	public function update_contact( $contact_id, $data, $map_meta_fields = true ) {
		if ( ! $this->params ) {
			$this->get_params();
		}
		if( empty( $data ) ) {
			return false;
		}
		$post_data = array();
		$post_data['IsExcludedFromCampaigns'] = false;
		$post_data['Name']				  	  = $data['Name'];
		$post_data['Email'] 				  = $data['Email'];
		$url               = 'https://api.mailjet.com/v3/REST/contact/' . $contact_id;
		$params            = $this->params;
		$params['method']  = 'PUT';
		$params['body']    = json_encode($post_data);
		$response = wp_remote_post( $url, $params );
		if( is_wp_error( $response ) ) {
			return $response;
		}
		// Update metadata below (everything other the Email and Name fields that are dealt with above)
		unset($data['Name']);
		unset($data['Email']);
		if( ! empty( $data ) ) {
			foreach ($data as $key => $value) {
				$meta[] = array (  'Name' => $key, 'Value' => $value );
			}
			$meta_data['ContactID'] = $contact_id;
			$meta_data['Data'] = $meta; 
			$url               = 'https://api.mailjet.com/v3/REST/contactdata/' . $contact_id;
			$params            = $this->params;
			$params['method']  = 'PUT';
			$params['body']    = json_encode($meta_data);
			$response = wp_remote_post( $url, $params );
			if( is_wp_error( $response ) ) {
				return $response;
			}

		}
		return true;
	}

	/**
	 * Gets all tags currently applied to the user, also update the list of available tags
	 *
	 * @param $contact_id
	 *
	 * @return array|bool|WP_Error
	 * @since 1.0.0
	 * @access public
	 */
	public function get_tags( $contact_id ) {
		if ( ! $this->params ) {
			$this->get_params();
		}
		$request    = 'https://api.mailjet.com/v3/REST/contact/' . urlencode( $contact_id ) . '/getcontactslists';
		$response   = wp_remote_get( $request, $this->params );
		if( is_wp_error( $response ) ) {
			return $response;
		}
		$body_json = json_decode( $response['body'], true );
		if ( empty( $body_json ) || empty( $body_json['Data'][0]['ListID'] ) ) {
			return false;
		}
		$tags = array();
		foreach ($body_json['Data'] as $tag_data) {
			$tags[] = $tag_data['ListID'];
		}
		return $tags;
	}

	/**
	 * Applies tags to a contact
	 *
	 * @param $tags
	 * @param $contact_id
	 *
	 * @return bool|WP_Error
	 * @since 1.0.0
	 * @access public
	 */
	public function apply_tags( $tags, $contact_id ) {
		if ( ! $this->params ) {
			$this->get_params();
		}
		$object_tags = array();
		foreach ($tags as $tag) {
			$object_tags[] = (object) ['ListID' => $tag, 'Action' => 'addnoforce' ];
		}
		$request      		= 'https://api.mailjet.com/v3/REST/contact/' . $contact_id . '/managecontactslists';
		$params           	= $this->params;
		$params['method'] 	= 'POST';
		$params['body']  	= json_encode( array ( 'ContactsLists' =>  $object_tags ) ) ;
		$response = wp_remote_post( $request, $params );
		if( is_wp_error( $response ) ) {
			return $response;
		}
		return true;
	}

	/**
	 * Removes tags from a contact
	 *
	 * @param $tags
	 * @param $contact_id
	 *
	 * @return bool|WP_Error
	 * @since 1.0.0
	 * @access public
	 */
	public function remove_tags( $tags, $contact_id ) {
		if ( ! $this->params ) {
			$this->get_params();
		}
		$object_tags = array();
		foreach ($tags as $tag) {
			$object_tags[] = (object) ['ListID' => $tag, 'Action' => 'remove' ];
		}
		$request      		= 'https://api.mailjet.com/v3/REST/contact/' . $contact_id . '/managecontactslists';
		$params           	= $this->params;
		$params['method'] 	= 'POST';
		$params['body']  	= json_encode( array ( 'ContactsLists' =>  $object_tags ) ) ; 
		$response = wp_remote_post( $request, $params );
		if( is_wp_error( $response ) ) {
			return $response;
		}
		return true;
	}
}
