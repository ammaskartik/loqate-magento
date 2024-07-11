requirejs(['jquery', 'mage/url','domReady'], function($, urlBuilder) {
    let addressMapping = {
        street_1: 'Line1',
        street_2: 'Line2',
        country: 'CountryIso2',
        postcode: 'PostalCode',
        city: 'City',
        county: 'ProvinceName',
        company: 'Company'
    };

    let loqateFindUrl;
    let loqateRetrieveUrl;

    $(document).ready(function() {
        var loqateElement = document.getElementById('loqate-urls');

        if (loqateElement) {
            loqateFindUrl = loqateElement.getAttribute('data-find-url');
            loqateRetrieveUrl = loqateElement.getAttribute('data-retrieve-url');
        } else {
            console.error('Element with ID "loqate-urls" not found');
            return;
        }

        /*execute a function when someone clicks in the document:*/
        $(document).on('click', function () {
            closeAllLists(this);
        });

        //wait until the address fields are rendered
        setInterval(function() {
            let streetInput = $( "#street_1" );
            if (streetInput.length === 0) { streetInput = $('[name*="street"][name*="[0]"]');}

            if ($(streetInput).length) {
                captureConfig(streetInput);
            }
        }, 100);
    });

    function captureConfig(streetInput) {
        if ($(streetInput).length !== 0) {
            $(streetInput).each(function (index, element) {
                if ($(element).data('loqate_parsed') !== 1) {
                    $(element).data('loqate_parsed', 1);
                    let form = $(element).closest('fieldset.admin__fieldset');

                    if ($(form).length === 0) {
                        form = $(element).closest('form');
                    }

                    let street_1 = form.find('#street_1');
                    if (street_1.length === 0) { street_1 = form.find('[name*="street"][name*="[0]"]');}

                    let street_2 = form.find('#street_2');
                    if (street_2.length === 0) { street_2 = form.find('[name*="street"][name*="[1]"]');}

                    let postcode = form.find('#zip');
                    if (postcode.length === 0) { postcode = form.find('[name*="postcode"]');}

                    let city = form.find('#city');
                    if (city.length === 0) { city = form.find('[name*="city"]');}

                    let county_input = form.find('#region');
                    if (county_input.length === 0) { county_input = form.find('[name*="region"]');}

                    let county_list = form.find('#region_id');
                    if (county_list.length === 0) { county_list = form.find('[name*="region_id"]');}

                    let country = form.find('#country');
                    if (country.length === 0) { country = form.find('[name*="country_id"]');}

                    let company = form.find('#company');
                    if (company.length === 0) { company = form.find('[name*="company"]');}

                    const addressElements = {
                        street_1: street_1,
                        street_2: street_2,
                        postcode: postcode,
                        city: city,
                        county: {
                            input: county_input,
                            list: county_list
                        },
                        country: country,
                        company: company
                    };

                    // create a DIV element which will contain the addresses
                    let addressList = $("<div class='loqate-autocomplete-items'></div>");
                    // add custom class to autocomplete container
                    $(element).parent().addClass('loqate-autocomplete-container')
                    // append DIV as child to autocomplete container
                    $(addressList).insertAfter($(element));


                    //handle street input
                    let inputTimer = 0;
                    $(element).on('input', function () {
                        var selectedCountryIso2 = country.find('option:selected').val();

                        if ($(element).val()) {
                            // cancel any previously-set timer
                            if (inputTimer) {
                                clearTimeout(inputTimer);
                            }
                            inputTimer = setTimeout(function() {
                                getAddresses(element, addressList, selectedCountryIso2);
                            }, 500);
                        } else {
                            $(addressList).empty();
                        }
                    });

                    //handle address selection
                    $(addressList).on('click', '.loqate-address-item', function () {
                        const addressId = $(this).attr('data-id');
                        const addressType = $(this).attr('data-type');

                        if (addressType === 'Container'){
                            var selectedCountryIso2 = country.find('option:selected').val();

                            getAddresses(element, addressList, selectedCountryIso2, addressId);
                        } else {
                            getCompleteAddress(addressId, addressElements, addressList);
                        }
                    });
                }
            })
        } else {
            return;
        }
    }

    function getAddresses(streetInput, addressList, origin, containerId = null)
    {
        $(addressList).empty();
        const params = {'text': $(streetInput).val(), 'origin': origin, 'containerId': containerId}
        const captureUrl = loqateFindUrl + '?' +  $.param(params);
        jQuery.ajax({
            type: "GET",
            url: captureUrl,
            showLoader: true,
            crossDomain: true,
            success: function (response) {
                if (response.error && response.message) {
                    displayError(response.message, addressList);
                } else {
                    handleFindApiResponse(response, addressList);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                displayError(thrownError, addressList);
            }
        });
    }

    function getCompleteAddress(addressId, addressElements, addressList)
    {
        $(addressList).empty();
        const params = {'address_id': addressId}
        const captureUrl = loqateRetrieveUrl + '?' + $.param(params);
        jQuery.ajax({
            type: "GET",
            url: captureUrl,
            showLoader: true,
            success: function (response) {
                if (response.error && response.message) {
                    displayError(response.message, addressList);
                } else {
                    handleRetrieveApiResponse(response, addressElements);
                }
            },
            error: function (xhr, ajaxOptions, thrownError) {
                displayError(thrownError, addressList);
            }
        });
    }

    function handleFindApiResponse(response, addressList)
    {
        if (Array.isArray(response)) {
            response.forEach(function (item) {
                let addressItem = $("<div class='loqate-address-item' data-id='" + item.Id + "' data-type='" + item.Type + "'>" + item.Text + (item.Description ? item.Description : "") + "</div>");
                $(addressItem).appendTo($(addressList));
            });
        }
    }

    function handleRetrieveApiResponse(response, addressElements)
    {
        if (Array.isArray(response)) {
            const autofillAddress = response[0];
            var evt = new Event("change", {bubbles: false, cancelable: true});

            $.each(addressMapping, function (key, val) {
                if (key === 'county') {
                    $(addressElements[key]['input']).val(autofillAddress[val]).change().get(0).dispatchEvent(evt);
                    var countyField = $(addressElements[key]['list']);

                    if ($(countyField).length) {
                        $(countyField).find("option").filter(function(){
                            var region1 = ($(this).text()).normalize('NFD').replace(/\p{Diacritic}|(-)|(\s)/gu, "");
                            var region2 = (autofillAddress[val]).normalize('NFD').replace(/\p{Diacritic}|(-)|(\s)/gu, "");

                            return (($(this).val() === autofillAddress[val]) || (region1.toLowerCase() === region2.toLowerCase()));
                        }).prop('selected', true);

                        $(countyField).change().get(0).dispatchEvent(evt);
                    }

                    return;
                }
                if (addressElements[key].length) {
                    $(addressElements[key]).val(autofillAddress[val]).change().get(0).dispatchEvent(evt);
                }
            });
        }
    }

    function displayError(message, container) {
        if (!message) {
            message = 'Unknown server error';
        }

        const errorElem = $("<div class='loqate-error-item message error'>" + message + "</div>")
        $(errorElem).appendTo($(container));
    }

    function closeAllLists(elem)
    {
        $('.loqate-autocomplete-items').each(function () {
            if (!$(this).is(elem)) {
                $(this).empty();
            }
        });
    }
});
