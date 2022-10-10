jQuery(function($) {
    // Handler for .ready() called.
    console.log("handler for ready called");
    if( $('.settings_page_caex-csv #wpbody-content').length ) {
        var upload_csv_html_form = `<div class="container">
        <h2 class="mb-3 mt-3"> Generate multiple tracking ids from a CSV File</h2>
        <form method="post" enctype="multipart/form-data" id="generate_trackings_form">
            <div class="form-group">
              <label for="exampleFormControlFile1">Please Select File</label>
              <input id="file_import" name="import_data" type="file" />
            </div>
            <div class="form-group">
             <input type="submit" name="submit" value="submit" class="btn btn-primary">
           </div>
      </form>
      </div>`;
        $( '#wpbody-content' ).after( upload_csv_html_form );
    }
    $('#generate_trackings_form').on('submit', function(event) {
        event.preventDefault();
        console.log("click detected on btn to generate tracking ids");
        var formData = new FormData();
        var importFiles = $('#file_import')[0].files;

        // For EACH file, append to formData.
        // NOTE: Just appending all of importFiles doesn't transition well to PHP.
        jQuery.each( importFiles, function( index, value ) {
            var name = 'file_' + index;
            formData.append( name, value )
        });

        formData.append( 'action', 'caex_generate_trackings' );
        formData.append( 'nonce', dl_wc_caex_admin_script.nonce );
        jQuery.ajax({
            type: "post",
            url: dl_wc_caex_admin_script.ajax_url,
            data: formData,
            cache: false,
            dataType: 'json', // This replaces dataFilter: function() && JSON.parse( data ).
            processData: false, // Don't process the files
            contentType: false, // Set content type to false as jQuery will tell the server its a query string request


            beforeSend: function(jqXHR, settings) {
                console.log("Haven't entered server side yet.");
            },
            success: function(result){
                console.log("sincfonización finalizó exitosamente");
                $('.wc-caex-sync-result').html(result.message);
                $('#locations_sync_date').val(result.locations_sync_date);
                console.log( result.data );
                let csvContent = "data:text/csv;charset=utf-8," 
                + result.data.map(e => e.join(",")).join("\n");
                var encodedUri = encodeURI(csvContent);
                window.open(encodedUri);
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
            data: "action=caex_generate_pdf&nonce=" + dl_wc_caex_admin_script.nonce,
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


// https://www.cloudways.com/blog/the-basics-of-file-upload-in-php/