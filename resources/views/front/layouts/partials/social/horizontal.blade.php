@if (isSocialSharesEnabled())
	@php
		// adniro: $socialIconStyle "plain" gives each network's glyph without its square, for pages that draw
		// their own button around it (the sitemap page); any other value keeps the square icons
		$usePlainIcons = (($socialIconStyle ?? 'square') === 'plain');
		
		$socialMedias = [
			'facebook' => [
				'name'      => 'Facebook',
				'iconClass' => 'fa-brands fa-square-facebook',
				'plainIcon' => 'fa-brands fa-facebook-f',
				'linkClass' => 'facebook',
			],
			'twitter' => [
				'name'      => 'X (Twitter)',
				'iconClass' => 'fa-brands fa-square-x-twitter',
				'plainIcon' => 'fa-brands fa-x-twitter',
				'linkClass' => 'x-twitter',
			],
			'linkedin' => [
				'name'      => 'LinkedIn',
				'iconClass' => 'fa-brands fa-linkedin',
				'plainIcon' => 'fa-brands fa-linkedin-in',
				'linkClass' => 'linkedin',
			],
			'whatsapp' => [
				'name'      => 'WhatsApp',
				'iconClass' => 'fa-brands fa-square-whatsapp',
				'plainIcon' => 'fa-brands fa-whatsapp',
				'linkClass' => 'whatsapp',
			],
			'telegram' => [
				'name'      => 'Telegram',
				'iconClass' => 'fa-brands fa-telegram',
				'plainIcon' => 'fa-brands fa-telegram',
				'linkClass' => 'telegram',
			],
			'snapchat' => [
				'name'      => 'Snapchat',
				'iconClass' => 'fa-brands fa-square-snapchat',
				'plainIcon' => 'fa-brands fa-snapchat',
				'linkClass' => 'snapchat',
			],
			'messenger' => [
				'name'      => 'Facebook Messenger',
				'iconClass' => 'fa-brands fa-facebook-messenger',
				'plainIcon' => 'fa-brands fa-facebook-messenger',
				'linkClass' => 'messenger',
				'data'      => [
					'fb-app-id' => config('settings.social_share.facebook_app_id'),
				],
			],
			'pinterest' => [
				'name'      => 'Pinterest',
				'iconClass' => 'fa-brands fa-square-pinterest',
				'plainIcon' => 'fa-brands fa-pinterest-p',
				'linkClass' => 'pinterest',
			],
			'vk' => [
				'name'      => 'VK (VKontakte)',
				'iconClass' => 'fa-brands fa-vk',
				'plainIcon' => 'fa-brands fa-vk',
				'linkClass' => 'vk',
			],
			'tumblr' => [
				'name'      => 'Tumblr',
				'iconClass' => 'fa-brands fa-square-tumblr',
				'plainIcon' => 'fa-brands fa-tumblr',
				'linkClass' => 'tumblr',
			],
		];
	@endphp
	<div class="social-media social-share text-center my-4 mx-0">
		<span class="text-secondary text-opacity-25" data-bs-toggle="tooltip" title="{{ t('share_on_social_media') }}">
			<i class="fa-solid fa-share"></i>
		</span>
		@foreach($socialMedias as $key => $item)
			@php
				$name = $item['name'] ?? '--';
				$iconClass = ($usePlainIcons ? ($item['plainIcon'] ?? null) : null) ?? $item['iconClass'] ?? '';
				$linkClass = $item['linkClass'] ?? '';
				
				$data = $item['data'] ?? [];
				$dataAttr = '';
				if (!empty($data) && is_array($data)) {
					$dataAttr .= ' ';
					$dataAttr .= collect($data)
						->map(fn ($v, $k) => 'data-' . $k . '="' . $v . '"')
						->join(' ');
				}
			@endphp
			@if (isSocialSharesEnabled($key))
				<a class="{{ $linkClass }} text-decoration-none" title="{{ t('share_on', ['media' => $name]) }}"{!! $dataAttr !!}>
					<i class="{{ $iconClass }}"></i>
				</a>
			@endif
		@endforeach
	</div>
@endif
