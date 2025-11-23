<?php

declare(strict_types=1);

use Dwnload\WpEmailDownload\Api\Api;
use Dwnload\WpEmailDownload\Api\Mailchimp;
use Dwnload\WpEmailDownload\RestApi\SubscriptionController;

/**
 * Render callback for the Mailchimp Subscription Form block
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
$api ??= new Api();
$list_id = isset($attributes['listId']) ? esc_attr($attributes['listId']) : '';
$list_name = isset($attributes['listName']) ? esc_html($attributes['listName']) : '';
$attachment_id = isset($attributes['attachmentId']) ? absint($attributes['attachmentId']) : 0;
$attachment_url = isset($attributes['attachmentUrl']) ? esc_url($attributes['attachmentUrl']) : '';

// Don't render if required attributes are missing
if (empty($list_id) || empty($attachment_id)) {
    return;
}

$wrapper_attributes = get_block_wrapper_attributes([
        'class' => 'EmailDownload__wrapper clearfix mailchimp-subscription-block',
]);

?>
<div <?php
echo $wrapper_attributes; ?>>
    <section>

        <div class="EmailDownload__inner">

            <div class="EmailDownload__notice" style="display: none"></div>

            <form class="EmailDownload__form"
                  action="" method="post" autocomplete="off">

                <div class="EmailDownload__group">
                    <input name="email" class="EmailDownload__input" id="EmailDownload__field-email"
                           onfocus="if ( this.placeholder === 'Email Address') { this.placeholder = ''; }"
                           onblur="if ( this.placeholder === '' ) { this.placeholder = 'Email Address'; }"
                           onkeyup="this.setAttribute('value', this.value);"
                           type="email"
                           placeholder="Email Address"
                           value=""
                           required
                           data-1p-ignore>
                    <label for="EmailDownload__field-email" class="EmailDownload__label">Email address</label>
                    <div class="EmailDownload__description">
                        <?php
                        printf(
                                esc_html__(
                                        'Enter the same email address you used when signing up to the "%s" list.',
                                        'email-download'
                                ),
                                esc_html($list_name)
                        ); ?>
                    </div>
                </div>

                <?php
                wp_nonce_field('wp_rest'); ?>

                <input name="<?php
                echo Mailchimp::LIST_ID; ?>"
                       type="hidden"
                       value="<?php
                       echo $api->encrypt($list_id); ?>">

                <input name="<?php
                echo SubscriptionController::DOWNLOAD_KEY; ?>"
                       type="hidden"
                       value="<?php
                       echo $api->encrypt($api->buildDataForFieldId((string)$attachment_id)); ?>">

                <button type="submit" class="EmailDownload__button">
                    Download
                </button>
            </form>
        </div>
    </section>
</div>
