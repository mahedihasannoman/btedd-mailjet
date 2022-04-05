<?php
defined( 'ABSPATH' ) || exit();

class BT_EDD_Settings {

	/**
	 * Contains all plugin settings
	 * @since 1.0.0
	 */
	public $options;

	/**
	 * Slug for settings field
	 * @since 1.0.0
	 */
	public $slug = 'mailjet_';

	/**
	 * class constructor
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->options = get_option( 'bt_edd_options', array() );
		add_filter( 'edd_settings_tabs', array( $this, 'mailjet_add_tab' ) );
		add_filter( 'edd_registered_settings', array( $this, 'mailjet_tab_settings' ) );
		add_filter( 'edd_settings_sections', array( $this, 'mailjet_settings_section' ) );
		add_action( 'admin_init', array($this,'wp_setting_init' ));  

	}

	/**
	 * add settings section for contact sync tab
	 *
	 * @since 1.0.0
	 * @return void
	 */
	function wp_setting_init() {
        // register a new section in the "reading" page
        add_settings_section(
			'edd_settings_bt_edd_mailjet_contact_sync',
			__return_null(),
			array($this,'sync_setting_section'),
			'edd_settings_bt_edd_mailjet_contact_sync'
		);
    }
      
	/**
	 * Callback function for edd_settings_bt_edd_mailjet_contact_sync section
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function sync_setting_section() {
		$options = bt_edd_get_mailjet_contact_list();
		$html = '<div class="bt_sync_settings_container">';
			$html .= '<h2>' . __('Sync All Existing Customer', 'bt-edd-mailjet') . '</h2>';
			$html .= '<table class="form-table">';
				$html .= '<tbody>';
					$html .= '<tr>';
						$html .= '<th>';
							$html .= __('Assign List', 'bt-edd-mailjet');
						$html .= '</th>';
						$html .= '<td>';
							$html .= '<select id="bt_edd_sync_assign_list_select">';
							foreach( $options as $key => $value ):
								$html .= '<option value="' . esc_attr( $key ) . '" >' . esc_html( $value ) . '</option>';
							endforeach;
							$html .= '</select>';
							$html .= '&nbsp;<i>' . __( 'The selected lists will be applied to all customer.', 'bt-edd-mailjet' ) . '</i>';
							$html .= '<div class="bt_edd_contact_sync_status">';
								$html .= '<div class="bt_edd_sync_status_span bt_edd_sync_started"> ' . __( 'Sync Started!', 'bt-edd-mailjet' ) . ' </div>';
								$html .= '<div class="bt_edd_sync_status_span bt_edd_sync_total_contact"> ' . __('Total Contact Found:', 'bt-edd-mailjet' ) . ' <span id="bt_edd_total_contact_for_sync">0</span> </div>';
								$html .= '<div class="bt_edd_sync_status_span bt_edd_sync_total_completed"> ' . __( 'Total Sync Completed:', 'bt-edd-mailjet' ) . ' <span id="bt_edd_total_completed_sync" >0</span> </div>';
								$html .= '<div class="bt_edd_sync_status_span bt_edd_sync_progress_bar"><progress id="bt_edd_sync_progress" value="0" max="0"> 0% </progress></div>';
								$html .= '<div class="bt_edd_sync_status_span bt_edd_sync_finished"> ' . __( 'Sync Completed Successfully!', 'bt-edd-mailjet' ) . ' </div>';
							$html .= '</div>';
						$html .= '</td>';
					$html .= '</tr>';

					$html .= '<tr>';
						$html .= '<th>';
						$html .= '</th>';
						$html .= '<td>';
							$html .= '</span><input type="button" id="bt_edd_start_sync_contact" class="button button-default" value="' . __('Sync Contact', 'bt-edd-mailjet') . '" /><span class="spinner" style="float:none">';
						$html .= '</td>';
					$html .= '</tr>';

				$html .= '</tbody>';
			$html .= '</table>';
		$html .= '</div>';
		echo $html;
		?>
		<style type="text/css">
			#submit{display:none}
		</style>
		<?php
	}
	
	/**
	 * Callback function for edd_settings_tabs
	 *
	 * @since 1.0.0
	 * @param array $tabs
	 * @return array
	 */
	public function mailjet_add_tab( $tabs ) {
		$tabs['bt_edd_mailjet'] = __( 'EDD Mailjet', 'bt-edd-mailjet' );
		return $tabs;
	}

	/**
	 * Callback function for edd_settings_sections
	 *
	 * @since 1.0.0
	 * @param array $sections
	 * @return array
	 */
	public function mailjet_settings_section( $sections ) {

		$sections['bt_edd_mailjet'] = array(
			'main'            => __( 'Authentication', 'bt-edd-mailjet' ),
			'general_settings' => __( 'General Settings', 'bt-edd-mailjet' ),
			'contact_fields'  => __( 'Contact Fields', 'bt-edd-mailjet' ),
			'contact_sync'  => __( 'Sync Existing Contacts', 'bt-edd-mailjet' ),
		);
		return apply_filters('bt_edd_settings_section', $sections);
	}

	/**
	 * Callback function for edd_registered_settings
	 *
	 * @since 1.0.0
	 * @param array $settings
	 * @return array
	 */
	public function mailjet_tab_settings( $settings ) {
		$settings['bt_edd_mailjet'] = apply_filters( 'edd_settings_mailjet', array(
			'main'            => array(
				'mailjet_settings'        => array(
					'id'   => 'mailjet_settings',
					'name' => '<h3>' . __( 'EDD Mailjet', 'bt-edd-mailjet' ) . '</h3>',
					'desc' => '',
					'type' => 'header',
				),
				'bt_edd_mailjet_api_key'    => array(
					'id'   => 'bt_edd_mailjet_api_key',
					'name' => __( 'Mailjet Api Key', 'bt-edd-mailjet' ),
					'desc' => __( 'Enter Mailjet Api Key', 'bt-edd-mailjet' ),
					'type' => 'password',
				),
				'bt_edd_mailjet_secret_key' => array(
					'id'   => 'bt_edd_mailjet_secret_key',
					'name' => __( 'Mailjet Secret Key', 'bt-edd-mailjet' ),
					'desc' => __( 'Enter Mailjet Secret Key', 'bt-edd-mailjet' ),
					'type' => 'password',
				),
			),
			'general_settings' => array(
				'bt_edd_mailjet_create_contacts' => array(
					'id'   => 'bt_edd_mailjet_create_contacts',
					'name' => __( 'Create Contacts', '' ),
					'desc' => __( 'Create new contacts in Mailjet when users register in WordPress.', 'bt-edd-mailjet' ),
					'type' => 'checkbox',
				),
				'bt_edd_mailjet_contacts_list'   => array(
					'id'          => 'bt_edd_mailjet_contacts_list',
					'name'        => __( 'Assign Lists', 'bt-edd-mailjet' ),
					'desc'        => __( 'The selected lists will be applied to anyone who registers an account in WordPress.', 'bt-edd-mailjet' ),
					'type'        => 'select',
					'chosen'      => true,
					'multiple'    => true,
					//todo this options is temporary for checking. Need to update when functionality added
					'options'     => $this->get( 'available_tags', array() ),
					'placeholder' => __( 'Select Lists', 'bt-edd-mailjet' ),
				),
			),
			'contact_fields'  => array(
				'bt_edd_contact_settings'   => array(
					'id'            => 'bt_edd_contact_settings',
					'name'          => '<strong>' . __( 'Contact Fields', 'bt-edd-mailjet' ) . '</strong>',
					'desc'          => '',
					'type'          => 'header',
					'tooltip_title' => __( 'Contact Fiels', 'bt-edd-mailjet' ),
					'tooltip_desc'  => __( 'Select Contact fields for MailJET', 'bt-edd-mailjet' ),
				),
				'bt_edd_custom_field'    => array(
					'name' 	=> __( 'Contact Fields', 'bt-edd-mailjet' ),
					'desc'  => '',
					'type' 	=> 'bt_contact_fields',
					'id'	=> 'bt_contact_fields_map'
				),
			),
			'contact_sync'  => array(),

		) );
		return $settings;
	}

	/**
	 * Get the value of a specific setting
	 *
	 * @param string $key
	 * @param bool $default
	 *
	 * @return array
	 * @since 1.0.0
	 */
	public function get( $key, $default = false ) {
		$key = $this->slug.$key;
		if ( empty( $this->options ) ) {
			$this->options = get_option( 'bt_edd_options', array() );
		}
		$value = isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;

		if( ( empty($value) || ! $value ) && $key == $this->slug . 'available_tags' ){
			$value = bt_edd_mailjet()->api->get_all_tags();
		}elseif( ( empty($value) || ! $value || ( isset($value['Custom Fields']) && empty($value['Custom Fields']) ) ) && $key == $this->slug . 'fields' ){
			$value = bt_edd_mailjet()->api->get_fields();
		}
		return apply_filters( 'bt_edd_get_setting_' . $key, $value );
	}

	/**
	 * Set the value of a specific setting
	 *
	 * @param $key
	 * @param $value
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function set( $key, $value ) {
		$key = $this->slug.$key;
		if ( empty( $this->options ) ) {
			$this->options = get_option( 'bt_edd_options', array() );
		}
		$this->options[ $key ] = $value;
		update_option( 'bt_edd_options', $this->options, false );
	}

	/**
	 * Get all settings
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_all() {
		if ( empty( $this->options ) ) {
			$this->options = get_option( 'bt_edd_options', array() );
		}
		return $this->options;
	}

}

new BT_EDD_Settings();
