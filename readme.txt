=== Plugin Name ===
Plugin Name: Enquire Gravity Forms
Description: Send form data to the Enquire CRM using Gravity Form's Add-on Framework
version: 0.5
Author: Husky Ninja
Author URI: https://www.husky.ninja
License: GPLv3 or later
Text Domain: enquire-gform
Domain Path: /languages

Sends Gravity Form data to Enquire 2.0 RESTful endpoint.

== Description ==

For use only with Gravity Forms v1.9 or greater.

Most configuration settings are done on a form by form basis (see "Sending a Debug Email" below), and can be found under admin -> Forms -> Forms -> {form name} -> Settings -> Enquire GForm.

Select the "Send this form to Enquire" checkbox to attach the form. You will need an Subscription Key.

The Enquire Endpoint URL may be edited if necessary.

Add any Enquire Community Names under Community Names. If you have more than one, seperate them with a comma. Community Names are provided by Enquire.

By default this plugin uses Remote Post (wp_remote_post) to send form data. This can be changed to to use cURL. If you have cURL installed and wish to use this method, select this checkbox.

To map the form fields, select the relevant Field (to be mapped for Enquire) to the Form Field (from the Gravity Form).

The form field must be of the correct type. The mapping is as follows:

First Name -> name, text or hidden
Last Name -> name, text or hidden
Email Address -> email or hidden
Home Phone -> phone or hidden

So make sure when creating your form that you use the correct form field types for the Emfluence field mapping.

Sending a Debug Email

You can send a debug email for all submissions that contain logging information if you do not have logging enabled. This setting can be found under admin -> Forms -> Settings -> Enquire GForm.

Select "Send a debug email" to enable this feature, and enter a valid email under "Debug email address". This will send an email containing logging information for all forms submitted to Enquire.

== Changelog ==

= 0.5 =
* added placeholder to languages directory
* finally fixed scripts and styles
* restricted phone field mapping to phone or hidden

= 0.4.1 =
* updated laugnauge settings to main config form

= 0.4 =
* added global debug email
* added error reporting for cURL
* improved error reporting & logging for Remote Post

= 0.3 =
* changed default method for posting to Wordpress Remote Post with cURL as a selectable alternative

= 0.2 =
* Typo corrections in UI.
* Test successful. Release for 'live fire' testing.

= 0.1-alpha =
* First buildout.

== Upgrade Notice ==

= 0.0 =
Placeholder.


== Arbitrary section ==

This is arbitrary.