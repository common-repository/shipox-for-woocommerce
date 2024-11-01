=== Shipox ===
Contributors: shipox, aurshax
Tags: shipox, woocommerce, shipping, integration, zip24
Requires at least: 5.6
Tested up to: 6.6.2
Stable tag: 3.3.2
Requires PHP: 7.0
License: GPLv3
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Shipox DMS provides you with a complete delivery management software solution for pickup and delivery.

== Description ==

Shipox DMS provides you with a complete delivery management software solution for pickup and delivery. Prioritize and assign your drivers with precision and efficiency.


**Check out our website for more information and news about the plugin and our software:** [shipox.com](https://shipox.com/)
Most innovative mobile and web-based delivery app for businesses and individual consumers.


**Note:** Feature plugin for WooCommerce + Shipox. This plugin gives you opportunity to integrate your WooCommerce orders with Shipox. **

== Getting Started ==

= Minimum Requirements =

* WordPress 4.4 or greater
* WooCommerce 3.0.0 or greater

Visit the [WooCommerce server requirements documentation](https://docs.woocommerce.com/document/server-requirements/) for a detailed list of server requirements.

= Automatic installation =

Automatic installation is the easiest option as WordPress handles the file transfers itself and you don’t need to leave your web browser. To do an automatic install of this plugin, log in to your WordPress dashboard, navigate to the Plugins menu and click Add New.

In the search field type “Shipox” and click Search Plugins. Once you’ve found this plugin you can view details about it such as the point release, rating and description. Most importantly of course, you can install it by simply clicking “Install Now”.

= Manual installation =

The manual installation method involves downloading the plugin and uploading it to your webserver via your favourite FTP application. The WordPress codex contains [instructions on how to do this here](https://codex.wordpress.org/Managing_Plugins#Manual_Plugin_Installation).

== Installation guide ==

= 1.Install =
Install the plugin from plugins store or download it from wordpress.org website. If you downloaded, unzip it and upload the plugin files to the website using dashboard/FTP


= 2. Select your instance =
For Shipox Instance please select instance 1


= 3.Configure =

On the right menu, go to the Shipox Menu.

There are 4 tabs named:
Service Configuration, Merchant Credentials, Merchant Address Details, and Order Settings.

**Service Configuration.**
- **Debug Mode** – It is a TEST or LIVE version of the plugin, No: LIVE version, Yes: TEST version;
- **Auto Push** – It can push the orders to the Shipox, after immediately receiving a new order.

**Merchant Credentials.**
- Enter your Shipox Credentials and Click **Get Token**. After that, you will be notified that you have successfully logged in.
- Then, click the **Save Changes** Button.

Merchant Address Details Tab.
- Fill all fields, all fields are required except Postcode and details.
- Latitude & Longitude Field is important so do not forget to fill it.
- Click the Save Changes Button.

Order Settings Tab
- **International Order Availability** – If your store have an international order, please select Available.
- **Default Weight** – is weight information of the order to push to the Shipox. By default, it calculates the weight of each product of the order.
- **Default Courier Type** – By default selecte one or some needed courier types as default courier type. It will be used on AUTO PUSH
- **Default Payment Option** – It depends on Agreement with Shipox. According to DMS Company Agreement, you need to select Credit Balance or Cash.

Above settings will be considered based on the agreement with DMS Company


= 4. Enable/Disable packages =

If you want to disable packages from Shipox on your Checkout Page. Go to Woocommerce -> Settings -> Shipping -> Shipox. Enable/Disable to show the Checkout Page.


= 5. Orders Page =

- To push the order to the Shipox (when AutoPush disabled or didn’t push the order automatically for some reasons) you can select Ship with Shipox Status and save the order
- After that, any response can be shown on the Order Notes Section



== Frequently Asked Questions ==

= Where can I report bugs? =

Report bugs on the shipox website contact form [Shipox website](https://shipox.com/) or please email us on support@shipox.com or info@shipox.com. You can also notify us via our support forum – be sure to search the forums to confirm that the error has not already been reported.

= Where can I find information about plugin author? =

You can find all information and contact details on [shipox.com](https://shipox.com/) official website.
About Shipox Software [About page](https://shipox.com/about-shipox-software/)

= How do I know if I got token? =

You will get success message and the Default Courier Type field will have values, if not: Make sure that credentials you were provided are right. Make sure that this account in proper instance (Test mode or not). Ask from provider company manager to check login\password.

= How should I fill latitude\longitude field? =

Separate by comma without spaces; fill this field like **{latitude},{longitude}**.

= When I check Allowed Payment type as Credit balance, I have error Credit balance is not activated, what should I do? =

Ask your provider company managers to activate **Credit balance** for your account.

= I have empty Default Service Types field, is it required field? =

Yes, it is required field, Service types are packages for delivery, they have estimate price. There can be several reasons why you don’t have any service types field values:

- Your credentials may be wrong;
- In provider system you don’t have any packages or there are rules that conflict with your configurations.

= What if I want to create Shipox order manually in administrator dashboard of my CMS? =

In Shipox plugin settings find Service Configuration tab and select off in Auto push field. Then you will have Create Shipox order button in order edit page.

= I get response “Package price not found”, what am I doing wrong? =

If you get package price not found error:
-Check package rules in Shipox dashboard if you have any package rules.
-If package rules are created check if conditions do not conflict with your request locations or customer account.

= I want to add some custom features to my CMS with the help of your system, how can I achieve it? =

Check our Merchant [API Documentation](https://shipox.docs.apiary.io/) here.

= What should I do if I have still questions? =

If you have still question or feedback feel free to submit our contact form on [shipox.com](https://shipox.com/)


== Screenshots ==

1. Service Configuration Tab
2. Merchant Credentials Tab
3. Merchant Address Details Tab
4. Order Settings Tab
5. Enable/Disable Settings
6. Orders Page Statuses
