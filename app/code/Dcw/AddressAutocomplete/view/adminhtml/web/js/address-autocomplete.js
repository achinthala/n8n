require(['jquery'], function($) {
    $(document).ready(function() {
        var addressField = document.getElementById('order_address');
        var autocomplete = new google.maps.places.Autocomplete(addressField);
        autocomplete.setTypes(['geocode']);
    });
});