requirejs(['jquery', 'mage/url', 'domReady'], function ($, urlBuilder) {
    $(document).ready(function () {
        //wait until the country fields are rendered
        const intervalId = setInterval(function () {
            let countryInput = $("#country");
            if (countryInput.length === 0) {
                countryInput = $('[name*="country_id"]');
            }

            if ($(countryInput).length) {
                getIpCountry(intervalId);
            }
        }, 100);
    });

    function getIpCountry(intervalId) {
        //run once at page load
        clearInterval(intervalId);

        jQuery.ajax({
            type: "GET",
            url: loqateIpcountryUrl,
            crossDomain: true,
            success: function (response) {
                if (!(response.error || response.message)) {
                    handleIpCountryApiResponse(response);
                }
            }
        });
    }

    function handleIpCountryApiResponse(response) {
        if (typeof response === 'object' && response !== null) {
            countryInput = $('[name*="country_id"]');
            if (response.Iso2 !== '' && response.Iso2 != null) {
                countryInput.each(function () {
                    var option = jQuery(this).find('option[value="' + response.Iso2 + '"]');
                    option.prop('selected', true).trigger('change');
                });
            }
        }
    }
});
