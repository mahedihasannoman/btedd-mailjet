/*jslint browser: true */
/*global jQuery:false */
jQuery(document).ready(function ($) {
    'use strict';
    $.bt_edd_mailjet = {
        init: function () {
            $('.bt-edd-select2').each(function () {
                $(this).select2({
                    theme: 'default bt-edd-select2',
                    placeholder: 'Select lists'
                });
            });
        },
        ajax:function (data) {
            return $.post(bt_edd_obj.ajax_url, data);
        },
        loop: function( data ){


            if( ( data.offset * data.per_request ) < data.total_customer ){

                var sync_data = { 'action':'bt_edd_sync_customers', 'nonce': bt_edd_obj._nonce };
                var sync_result = $.bt_edd_mailjet.ajax( sync_data );
                sync_result.done( function(sync_response){

                    $('.bt_edd_sync_total_completed').show();
                    $('#bt_edd_total_completed_sync').html( sync_response.offset * sync_response.per_request );
                    $( '#bt_edd_sync_progress' ).attr('value', ( sync_response.offset * sync_response.per_request ));
                    $.bt_edd_mailjet.loop( sync_response );
                } );

            }else{
                $('.bt_edd_sync_finished').show();
                $('#bt_edd_start_sync_contact').removeAttr('disabled');
                $('#bt_edd_start_sync_contact').next().removeClass( 'is-active' );
            }
            
        }
    };
    $.bt_edd_mailjet.init();

    /**
     * handle click for start sync button
     */
    $(document).on('click', '#bt_edd_start_sync_contact', function(e){
        e.preventDefault();
        $( '#bt_edd_total_completed_sync' ).html(0);
        $( '#bt_edd_total_contact_for_sync').html(0);
        var obj = $(this);
        // disable the sync button
        obj.attr( 'disabled', 'disabled' );
        //show loading icon
        obj.next().addClass( 'is-active' );
        var list = $( '#bt_edd_sync_assign_list_select' ).val();
        var data = { 'action':'bt_edd_get_customers', 'list': list, 'nonce': bt_edd_obj._nonce };
        var result = $.bt_edd_mailjet.ajax( data );
        result.done( function( response ) {
            if( response.total_customer > 0 ){
                $( '.bt_edd_sync_started' ).show();
                $( '.bt_edd_sync_total_contact' ).show();
                $( '#bt_edd_total_contact_for_sync').html( response.total_customer );
                $( '.bt_edd_sync_progress_bar').show();
                $( '#bt_edd_sync_progress' ).attr('max', response.total_customer)
                $.bt_edd_mailjet.loop( response );
            }else{
                $('.bt_edd_sync_finished').show();
                $('#bt_edd_start_sync_contact').removeAttr('disabled');
                $('#bt_edd_start_sync_contact').next().removeClass( 'is-active' );
            }
        });
    })

});