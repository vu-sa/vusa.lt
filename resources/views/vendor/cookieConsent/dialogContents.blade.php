<div class="js-cookie-consent cookie-consent">

    <p class="cookie-consent__message">
        {!! trans('cookieConsent::texts.message') !!}
    </p>

    <button class="js-cookie-consent-agree cookie-consent__agree">
        {{ trans('cookieConsent::texts.agree') }}
    </button>

	<button class="cookie-privacy-policy">
		<a href="/privatumas" style="color:white;">{{ trans('cookieConsent::texts.privacy_policy') }}</a>
	</button>

</div>
