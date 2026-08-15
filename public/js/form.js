/* AF Email Marketing — Subscribe Form JS */
jQuery( function ( $ ) {

    $( document ).on( 'submit', '.af-form', function ( e ) {
        e.preventDefault();

        var $form  = $( this );
        var $btn   = $form.find( '.af-btn' );
        var $msg   = $form.find( '.af-message' );
        var origTxt = $btn.text();

        // Reset
        $msg.hide().removeClass( 'success error' ).text( '' );
        $btn.prop( 'disabled', true ).text( 'Subscribing…' );

        $.post(
            AF_Email.ajax_url,
            {
                action:     'af_subscribe',
                nonce:      AF_Email.nonce,
                email:      $form.find( '[name="email"]' ).val(),
                first_name: $form.find( '[name="first_name"]' ).val(),
            },
            function ( response ) {
                if ( response.success ) {
                    $msg.addClass( 'success' ).text( response.data.message ).show();
                    $form.find( '.af-fields' ).slideUp( 200 );
                    $form.find( '.af-privacy' ).hide();
                } else {
                    $msg.addClass( 'error' ).text( response.data.message ).show();
                    $btn.prop( 'disabled', false ).text( origTxt );
                }
            }
        ).fail( function () {
            $msg.addClass( 'error' ).text( 'Something went wrong. Please try again.' ).show();
            $btn.prop( 'disabled', false ).text( origTxt );
        } );
    } );

} );
