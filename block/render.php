<?php
/**
 * Render callback for the Mailchimp Subscription Form block
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

$list_id = isset( $attributes['listId'] ) ? esc_attr( $attributes['listId'] ) : '';
$list_name = isset( $attributes['listName'] ) ? esc_html( $attributes['listName'] ) : '';
$attachment_id = isset( $attributes['attachmentId'] ) ? absint( $attributes['attachmentId'] ) : 0;

// Don't render if required attributes are missing
if ( empty( $list_id ) || empty( $attachment_id ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array(
	'class' => 'mailchimp-subscription-block'
) );

?>
<div <?php echo $wrapper_attributes; ?>>
	<form class="subscription-form" method="post" aria-label="<?php esc_attr_e( 'Email subscription form', 'email-download' ); ?>">
		<h3 class="form-title"><?php esc_html_e( 'Subscribe to our newsletter', 'email-download' ); ?></h3>
		
		<div class="form-group">
			<label for="email-<?php echo esc_attr( $list_id ); ?>">
				<?php esc_html_e( 'Email Address', 'email-download' ); ?>
				<span aria-label="<?php esc_attr_e( 'required', 'email-download' ); ?>">*</span>
			</label>
			<input 
				type="email" 
				id="email-<?php echo esc_attr( $list_id ); ?>"
				name="email"
				placeholder="<?php esc_attr_e( 'Enter your email address', 'email-download' ); ?>"
				required
				aria-required="true"
			/>
		</div>
		
		<input type="hidden" name="list_id" value="<?php echo esc_attr( $list_id ); ?>" />
		<input type="hidden" name="attachment_id" value="<?php echo esc_attr( $attachment_id ); ?>" />
		
		<button type="submit" class="submit-button">
			<?php esc_html_e( 'Subscribe', 'email-download' ); ?>
		</button>
	</form>
</div>
