/**
 * Created by Devonne<devonne@callpay.com> on 2016/11/29.
 */

var eftSec = eftSec || {};
eftSec.checkout = {
    frame : null,
    frameReady: false,
    settings: {
        serviceUrl: '{protocol}://paystack.callpay.com/eft',
        theme: 'generic',
        primaryColor: null,
        secondaryColor: null,
        token: null,
        amount: null,
        organisation_id: null,
        reference: null,
        onLoad : function() { console.log('Load Completed'); },
        onComplete: function(data) { eftSec.checkout.hideFrame(); console.log('Transaction Completed'); console.log(data) },
    },
    getServiceUrl : function() {
        var proto = (location.protocol != 'https:') ? 'http': 'https';
        var url = this.settings.serviceUrl.replace('{protocol}',proto);
        url = url +'?checkout=1';
        if (this.settings.theme != null) {
            url = url + '&theme='+encodeURIComponent(this.settings.theme);
        }
        if (this.settings.primaryColor != null) {
            url = url + '&primary-color='+encodeURIComponent(this.settings.primaryColor );
        }
        if (this.settings.secondaryColor != null) {
            url = url + '&secondary-color='+encodeURIComponent(this.settings.secondaryColor );
        }
        return url;
    },
    hideFrame: function() {
        if (document.contains(document.getElementById("paystack_checkout_app"))) {
            document.getElementById("paystack_checkout_app").remove();
        }
        this.frame.style.display = 'none';
        this.frame = null;
    },
    showFrame: function() {
        cssText = "z-index: 2147483647;\nbackground: transparent; display:block !important;\nbackground: rgba(0,0,0,0.005);\nborder: 0px none transparent;\noverflow-x: hidden;\noverflow-y: auto;\nmargin: 0;\npadding: 0;\n-webkit-tap-highlight-color: transparent;\n-webkit-touch-callout: none;";
        cssText += "position: absolute; top:0; left:0;\nwidth:100%;\nheight: 100%; position:fixed;"
        this.frame.style.cssText  = cssText;
    },
    createFrame: function() {
        var cssText, iframe;
        iframe = document.createElement("iframe");
        iframe.setAttribute("frameBorder", "0");
        iframe.setAttribute("allowtransparency", "true");
        cssText = "z-index: 2147483647;\nbackground: transparent; display:none;\nbackground: rgba(0,0,0,0.005);\nborder: 0px none transparent;\noverflow-x: hidden;\noverflow-y: auto;\nmargin: 0;\npadding: 0;\n-webkit-tap-highlight-color: transparent;\n-webkit-touch-callout: none;";
        cssText += "position: absolute; top:0; left:0;\nwidth:100%;\nheight: 100%; position:fixed;"
        iframe.style.cssText = cssText;
        iframe.onload = function (){
            eftSec.checkout.frame = document.getElementById('paystack_checkout_app');
            eftSec.checkout.showFrame();
            eftSec.checkout.settings.onLoad();
        };
        iframe.className = iframe.name = "paystack_checkout_app";
        iframe.id = 'paystack_checkout_app';
        document.body.appendChild(iframe);

        var form = document.createElement("form");
        var elToken = document.createElement("INPUT");
        elToken.name="token"
        elToken.value = eftSec.checkout.settings.token;
        elToken.type = 'hidden';
        var elAmount = document.createElement("INPUT");
        elAmount.name="amount"
        elAmount.value = eftSec.checkout.settings.amount;
        elAmount.type = 'hidden';
        var elReference = document.createElement("INPUT");
        elReference.name="merchant_reference"
        elReference.value = eftSec.checkout.settings.reference;
        elReference.type = 'hidden'
        var elOrganisation = document.createElement("INPUT");
        elOrganisation.name="organisation_id"
        elOrganisation.value = eftSec.checkout.settings.organisation_id;
        elOrganisation.type = 'hidden'

        form.method = "POST";
        form.target = "paystack_checkout_app";
        form.action = this.getServiceUrl();
        form.id = "paystack_checkout_form";

        form.appendChild(elToken);
        form.appendChild(elAmount);
        form.appendChild(elReference);
        form.appendChild(elOrganisation);

        document.body.appendChild(form);

        form.submit();

        setTimeout(function() {
            var form = document.getElementById("paystack_checkout_form");
            form.outerHTML = "";
            delete form;
        },2000);

        this.frameReady = true;
        this.frame = document.getElementById('paystack_checkout_app');
        return iframe;
    },
    validate: function() {
        if (eftSec.checkout.settings.token == null) {
            console.error('EftSecure Token Required for Processing');
            return false;
        }
        if (eftSec.checkout.settings.reference == null) {
            console.error('EftSecure Reference Required for Processing');
            return false;
        }
        if (eftSec.checkout.settings.amount == null) {
            console.error('EftSecure Amount Required for Processing');
            return false;
        }

        return true;
    },
    init : function() {
        if (arguments[0] && typeof arguments[0] === "object") {
            eftSec.checkout.extendSettings(arguments[0]);
        }

        if (eftSec.checkout.validate()) {
            eftSec.checkout.frameReady = false;
            if (!eftSec.checkout.frameReady) {
                eftSec.checkout.createFrame();
            }
            else {
                eftSec.checkout.showFrame();
                eftSec.checkout.settings.onLoad();
            }
        }
    },
    /**
     * Utility method to extend defaults with user options
     */
    extendSettings: function (extendedSettings) {
        for (setting in extendedSettings) {
            if (extendedSettings.hasOwnProperty(setting) && extendedSettings[setting] !== '') {
                eftSec.checkout.settings[setting] = extendedSettings[setting];
            }
        }
    }
}


