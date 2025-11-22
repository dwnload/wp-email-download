/**
 * Frontend JavaScript for the Mailchimp Subscription Form block
 */

document.addEventListener('DOMContentLoaded', function() {
	const forms = document.querySelectorAll('.wp-block-telex-mailchimp-subscription-form form');
	
	forms.forEach(function(form) {
		form.addEventListener('submit', function(e) {
			e.preventDefault();
			
			const emailInput = form.querySelector('input[name="email"]');
			const listIdInput = form.querySelector('input[name="list_id"]');
			const attachmentIdInput = form.querySelector('input[name="attachment_id"]');
			const submitButton = form.querySelector('.submit-button');
			const messageDiv = form.querySelector('.form-message');
			
			// Clear previous messages
			if (messageDiv) {
				messageDiv.remove();
			}
			
			// Validate email
			const email = emailInput.value.trim();
			if (!email || !isValidEmail(email)) {
				showMessage(form, 'Please enter a valid email address.', 'error');
				return;
			}
			
			// Get form data
			const listId = listIdInput.value;
			const attachmentId = attachmentIdInput.value;
			
			// Set loading state
			submitButton.disabled = true;
			submitButton.innerHTML = '<span class="spinner"></span> Subscribing...';
			
			// Prepare request data
			const data = {
				email: email,
				listId: listId,
				attachmentId: parseInt(attachmentId, 10)
			};
			
			// Send AJAX request
			fetch('/wp-json/mailchimp-form/v1/subscribe', {
				method: 'POST',
				headers: {
					'Content-Type': 'application/json',
				},
				body: JSON.stringify(data)
			})
			.then(function(response) {
				if (!response.ok) {
					return response.json().then(function(errorData) {
						throw new Error(errorData.message || 'Subscription failed');
					});
				}
				return response.json();
			})
			.then(function(result) {
				// Success
				showMessage(form, result.message || 'Successfully subscribed!', 'success');
				emailInput.value = '';
				
				// Reset button after delay
				setTimeout(function() {
					submitButton.disabled = false;
					submitButton.textContent = 'Subscribe';
				}, 3000);
			})
			.catch(function(error) {
				// Error
				showMessage(form, error.message || 'An error occurred. Please try again.', 'error');
				
				// Reset button
				submitButton.disabled = false;
				submitButton.textContent = 'Subscribe';
			});
		});
	});
	
	function isValidEmail(email) {
		const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
		return re.test(email);
	}
	
	function showMessage(form, message, type) {
		const messageDiv = document.createElement('div');
		messageDiv.className = 'form-message ' + type;
		messageDiv.textContent = message;
		messageDiv.setAttribute('role', type === 'error' ? 'alert' : 'status');
		messageDiv.setAttribute('aria-live', 'polite');
		
		form.appendChild(messageDiv);
		
		// Remove message after 5 seconds for success
		if (type === 'success') {
			setTimeout(function() {
				messageDiv.remove();
			}, 5000);
		}
	}
});
