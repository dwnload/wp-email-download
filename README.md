# Email Download #

### Allow users to download any WordPress managed file if they're subscribed to you MailChimp list

**Contributors:** [thefrosty](https://github.com/thefrosty)  
**Tags**: wordpress-plugin, downloader, email-subscription, mailchimp, wp-api  
**Requires at least:** 4.7  
**Tested up to**: 4.8  
**Stable tag**: master  
**License**: GPLv2 or later  
**License URI**: http://www.gnu.org/licenses/gpl-2.0.html  

Mange downloads via WordPress' media manager and the Email Download shortcode which requires 
a users to currently be subscribed to your MailChimp mailing list designated in the shortcod
attribute.

#### Shortcode

The current shortcode expects two attributes to be set; `list-id=(string)` and `file=(int)`
where `list-id` is the ID of your MailChimp list (which you can get from the admin settings 
page or the shortcode builder) and the `file` which is the attachment ID from WordPress' media
manager (again you can get this from the media screen, the post object media manager or the 
shortcode builder).

**Example shortcode**:

```html
[email_to_download list-id="uo063u0837" file="4" /]
```