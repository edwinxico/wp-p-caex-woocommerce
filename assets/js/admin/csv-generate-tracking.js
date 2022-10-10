jQuery(function($) {
    // Handler for .ready() called.
    console.log("handler for ready called");
    $('.btn-caex-generate-trackings').on('click', function(event) {
        event.preventDefault();
        console.log("click detected on btn to generate tracking ids");
        jQuery.ajax({
            type: "post",
            url: dl_wc_caex_admin_script.ajax_url,
            data: "action=caex_generate_tracking_ids&nonce=" + dl_wc_caex_admin_script.nonce,
            success: function(result){
                console.log("sincfonización finalizó exitosamente");
                result = JSON.parse(result);
                $('.wc-caex-sync-result').html(result.message);
                $('#locations_sync_date').val(result.locations_sync_date);

            },
            error: function(errorThrown){
                console.log(errorThrown);
            }
        });
    });


    $('.btn-caex-generate-pdfs').on('click', function(event) {
        event.preventDefault();
        console.log("click detected on btn to generate pdfs combined");
        jQuery.ajax({
            type: "post",
            url: dl_wc_caex_admin_script.ajax_url,
            data: "action=caex_generate_tracking_ids&nonce=" + dl_wc_caex_admin_script.nonce,
            success: function(result){
                console.log("sincfonización finalizó exitosamente");
                result = JSON.parse(result);
                $('.wc-caex-sync-result').html(result.message);
                $('#locations_sync_date').val(result.locations_sync_date);

            },
            error: function(errorThrown){
                console.log(errorThrown);
            }
        });
    });
});