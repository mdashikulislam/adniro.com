(function () {
	"use strict";
	
	/*
	// Preloader
	window.addEventListener('load', function () {
		const ellipsis = document.querySelector('.lds-ellipsis');
		const preloader = document.querySelector('.preloader');
		const body = document.body;
		
		if (ellipsis) {
			ellipsis.style.transition = 'opacity 0.3s';
			ellipsis.style.opacity = '0';
			ellipsis.addEventListener('transitionend', () => ellipsis.style.display = 'none');
		}
		
		if (preloader) {
			setTimeout(() => {
				preloader.style.transition = 'opacity 0.3s';
				preloader.style.opacity = '0';
				preloader.addEventListener('transitionend', () => preloader.style.display = 'none');
			}, 333);
		}
		
		if (body) {
			setTimeout(() => {
				// No specific action needed for body delay in vanilla JS
			}, 333);
		}
	});
	*/
	
	// YouTube video to autoplay in modal
	let videoSrc;
	const videoButtons = document.querySelectorAll('.video-btn');
	videoButtons.forEach(button => {
		button.addEventListener('click', function () {
			videoSrc = this.dataset.src;
		});
	});
	
	const videoModal = document.getElementById('videoModal');
	const video = document.getElementById('video');
	
	if (videoModal) {
		videoModal.addEventListener('shown.bs.modal', function () {
			if (video && videoSrc) {
				video.setAttribute('src', `${videoSrc}?autoplay=1&modestbranding=1&showinfo=0&rel=0`);
			}
		});
		
		videoModal.addEventListener('hide.bs.modal', function () {
			if (video && videoSrc) {
				video.setAttribute('src', videoSrc);
			}
		});
	}
	
	// Tooltips (this remains the same as it was already vanilla JS using Bootstrap)
	const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
	const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
		return new bootstrap.Tooltip(tooltipTriggerEl);
	});
	
})();
