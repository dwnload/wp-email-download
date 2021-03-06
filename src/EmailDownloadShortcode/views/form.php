<?php
/** @var Api $api */
use Dwnload\WpEmailDownload\Api\Api;
use Dwnload\WpEmailDownload\Api\SubscriptionController;
use Dwnload\WpEmailDownload\Api\Mailchimp;
use Dwnload\WpEmailDownload\EmailDownloadShortcode\Handler;

if ( ! ( $this instanceof Handler ) ) {
    wp_die( __( 'Cheatin&#8217; uh?' ) );
}

?>
<div class="EmailDownload__wrapper clearfix">
    <section>

        <div class="EmailDownload__inner">

            <div class="EmailDownload__notice" style="display: none"><p></p></div>

            <form class="EmailDownload__form"
                  action="" method="post">

                <div class="EmailDownload__group">
                    <input name="email" class="EmailDownload__input" id="EmailDownload__field-email"
                           onfocus="if ( this.placeholder === 'Email Address') { this.placeholder = ''; }"
                           onblur="if ( this.placeholder === '' ) { this.placeholder = 'Email Address'; }"
                           onkeyup="this.setAttribute('value', this.value);"
                           type="email"
                           placeholder="Email Address"
                           value=""
                           required>
                    <label for="EmailDownload__field-email" class="EmailDownload__label">Enter your email
                        address</label>
                    <div class="EmailDownload__description">Enter the email address you used to signup for my mailing
                        list.
                    </div>
                </div>

                <?php wp_nonce_field( SubscriptionController::NONCE_ACTION ); ?>

                <input name="<?php echo Mailchimp::LIST_ID; ?>"
                       type="hidden"
                       value="<?php echo $api->encrypt(
                           $this->getAttribute( Handler::ATTRIBUTE_LIST_ID )
                       ); ?>">

                <input name="<?php echo SubscriptionController::DOWNLOAD_KEY; ?>"
                       type="hidden"
                       value="<?php echo $api->encrypt(
                           $this->getAttribute( Handler::ATTRIBUTE_FILE ),
                           $api->getComputerId()
                       ); ?>">

                <button class="EmailDownload__button">
                    Download
                </button>
            </form>
        </div>
    </section>
</div>
