jQuery(function($) {
    // Handler for .ready() called.
    console.log("handler for ready called");
    $('.btn-caex-sync').on('click', function(event) {
        event.preventDefault();
        console.log("click detected on btn");
    })
});