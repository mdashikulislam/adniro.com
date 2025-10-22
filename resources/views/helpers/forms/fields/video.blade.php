{{-- text input --}}
@php
	$layout ??= 'default'; // default, horizontal
	$isHorizontal = $layout === 'horizontal';
	$colLabel ??= 'col-md-3';
    $colField ??= 'col-md-9';
	
	$viewName = 'video';
	$type = 'text';
	$label ??= null;
	$id ??= null;
	$name ??= null;
	$value ??= null;
	$default ??= null;
	$placeholder ??= null;
	$required ??= false;
	$hint ??= null;
	$attributes ??= [];
	
	$dotSepName = arrayFieldToDotNotation($name);
	$id = !empty($id) ? $id : str_replace('.', '-', $dotSepName);
	
	$value = $value ?? ($default ?? null);
	$value = old($name, $value);
	
	$attributes = \App\Helpers\Common\Html\HtmlAttr::append($attributes, 'class', 'video-link');
	
	// If attribute casting is used, convert to JSON
	if (is_array($value)) {
		$value = json_encode((object)$value);
	}
	if (is_object($value)) {
		$value = json_encode($value);
	}
@endphp
<div data-video @include('helpers.forms.attributes.field-wrapper')>
	@include('helpers.forms.partials.label')
	
	@if ($isHorizontal)
		<div class="{{ $colField }}">
			@endif
			
			<input class="video-json" type="hidden" name="{{ $name }}" value="{{ $value }}">
			<div class="input-group">
				<input
						type="text"
						id="{{ $name }}"
						@if (!empty($placeholder))placeholder="{{ $placeholder }}"@endif
						@include('helpers.forms.attributes.field')
				>
				<span class="input-group-text video-previewSuffix video-noPadding">
		            <span class="video-preview">
		                <span class="video-previewImage"></span>
		                <a class="video-previewLink hidden" target="_blank" href="">
		                    <i class="fa video-previewIcon"></i>
		                </a>
		            </span>
		            <span class="video-dummy">
		                <a class="video-previewLink youtube dummy" target="_blank" href="">
		                    <i class="fa-brands fa-youtube video-previewIcon dummy"></i>
		                </a>
		                <a class="video-previewLink vimeo dummy" target="_blank" href="">
		                    <i class="fa-brands fa-vimeo video-previewIcon dummy"></i>
		                </a>
		            </span>
		        </span>
			</div>
			
			@include('helpers.forms.partials.hint')
			@include('helpers.forms.partials.validation')
			
			@if ($isHorizontal)
		</div>
	@endif
</div>
@include('helpers.forms.partials.newline')

@php
	$viewName = str($viewName)->replace('-', '_')->toString();
@endphp

{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}
@pushonce("{$viewName}_helper_styles")
	<style media="screen">
		.video-previewSuffix {
			border: 0;
			min-width: 68px;
		}
		
		.video-noPadding {
			padding: 0;
		}
		
		.video-preview {
			display: none;
		}
		
		.video-previewLink {
			color: #fff;
			display: block;
			width: 34px;
			height: 34px;
			text-align: center;
			float: left;
		}
		
		.video-previewLink.youtube {
			background: #DA2724;
		}
		
		.video-previewLink.vimeo {
			background: #00ADEF;
		}
		
		.video-previewIcon {
			transform: translateY(10px);
		}
		
		.video-previewImage {
			float: left;
			display: block;
			width: 34px;
			height: 34px;
			background-size: cover;
			background-position: center center;
		}
	</style>
@endpushonce

@pushonce("{$viewName}_helper_scripts")
	<script>
		onDocumentReady((event) => {
			const tryYouTube = function (link) {
				let id = null;
				
				// RegExps for YouTube link forms
				const youtubeStandardExpr = /^https?:\/\/(www\.)?youtube.com\/watch\?v=([^?&]+)/i; // Group 2 is video ID
				const youtubeAlternateExpr = /^https?:\/\/(www\.)?youtube.com\/v\/([^\/\?]+)/i; // Group 2 is video ID
				const youtubeShortExpr = /^https?:\/\/youtu.be\/([^\/]+)/i; // Group 1 is video ID
				const youtubeEmbedExpr = /^https?:\/\/(www\.)?youtube.com\/embed\/([^\/]+)/i; // Group 2 is video ID
				
				let match = link.match(youtubeStandardExpr);
				
				if (match != null) {
					id = match[2];
				} else {
					match = link.match(youtubeAlternateExpr);
					
					if (match != null) {
						id = match[2];
					} else {
						match = link.match(youtubeShortExpr);
						
						if (match != null) {
							id = match[1];
						} else {
							match = link.match(youtubeEmbedExpr);
							
							if (match != null) {
								id = match[2];
							}
						}
					}
				}
				
				return id;
			};
			
			const tryVimeo = function (link) {
				let id = null;
				const regExp = /(http|https):\/\/(www\.)?vimeo.com\/(\d+)($|\/)/;
				
				let match = link.match(regExp);
				if (match) {
					id = match[3];
				}
				
				return id;
			};
			
			const fetchYouTube = function (videoId, callback) {
				const api = 'https://www.googleapis.com/youtube/v3/videos?id='
					+ videoId
					+ '&key=AIzaSyDQa76EpdNPzfeTAoZUut2AnvBA0jkx3FI&part=snippet';
				
				const video = {
					provider: 'youtube',
					id: null,
					title: null,
					image: null,
					url: null
				};
				
				$.getJSON(api, function (data) {
					if (typeof (data.items[0]) != "undefined") {
						const v = data.items[0].snippet;
						
						video.id = videoId;
						video.title = v.title;
						video.image = v.thumbnails.maxres ? v.thumbnails.maxres.url : v.thumbnails.default.url;
						video.url = 'https://www.youtube.com/watch?v=' + video.id;
						
						callback(video);
					}
				});
			};
			
			const fetchVimeo = function (videoId, callback) {
				const api = 'http://vimeo.com/api/v2/video/' + videoId + '.json?callback=?';
				
				const video = {
					provider: 'vimeo',
					id: null,
					title: null,
					image: null,
					url: null
				};
				
				$.getJSON(api, function (data) {
					if (typeof (data[0]) != "undefined") {
						const v = data[0];
						
						video.id = v.id;
						video.title = v.title;
						video.image = v.thumbnail_large || v.thumbnail_small;
						video.url = v.url;
						
						callback(video);
					}
				});
			};
			
			const parseVideoLink = function (link, callback) {
				const response = {success: false, message: 'unknown error occured, please try again', data: []};
				
				try {
					const parser = document.createElement('a');
				} catch (e) {
					response.message = 'Please post a valid youtube/vimeo url';
					return response;
				}
				
				let id = tryYouTube(link);
				if (id) {
					return fetchYouTube(id, function (video) {
						if (video) {
							response.success = true;
							response.message = 'video found';
							response.data = video;
						}
						
						callback(response);
					});
				} else {
					id = tryVimeo(link);
					if (id) {
						return fetchVimeo(id, function (video) {
							if (video) {
								response.success = true;
								response.message = 'video found';
								response.data = video;
							}
							
							callback(response);
						});
					}
				}
				
				response.message = 'We could not detect a YouTube or Vimeo ID, please try obtain the URL again';
				
				return callback(response);
			};
			
			const updateVideoPreview = function (video, container) {
				const pWrap = container.find('.video-preview'),
					pLink = container.find('.video-previewLink').not('.dummy'),
					pImage = container.find('.video-previewImage').not('dummy'),
					pIcon = container.find('.video-previewIcon').not('.dummy'),
					pSuffix = container.find('.video-previewSuffix'),
					pDummy = container.find('.video-dummy');
				
				pDummy.hide();
				
				pLink.attr('href', video.url)
				.removeClass('youtube vimeo hidden')
				.addClass(video.provider);
				
				pImage.css('backgroundImage', 'url(' + video.image + ')');
				
				pIcon.removeClass('fa-vimeo fa-youtube').addClass('fa-' + video.provider);
				
				pWrap.fadeIn();
			};
			
			// Loop through all instances of the video field
			$("[data-video]").each(function (index) {
				const $this = $(this),
					jsonField = $this.find('.video-json'),
					linkField = $this.find('.video-link'),
					pDummy = $this.find('.video-dummy'),
					pWrap = $this.find('.video-preview');
				
				try {
					const videoJson = JSON.parse(jsonField.val());
					jsonField.val(JSON.stringify(videoJson));
					linkField.val(videoJson.url);
					updateVideoPreview(videoJson, $this);
				} catch (e) {
					pDummy.show();
					pWrap.hide();
					jsonField.val('');
					linkField.val('');
				}
				
				linkField.on('focus', function () {
					linkField.originalState = linkField.val();
				});
				
				linkField.on('change', function () {
					if (linkField.originalState !== linkField.val()) {
						if (linkField.val().length) {
							videoParsing = true;
							
							parseVideoLink(linkField.val(), function (videoJson) {
								if (videoJson.success) {
									linkField.val(videoJson.data.url);
									jsonField.val(JSON.stringify(videoJson.data));
									updateVideoPreview(videoJson.data, $this);
								} else {
									pDummy.show();
									pWrap.hide();
									alert(videoJson.message);
								}
								
								videoParsing = false;
							});
						} else {
							videoParsing = false;
							jsonField.val('');
							$this.find('.video-preview').fadeOut();
							pDummy.show();
							pWrap.hide();
						}
					}
				});
			});
			
			let videoParsing = false;
			
			$('form').on('submit', function (e) {
				if (videoParsing) {
					alert('Video details are still loading, please wait a moment and try again');
					e.preventDefault();
					return false;
				}
			})
		});
	</script>
@endpushonce
