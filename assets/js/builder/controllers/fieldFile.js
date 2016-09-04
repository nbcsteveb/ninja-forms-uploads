var fileUploadsFieldController = Marionette.Object.extend( {
    initialize: function() {
        Backbone.Radio.channel( 'conditions-key-select-field-file_upload' ).reply( 'hide', function(){ return true; } );
    }
});

jQuery( document ).ready( function( $ ) {
    new fileUploadsFieldController();
});