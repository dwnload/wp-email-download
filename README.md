# Email Download #

[![PHP from Packagist](https://img.shields.io/packagist/php-v/dwnload/wp-email-download.svg)]()
[![Latest Stable Version](https://img.shields.io/packagist/v/dwnload/wp-email-download.svg)](https://packagist.org/packages/dwnload/wp-email-download)
[![Total Downloads](https://img.shields.io/packagist/dt/dwnload/wp-email-download.svg)](https://packagist.org/packages/dwnload/wp-email-download)
[![License](https://img.shields.io/packagist/l/dwnload/wp-email-download.svg)](https://packagist.org/packages/dwnload/wp-email-download)
[![Build Status](https://travis-ci.org/dwnload/wp-email-download.svg?branch=master)](https://travis-ci.org/dwnload/wp-email-download)

### Allow users to download any WordPress managed file if they're subscribed to your MailChimp list

**Contributors:** [thefrosty](https://github.com/thefrosty)  
**Tags**: wordpress-plugin, downloader, email-subscription, mailchimp, wp-api  
**Requires at least:** 5.7  
**Tested up to**: 5.8  
**Requires PHP**: 7.4.0  
**Stable tag**: master  
**License**: GPLv2 or later  
**License URI**: http://www.gnu.org/licenses/gpl-2.0.html

Mange downloads via WordPress' media manager, and the Email Download shortcode which requires a users to currently be
subscribed to your MailChimp mailing list designated in the shortcode attribute.

#### Shortcode

The current shortcode expects two attributes to be set; `list-id=(string)` and `file=(int)`
where `list-id` is the ID of your MailChimp list (which you can get from the admin settings page or the shortcode
builder) and the `file` which is the attachment ID from WordPress' media manager (again you can get this from the media
screen, the post object media manager or the shortcode builder).

**Example shortcode**:

```html
[email_to_download list-id="uo063u0837" file="4" /]
```

### Installation

To install this plugin, using Composer:

```
composer require dwnload/wp-email-download:~0.5
```
