# Paystack Gateway for Prestashop 1.7

The Paystack Gateway for Prestashop is a module that allows customers to make payments via their card or bank details using Paystack on [Prestashop](https://www.prestashop.com/)

## Installation

1. Download the zip folder for this repository to get the PrestaShop module. Unzip the zip file and zip the folder titled `paystack`. Ignore the `screenshots` and `README.md` file.

2. Zip the Paystack folder contained within. This is the file you will be uploading as the Paystack module.

3. In your PrestaShop back office, click the *Modules* link on the left side menu of your dashboard.
   ![Prestashop Dashboard](/screenshots/prestashop-dashboard.png)

4. Click the *Upload A Module* button. A dialog box will pop up, asking you to upload a file. 

   Drag the paystack.zip file to this dialog box or click 'Select A File' to select from your file picker and choose select paystack.zip.
   ![Prestashop Modules](/screenshots/prestashop-modules.png)
   ![Upload Module](/screenshots/upload-module.png)

   You will receive a success message.
   ![Upload Complet](/screenshots/upload-complete.png)

5. If you are not automatically redirected on successful installation, manually navigate to the Installed Modules tab. Click 'Configure' on the Paystack module.
   ![Paystack Module](/screenshots/paystack-module.png)

6. You will be directed to an interface where you can input your API keys. These keys are available on your Paystack dashboard at <https://dashboard.paystack.co/#/settings/developer>.
   ![Paystack Module Configuration](/screenshots/paystack-module-config.png)

   The Paystack module will work when the Test (Public and Secret) Keys and/or Live (Public and Secret) Keys are inserted.

   Use Test Keys to test payments on your sites without incurring a charge, and use Live Keys when you are ready to start accepting payments from customers.
   
## Documentation
* [Paystack Documentation](https://developers.paystack.co/v2.0/docs/)
* [Paystack Helpdesk](https://paystack.com/help)

## Support
For bug reports and feature requests directly related to this plugin, please use the [issue tracker](https://github.com/PaystackHQ/plugin-prestashop-1.7/issues). 

For general support or questions about your Paystack account, you can reach out by sending a message from [our website](https://paystack.com/contact).

## Community
If you are a developer, please join our Developer Community on [Slack](https://slack.paystack.com).

## Contributing to Payment Forms for Paystack

If you have a patch or have stumbled upon an issue with the Paystack Gateway for Paid Membership Pro plugin, you can contribute this back to the code. Please read our [contributor guidelines](https://github.com/PaystackHQ/plugin-prestashop-1.7/blob/master/CONTRIBUTING.md) for more information how you can do this.
