<?php

use Dwnload\WpEmailDownload\Admin\Settings;
use Dwnload\WpEmailDownload\Api\Mailchimp;

if ( ! ( $this instanceof Settings ) ) {
    wp_die( 'Cheatin&#8217; uh?' );
}

?>
<div class="wrap">
    <h2>Email Download</h2>

    <form method="post">
        <table class="form-table">
            <tbody>
            <tr>
                <th scope="row" valign="top">MailChimp API Key</th>
                <td>
                    <label>
                        <input type="password"
                               class="widefat"
                               name="<?php echo $this->getFieldName( Mailchimp::SETTING_API_KEY ); ?>"
                               value="<?php echo $this->getSetting( Mailchimp::SETTING_API_KEY ); ?>">
                    </label>
                    <span class="description">Enter your MailChimp API Key here.</span>
                </td>
            </tr>
            <?php if ( ! empty( $api_key = $this->getSetting( Mailchimp::SETTING_API_KEY ) ) ) : ?>
                <tr>
                    <th scope="row" valign="top">MailChimp Lists</th>
                    <td>
                        <?php
                        try {
                            echo ( new MailChimp( $api_key ) )->getListsHtml();
                        } catch ( Exception $e ) {
                            echo "<span class=\"description\">{$e->getMessage()}</span>";
                        } ?>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
        <?php submit_button(); ?>
        <?php $this->theNonce(); ?>
    </form>
</div>
