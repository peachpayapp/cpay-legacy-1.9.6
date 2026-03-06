<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<style type="text/css">
		/* Google Font */
		@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap');

		body, html {
			height: 100%;
			margin: 0;
			background: #F7F9FC;
			font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", "Helvetica Neue", Arial, sans-serif;
		}
		.navbar {
			margin-left: -23px;
			width: calc(100% + 10px);
			padding: 0.875rem;
			background: #0D2743;
			box-shadow: 0 0 2rem 0 rgba(41, 48, 66, 0.1);
		}
		.d-block {
			display: block;
		}
		.d-flex {
		  display: flex !important;
		}
		.d-grid {
		  display: grid !important;
		}
		.mx-auto {
		  margin-right: auto !important;
		  margin-left: auto !important;
		}
		.mt-6 {
		  margin-top: 2rem !important;
		}
		.mb-3 {
		  margin-bottom: 1rem !important;
		}
		.align-middle {
		  vertical-align: middle !important;
		}
		.flex-column {
		  flex-direction: column !important;
		}
		.justify-content-between {
			justify-content: space-between !important;
		}
		.container {
			padding-top: 20px;
		  max-width: 85%
		}
		.card {
		  flex: 1;
		  padding: 1.25rem;
		  margin-bottom: 24px;
		  background: #fff;
		  box-shadow: 0px 2px 10px rgba(0, 0, 0, 0.1);
		  border-radius: 6px;
		  border: none;
		}
		.card-body {
		  flex: 1 1 auto;
		  padding: 1.25rem;
		  min-height: 140px;
		}
		h1, .h1 {
		  font-size: 1.85625rem;
		  margin-top: 0;
		  margin-bottom: 0.5rem;
		  font-weight: 600;
		  line-height: 1.2;
		  color: #495057;
		}
		label {
		  display: inline-block;
		}
		.form-label {
			margin-bottom: 0.5rem;
		  color: #000;
		  font-size: 0.875rem;
		  line-height: 1.25rem;
		  font-weight: 600;
		  gap: 6px;
		}
		input.form-control {
		  border: 1px solid #DAD9DE;
		  box-shadow: 0px 1px 2px 0px rgba(0, 0, 0, 0.05);
		  border-radius: 4px;
		  padding: 0 13px;
		  height: 44px;
		  width: 100%;
		  outline: none;
		  line-height: 24.7px;
		}
		.form-control-lg {
		  min-height: calc(2.24375rem + 2px);
		  padding: 0.35rem 1rem;
		  font-size: 0.95rem;
		  border-radius: 0.3rem;
		}
		.form-control {
		  display: block;
		  width: 100%;
		  padding: 0.25rem 0.7rem;
		  font-size: 0.825rem;
		  font-weight: 400;
		  line-height: 1.625;
		  color: #495057;
		  background-color: #fff;
		  background-clip: padding-box;
		  border: 1px solid #DAD9DE;
		  appearance: none;
		  border-radius: 0.2rem;
		  transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
		}
		.btn {
		  padding: 9px 17px;
		  font-weight: 600;
		  font-size: 13px;
		  border-radius: 3.2px;
		}
		.btn.btn-link {
		  padding: 0;
		  font-weight: 400;
		  text-decoration: underline;
		}
		.btn.btn-link.btn-secondary {
		  background: transparent;
		  color: #0D2743;
		}
		.btn.btn-link.btn-secondary:hover {
		  color: #FF6A5B;
		}
		.btn-primary {
			color: #fff;
		  text-transform: uppercase;
		  background: #FF6A5B;
		  border: none;
		  font-weight: 600;
		  line-height: 21.45px;
		  cursor: pointer;
		}
		.btn-primary:hover {
			background: #ff8074;
		}
		#cpay-auth {
			display: none;
		}

		/* Loader */
		.loader {
		  animation: rotate 2s linear infinite;
		  z-index: 2;
		  position: absolute;
		  top: 50%;
		  left: 50%;
		  margin: -25px 0 0 -25px;
		  width: 50px;
		  height: 50px;
		}
		  
	  .loader .path {
	    stroke: #FF6A5B;
	    animation: dash 1.5s ease-in-out infinite;
	  }

		@keyframes rotate {
		  100% {
		    transform: rotate(360deg);
		  }
		}

		@keyframes dash {
		  0% {
		    stroke-dasharray: 1, 150;
		    stroke-dashoffset: 0;
		  }
		  50% {
		    stroke-dasharray: 90, 150;
		    stroke-dashoffset: -35;
		  }
		  100% {
		    stroke-dasharray: 90, 150;
		    stroke-dashoffset: -124;
		  }
		}

		/* WP Thickbox Overrides */
		#TB_ajaxContent {
			background: #F7F9FC;
		}
	</style>
</head>
<body>
	<nav class="navbar">
		<svg width="71" height="31" viewBox="0 0 71 31" fill="none" xmlns="http://www.w3.org/2000/svg" class="d-block align-middle mx-auto">
			<path d="M10.8891 3.229L0 9.4883V21.9743L10.8891 28.2336L21.7782 21.9743V9.4883L10.8891 3.229ZM16.3992 11.7051L10.8891 14.8022L5.44455 11.6725L10.8891 8.54289L16.3992 11.7051ZM4.78858 12.7157L10.2659 15.878V22.2351L4.78858 19.0728V12.7157ZM11.5123 22.2351V15.878L16.9896 12.7157V19.0728L11.5123 22.2351Z" fill="#FF6A5B"></path>
			<path d="M32.9707 23H29.9716V5.9024H37.4822C41.0709 5.9024 43.0704 8.36322 43.0704 11.2598C43.0704 14.1564 41.0453 16.6172 37.4822 16.6172H32.9707V23ZM37.0721 13.977C38.7639 13.977 39.9943 12.9004 39.9943 11.2598C39.9943 9.61927 38.7639 8.54266 37.0721 8.54266H32.9707V13.977H37.0721ZM55.2429 23H52.5514V21.6671C51.6286 22.718 50.1931 23.3076 48.5525 23.3076C46.5275 23.3076 44.2461 21.949 44.2461 19.2062C44.2461 16.3609 46.5275 15.1818 48.5525 15.1818C50.2187 15.1818 51.6542 15.7201 52.5514 16.771V14.9254C52.5514 13.4899 51.3722 12.6184 49.6548 12.6184C48.2706 12.6184 47.0658 13.1311 45.9892 14.182L44.8869 12.3108C46.3224 10.9522 48.1168 10.3114 50.0906 10.3114C52.8333 10.3114 55.2429 11.4649 55.2429 14.7973V23ZM49.6035 21.462C50.7827 21.462 51.9362 21.0262 52.5514 20.1803V18.3091C51.9362 17.4631 50.7827 17.0274 49.6035 17.0274C48.0911 17.0274 46.9633 17.8989 46.9633 19.2575C46.9633 20.5904 48.0911 21.462 49.6035 21.462ZM58.1942 27.8447L58.6043 25.4352C58.8863 25.5634 59.322 25.6403 59.6297 25.6403C60.4756 25.6403 61.0395 25.3839 61.3984 24.5636L62.0136 23.1538L56.9381 10.619H59.8091L63.4234 19.8983L67.0378 10.619H69.9344L63.9617 25.2301C63.1158 27.3321 61.6291 27.9985 59.7066 28.0242C59.322 28.0242 58.5787 27.9473 58.1942 27.8447Z" fill="white"></path>
		</svg>
	</nav>

	<div class="d-flex flex-column container mx-auto">
		<div class="card">
			<div class="card-body">
				<div id="cpay-loader" style="display: none;">
					<svg class="loader" viewBox="0 0 50 50">
					  <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
					</svg>
				</div>

				<div id="cpay-connect">
					<h1>Connect ConvesioPay</h1>
					<div class="mb-3">
						<p>Login with your ConvesioPay account credentials to connect your account API key to this store.</p>
					</div>
					<div class="mb-3">
						<label class="form-label">Email Address</label>
						<input id="connect-email" name="email" type="email" class="form-control form-control-lg">
					</div>
					<div id="cpay-password-wrapper" class="mb-3">
						<label class="form-label">Password</label>
						<input id="connect-password" name="password" type="password" class="form-control form-control-lg">
					</div>
					<div id="cpay-admin-key-wrapper" class="mb-3" style="display: none;">
						<label class="form-label">Admin Client Key</label>
						<input id="connect-admin-key" name="password" type="password" class="form-control form-control-lg">
					</div>
					<div class="d-flex justify-content-between">
						<a class="btn btn-link btn-secondary" href="https://pay.convesio.com/auth/forgot-password" target="_blank">Forgot password?</a>
						<a id="cpay-admin-key-btn" class="btn btn-link btn-secondary" href="javascript:;">Use Admin Key</a>
					</div>
					<div class="d-grid mt-6">
						<button id="cpay-request-api-key-btn" class="btn btn-primary">Connect Account</button>
					</div>
				</div>

				<div id="cpay-auth" style="display: none;">
					<h1>Authorize Payments</h1>
					<div class="mb-3">
						<p>Good news, your account has been connected and your ConvesioPay API key configured!</p>
						<p>Do you want to authorize this store to make transactions?</p>
					</div>
					<button type="button" class="btn btn-primary mt-6" data-adn-authorization='{"action":"authorize"}'>Click to authorize</button>
				</div>

				<div id="cpay-error" style="display: none;">
					<h1>Unable to Authorize</h1>
						<div class="mb-3">
							<p>Unfortunately, we were unable to authorize this store. Please reach out to <a href="mailto:pay@convesio.com">pay@convesio.com</a> for assistance.</p>
						</div>
				</div>
			</div>
		</div>
	</div>

	<script type="text/javascript">
		(function ($) {
			$(document).ready(function () {
				// Update form for Admin Key
				$('#cpay-admin-key-btn').click(function(e) {
					$('#cpay-admin-key-wrapper').toggle();
					$('#cpay-password-wrapper').toggle();
					$('#cpay-admin-key-btn').text() === 'Use Admin Key' ? $('#cpay-admin-key-btn').text('Use Form') : $('#cpay-admin-key-btn').text('Use Admin Key');
				});

				// Connect the store
				$('#cpay-request-api-key-btn').click(function(e) {
					var cpayApiRequestUrl = $('#cpay-connect-account-btn').data('cpayApiRequestUrl');
					var cpayRequestingStoreUrl = $('#cpay-connect-account-btn').data('cpayRequestingStoreUrl');
					var cpayRequestingStoreWebhook = $('#cpay-connect-account-btn').data('cpayRequestingStoreWebhook');

					// Show loader
					$('#cpay-loader').show();
					$('#cpay-connect').hide();

					$.ajax({
						type: 'POST',
						dataType: 'json',
						url: '/wp-admin/admin-ajax.php',
						data: {
							action:'cpay_authorize',
							email: $('#connect-email').val(),
							password: $('#connect-password').val(),
							requestingStoreUrl: cpayRequestingStoreUrl,
							requestingStoreWebhook: cpayRequestingStoreWebhook,
							adminClientKey: $('#connect-admin-key').val()
						},
						success: function(res) {
							if (res.success) {
	 							$('#cpay-auth').show();
	 						} else {
	 							$('#cpay-error p').html('Unfortunately, we were unable to authorize this store. ' + res.data.message + ' Please reach out to <a href="mailto:pay@convesio.com">pay@convesio.com</a> for assistance.');
	 							$('#cpay-error').show();
	 						}
	 						$('#cpay-loader').hide();
						},
						error: function(error) {
							$('#cpay-loader').hide();
 							$('#cpay-error').show();
						}
					});
				});
			});
		})(jQuery);
	</script>
</body>
</html>