const { PDFDocument } = PDFLib;

async function copyPages() {
    const url1 = 'https://ws.caexlogistics.com/wsCAEXLogisticsSB/doc/VerGuiaPDF.aspx?NumeroGuia=1W229422399&Login=PHONEXGT'
    const url2 = 'https://ws.caexlogistics.com/wsCAEXLogisticsSB/doc/VerGuiaPDF.aspx?NumeroGuia=1W229422400&Login=PHONEXGT'
  
    const firstDonorPdfBytes = await fetch(url1,{ method: 'GET',
    mode: 'cors',
    cache: 'default'
 }).then(res => res.arrayBuffer()).catch( error => console.log(error));
    const secondDonorPdfBytes = await fetch(url2,{ method: 'GET',
    mode: 'cors',
    cache: 'default'
 }).then(res => res.arrayBuffer()).catch( error => console.log(error))
  
    const firstDonorPdfDoc = await PDFDocument.load(firstDonorPdfBytes)
    const secondDonorPdfDoc = await PDFDocument.load(secondDonorPdfBytes)
  
    const pdfDoc = await PDFDocument.create();
  
    const [firstDonorPage] = await pdfDoc.copyPages(firstDonorPdfDoc, [0])
    const [secondDonorPage] = await pdfDoc.copyPages(secondDonorPdfDoc, [0])
  
    pdfDoc.addPage(firstDonorPage)
    pdfDoc.insertPage(0, secondDonorPage)
  
    const pdfBytes = await pdfDoc.save()
    console.log("just before trying to download pdf");
    download(pdfBytes, "pdf-lib_page_copying_example.pdf", "application/pdf");
}

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
                console.log("csvContent" + csvContent);
                var encodedUri = encodeURI(csvContent);
                console.log( "afterencodedURL" );
                window.open(encodedUri);
                console.log( "after window open" );
                console.log("started working on pdfs:");
                copyPages();
                console.log("after calling copyPages()");

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