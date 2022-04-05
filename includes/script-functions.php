<?php
defined( 'ABSPATH' ) || exit();

function bt_edd_mailjet_load_admin_scripts() {
	$css_dir    = BT_EDD_MAILJET_PLUGIN_ASSETS . '/css/';
	$js_dir     = BT_EDD_MAILJET_PLUGIN_ASSETS . '/js/';
	$vendor_dir = BT_EDD_MAILJET_PLUGIN_ASSETS . '/vendor/';

	wp_enqueue_style( 'bt-edd-mailjet-select2', $vendor_dir . 'select2/select2.css', array(), time() );
	wp_enqueue_script( 'bt-edd-mailjet-select2', $vendor_dir . 'select2/select2.js', array( 'jquery' ), time(), true );
	wp_enqueue_style( 'bt-edd-mailjet-admin', $css_dir . 'bt-edd-mailjet-admin.css', array(), time() );
	wp_enqueue_script( 'bt-edd-mailjet-admin', $js_dir . 'bt-edd-mailjet-admin.js', array( 'jquery' ), time(), true );
	$bt_edd_obj = array( 'ajax_url' => admin_url('admin-ajax.php'), '_nonce' => wp_create_nonce("bt-edd-contact-sync-nonce") );
	wp_localize_script( 'bt-edd-mailjet-admin', 'bt_edd_obj', $bt_edd_obj );
}

add_action( 'admin_enqueue_scripts', 'bt_edd_mailjet_load_admin_scripts' );
