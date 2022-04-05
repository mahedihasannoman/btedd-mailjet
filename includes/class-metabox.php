<?php
defined( 'ABSPATH' ) || exit();

class BT_EDD_Metabox {
	/**
	 * class constructor
	 * @since 1.0.0
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'bt_edd_metaboxes_render' ) );
		add_action( 'save_post', array( $this, 'metabox_save' ) );
	}

	/**
	 * function to define metaboxes in download post type
	 * @since 1.0.0
	 */
	public function bt_edd_metaboxes_render() {
		add_meta_box( 'bt-edd-download-metabox', __( 'Edd Mailjet', 'bt-edd-mailjet' ), array(
			$this,
			'bt_edd_download_settings'
		), 'download', 'side', 'default' );
	}

	/**
	 * function to add meta boxes
	 *
	 * @param $post
	 *
	 * @return void
	 * @since 1.0.0
	 */
	public function bt_edd_download_settings( $post ) {
		wp_nonce_field( 'bt_edd_download_settings_nonce_action', 'bt_edd_download_settings_nonce' );
		$options = bt_edd_get_mailjet_contact_list();
		$apply_list_download  = get_post_meta( $post->ID, 'bt_edd_apply_list_download', true );
		$remove_list_download = get_post_meta( $post->ID, 'bt_edd_remove_list_download', true );
		$delay                = get_post_meta( $post->ID, 'bt_edd_delay_before_lists', true );
		$purchase_tag_list    = get_post_meta( $post->ID, 'bt_edd_purchase_tag_list', true );
		$refund_tag_list      = get_post_meta( $post->ID, 'bt_edd_refund_tag_list', true );
		?>
        <p class="form-field bt-edd-field bt-edd-check _apply_list_download-field">
            <label for="_apply_list_download"><?php echo __( 'Apply lists when a user views this download:', 'bt-edd-mailjet' ); ?></label>
            <select name="_apply_list_download[]" id="_apply_list_download" class="bt-edd-select2" multiple>
				<?php foreach ( $options as $key => $value ) { ?>
                    <option value="<?php echo esc_attr($key) ?>" <?php echo ! empty( $apply_list_download ) && in_array( $key, $apply_list_download ) ? 'selected' : '' ?>><?php echo esc_html($value); ?></option>
				<?php } ?>
            </select>
        </p>

        <p class="form-field bt-edd-field bt-edd-check _remove_list_download-field">
            <label for="_remove_list_download"><?php echo __( 'Remove lists when a user views this download:', 'bt-edd-mailjet' ); ?></label>
            <select name="_remove_list_download[]" id="_remove_list_download" class="bt-edd-select2" multiple>
				<?php foreach ( $options as $key => $value ) { ?>
                    <option value="<?php echo $key ?>" <?php echo ! empty( $remove_list_download ) && in_array( $key, $remove_list_download ) ? 'selected' : '' ?>><?php echo esc_html($value); ?></option>
				<?php } ?>
            </select>
        </p>

        <p class="form-field bt-edd-field bt-edd-check _remove_list_download-field">
            <label for="_delay_before_lists"><?php echo __( 'Delay (in ms) before applying / removing lists:', 'bt-edd-mailjet' ); ?></label>
            <input type="text" name="_delay_before_lists" id="_delay_before_lists" value="<?php echo ! empty( $delay ) ? $delay : 0; ?>">
        </p>

        <p class="form-field bt-edd-field bt-edd-check _purchase_tag_list-field">
            <label for="_purchase_tag_list"><?php echo __( 'Apply these tags in Mailjet when purchased', 'bt-edd-mailjet' ); ?></label>
            <select name="_purchase_tag_list[]" id="_purchase_tag_list" class="bt-edd-select2" multiple>
				<?php foreach ( $options as $key => $value ) { ?>
                    <option value="<?php echo esc_attr($key) ?>" <?php echo ! empty( $purchase_tag_list ) && in_array( $key, $purchase_tag_list ) ? 'selected' : '' ?>><?php echo esc_html($value); ?></option>
				<?php } ?>
            </select>
        </p>

        <p class="form-field bt-edd-field bt-edd-check _purchase_tag_list-field">
            <label for="_refund_tag_list"><?php echo __( 'Apply these tags in Mailjet when refunded', 'bt-edd-mailjet' ); ?></label>
            <select name="_refund_tag_list[]" id="_refund_tag_list" class="bt-edd-select2" multiple>
				<?php foreach ( $options as $key => $value ) { ?>
                    <option value="<?php echo esc_attr($key) ?>" <?php echo ! empty( $refund_tag_list ) && in_array( $key, $refund_tag_list ) ? 'selected' : '' ?>><?php echo esc_html($value); ?></option>
				<?php } ?>
            </select>
        </p>

		<?php

		?>
		<?php
	}

	/**
	 * function to save meta value
	 *
	 * @param $post_id
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function metabox_save( $post_id ) {

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! isset( $_POST['bt_edd_download_settings_nonce'] ) || ! wp_verify_nonce( $_POST['bt_edd_download_settings_nonce'], 'bt_edd_download_settings_nonce_action' ) ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['_apply_list_download'] ) ) {
			update_post_meta( $post_id, 'bt_edd_apply_list_download', $_POST['_apply_list_download'] );
		} else {
			update_post_meta( $post_id, 'bt_edd_apply_list_download', '' );
		}

		if ( isset( $_POST['_remove_list_download'] ) ) {
			update_post_meta( $post_id, 'bt_edd_remove_list_download', $_POST['_remove_list_download'] );
		} else {
			update_post_meta( $post_id, 'bt_edd_remove_list_download', '' );
		}

		if ( isset( $_POST['_delay_before_lists'] ) ) {
			update_post_meta( $post_id, 'bt_edd_delay_before_lists', ! empty( $_POST['_delay_before_lists'] ) ? sanitize_text_field( $_POST['_delay_before_lists'] ) : 0 );
		} else {
			update_post_meta( $post_id, 'bt_edd_delay_before_lists', 0 );
		}

		if ( isset( $_POST['_purchase_tag_list'] ) ) {
			update_post_meta( $post_id, 'bt_edd_purchase_tag_list', $_POST['_purchase_tag_list'] );
		} else {
			update_post_meta( $post_id, 'bt_edd_purchase_tag_list', '' );
		}

		if ( isset( $_POST['_refund_tag_list'] ) ) {
			update_post_meta( $post_id, 'bt_edd_refund_tag_list', $_POST['_refund_tag_list'] );
		} else {
			update_post_meta( $post_id, 'bt_edd_refund_tag_list', '' );
		}
	}
}

new BT_EDD_Metabox();