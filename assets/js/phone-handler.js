document.addEventListener("DOMContentLoaded", function () {
    const sendBtn = document.querySelector("#send_otp_button");
    const phoneInput = document.querySelector("#reg_billing_phone");
    const otpStatus = document.getElementById("otp_status");
    const countryCodeInput = document.getElementById("reg_billing_country_code");

    if (!sendBtn || !phoneInput || !otpStatus || !countryCodeInput) return;

    const iti = window.intlTelInput(phoneInput, {
        separateDialCode: true,  // Display the country code separately
        initialCountry: "auto",
        geoIpLookup: function(callback) {
            let ipinfoToken = wc_sms_ajax.ipinfo_api_key;
            let ipinfoUrl = "https://ipinfo.io/?token=" + ipinfoToken;

            fetch(ipinfoUrl, { mode: 'cors' })
                .then(res => res.json())
                .then(data => callback(data.country))
                .catch(() => callback("us")); // Default to US if lookup fails
        },
        utilsScript: "https://cdn.jsdelivr.net/npm/intl-tel-input@17/build/js/utils.js",
    });

    // Set country code on init.
    countryCodeInput.value = iti.getSelectedCountryData().dialCode;


    sendBtn.addEventListener("click", function () {
        if (!iti.isValidNumber()) {
            otpStatus.innerText = "Invalid phone number.";
            return;
        }

        const countryCode = iti.getSelectedCountryData().dialCode;
        let phoneNumber = iti.getNumber(intlTelInputUtils.numberFormat.E164);
        phoneNumber = phoneNumber.replace('+', '');  // Remove the plus sign
        const fullPhone = phoneNumber.replace(/\D/g, '');  // Remove non-digit characters


        countryCodeInput.value = countryCode; // Store the country code
        phoneInput.value = fullPhone; // Store the phone number
        otpStatus.innerText = "Sending OTP...";

        fetch(wc_sms_ajax.ajax_url, {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: new URLSearchParams({
                action: "send_otp_sms",
                security: wc_sms_ajax.nonce,
                phone: fullPhone,
                country_code: countryCode
            })
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                otpStatus.innerText = "OTP sent successfully.";
            } else {
                otpStatus.innerText = "Error: " + response.data;
            }
        });
    });


    phoneInput.addEventListener("countrychange", function() {
        countryCodeInput.value = iti.getSelectedCountryData().dialCode;
    });
});