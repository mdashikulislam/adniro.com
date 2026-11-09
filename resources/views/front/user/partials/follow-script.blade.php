{{--
	"Follow" buttons of the sellers (seller's public page, listing page...).
	To be included inside a <script> section. Button's markup:
	<button class="js-follow-btn [is-following]" data-username="..." data-url="..." data-auth="0|1" [data-count-target="#selector"]>
--}}
<script>
	onDocumentReady((event) => {
		const compactNumber = (number) => {
			if (number >= 1000000) return (Math.round(number / 100000) / 10) + 'M';
			if (number >= 1000) return (Math.round(number / 100) / 10) + 'K';
			return String(number);
		};
		
		document.querySelectorAll('.js-follow-btn').forEach((followBtn) => {
			followBtn.addEventListener('click', async (e) => {
				e.preventDefault();
				
				if (followBtn.dataset.auth !== '1') {
					openLoginModal();
					return;
				}
				if (followBtn.disabled) {
					return;
				}
				followBtn.disabled = true;
				
				try {
					const tokenEl = document.querySelector('meta[name="csrf-token"]');
					const response = await fetch(followBtn.dataset.url, {
						method: 'POST',
						credentials: 'same-origin',
						headers: {
							'Content-Type': 'application/json',
							'Accept': 'application/json',
							'X-Requested-With': 'XMLHttpRequest',
							'X-CSRF-TOKEN': tokenEl ? tokenEl.getAttribute('content') : ''
						},
						body: JSON.stringify({username: followBtn.dataset.username})
					});
					
					if (response.status === 401 || response.status === 419) {
						openLoginModal();
						return;
					}
					
					const json = await response.json();
					if (!response.ok) {
						if (json.message) {
							jsAlert(json.message, 'error');
						}
						return;
					}
					
					const isFollowing = (json.isFollowing === true);
					
					{{-- Every button of this seller on the page --}}
					document.querySelectorAll('.js-follow-btn').forEach((btn) => {
						if (btn.dataset.username !== followBtn.dataset.username) {
							return;
						}
						btn.classList.toggle('is-following', isFollowing);
						btn.setAttribute('aria-pressed', isFollowing ? 'true' : 'false');
					});
					
					{{-- Don't show "Unfollow" right after the click, while the pointer is still over the button --}}
					followBtn.classList.add('just-toggled');
					followBtn.addEventListener('mouseleave', () => followBtn.classList.remove('just-toggled'), {once: true});
					
					const countEl = followBtn.dataset.countTarget ? document.querySelector(followBtn.dataset.countTarget) : null;
					if (countEl && typeof json.followers === 'number') {
						countEl.dataset.count = json.followers;
						countEl.textContent = compactNumber(json.followers);
						countEl.setAttribute('title', json.followers.toLocaleString());
						countEl.classList.remove('is-bumped');
						void countEl.offsetWidth;
						countEl.classList.add('is-bumped');
					}
					
					if (json.message) {
						jsAlert(json.message, 'success');
					}
				} catch (error) {
					console.error(error);
				} finally {
					followBtn.disabled = false;
				}
			});
		});
	});
</script>
