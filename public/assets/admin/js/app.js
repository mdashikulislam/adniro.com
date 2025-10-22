/* Admin Panel settings */
$.fn.AdminSettings = function (settings) {
	const wrapperId = $(this).attr('id');
	
	/* General option for vertical header */
	const defaults = {
		Theme: true, /* Boolean (`true` means dark and `false` means light), */
		Layout: 'vertical', /* ... */
		LogoBg: 'skin1', /* You can change the Value to be: skin1, skin2, skin3, skin4, skin5, skin6 */
		NavbarBg: 'skin6', /* You can change the Value to be: skin1, skin2, skin3, skin4, skin5, skin6 */
		SidebarType: 'full', /* You can change it to: 'full' or 'mini-sidebar' */
		SidebarColor: 'skin1', /* You can change the Value to be: skin1, skin2, skin3, skin4, skin5, skin6 */
		StylishSidebarColor: 'skin1', /* You can change the Value to be: skin1, skin2, skin3, skin4, skin5, skin6 */
		SidebarPosition: false, /* Boolean */
		HeaderPosition: false, /* Boolean */
		BoxedLayout: false, /* Boolean */
	};
	settings = $.extend({}, defaults, settings);
	
	/* Attribute functions */
	const AdminSettings = {
		/* Settings INIT */
		AdminSettingsInit: function () {
			AdminSettings.ManageTheme();
			AdminSettings.ManageThemeLayout();
			AdminSettings.ManageThemeBackground();
			AdminSettings.ManageSidebarType();
			AdminSettings.ManageSidebarColor();
			AdminSettings.ManageSidebarPosition();
			AdminSettings.ManageBoxedLayout();
			AdminSettings.ManageStylishSidebar();
		},
		
		/*******************************/
		/* ManageThemeLayout functions */
		/*******************************/
		ManageTheme: function () {
			const $body = $('body');
			const themeView = settings.Theme;
			const wrapperEl = $('#' + wrapperId);
			
			switch (settings.Layout) {
				case 'vertical':
					if (themeView === true) {
						document.documentElement.setAttribute('data-bs-theme', 'dark');
						$body.attr('data-theme', 'dark');
					} else {
						document.documentElement.removeAttribute('data-bs-theme');
						$body.attr('data-theme', 'light');
					}
					break;
				
				default:
			}
		},
		
		/*******************************/
		/* ManageThemeLayout functions */
		/*******************************/
		ManageThemeLayout: function () {
			const wrapperEl = $('#' + wrapperId);
			const scrollSidebarEl = $('.scroll-sidebar');
			
			switch (settings.Layout) {
				case 'horizontal':
					wrapperEl.attr('data-layout', 'horizontal');
					const setPerfectScrollHorizontal = function () {
						const width = (window.innerWidth > 0) ? window.innerWidth : this.screen.width;
						if (width < 991) {
							scrollSidebarEl.perfectScrollbar({});
						} else {
						}
					};
					$(window).ready(setPerfectScrollHorizontal);
					$(window).on('resize', setPerfectScrollHorizontal);
					break;
				case 'vertical':
					wrapperEl.attr('data-layout', 'vertical');
					scrollSidebarEl.perfectScrollbar({});
					break;
				default:
			}
		},
		
		/*******************************/
		/* ManageSidebarType functions */
		/*******************************/
		ManageThemeBackground: function () {
			/* Logo bg attribute */
			function setLogoBg() {
				const headerNavEl = $('#' + wrapperId + ' .topbar .top-navbar .navbar-header');
				const logoBg = settings.LogoBg;
				if (logoBg !== undefined && logoBg !== null && logoBg !== '') {
					headerNavEl.attr('data-logobg', logoBg);
				} else {
					headerNavEl.attr('data-logobg', 'skin1');
				}
			}
			
			setLogoBg();
			
			/* Navbar bg attribute */
			function setNavbarBg() {
				const navbarBg = settings.NavbarBg;
				const wrapperEl = $('#' + wrapperId);
				
				if (navbarBg !== undefined && navbarBg !== null && navbarBg !== '') {
					$('#' + wrapperId + ' .topbar .navbar-collapse').attr('data-navbarbg', navbarBg);
					$('#' + wrapperId + ' .topbar').attr('data-navbarbg', navbarBg);
					wrapperEl.attr('data-navbarbg', navbarBg);
				} else {
					$('#' + wrapperId + ' .topbar .navbar-collapse').attr('data-navbarbg', 'skin1');
					$('#' + wrapperId + ' .topbar').attr('data-navbarbg', 'skin1');
					wrapperEl.attr('data-navbarbg', 'skin1');
				}
			}
			
			setNavbarBg();
		},
		
		/*******************************/
		/* ManageThemeLayout functions */
		/*******************************/
		ManageSidebarType: function () {
			const wrapperEl = $(`#${wrapperId}`);
			const mainWrapper = $('#main-wrapper');
			const sidebarToggler = $('.sidebartoggler');
			
			let setSidebarType;
			switch (settings.SidebarType) {
				/********************************/
				/* If the sidebar type has full */
				/********************************/
				case 'full':
					wrapperEl.attr('data-sidebartype', 'full');
					mainWrapper.removeClass('mini-sidebar');
					
					/***********************************************************/
					/* This is for the mini-sidebar if width is less then 1170 */
					/***********************************************************/
					setSidebarType = function () {
						const width = (window.innerWidth > 0) ? window.innerWidth : this.screen.width;
						if (width < 1170) {
							mainWrapper.attr('data-sidebartype', 'mini-sidebar');
							mainWrapper.addClass('mini-sidebar');
						} else {
							mainWrapper.attr('data-sidebartype', 'full');
							mainWrapper.removeClass('mini-sidebar');
						}
					};
					$(window).ready(setSidebarType);
					$(window).off('resize').on('resize', setSidebarType);
					
					/******************************/
					/* This is for sidebartoggler */
					/******************************/
					sidebarToggler.off('click').on('click', function () {
						if (mainWrapper.hasClass('mini-sidebar')) {
							mainWrapper.attr('data-sidebartype', 'full');
							mainWrapper.removeClass('mini-sidebar');
						} else {
							mainWrapper.attr('data-sidebartype', 'mini-sidebar');
							mainWrapper.addClass('mini-sidebar');
						}
					});
					break;
				
				/****************************************/
				/* If the sidebar type has mini-sidebar */
				/****************************************/
				case 'mini-sidebar':
					wrapperEl.attr('data-sidebartype', 'mini-sidebar');
					mainWrapper.addClass('mini-sidebar');
					
					/******************************/
					/* This is for sidebartoggler */
					/******************************/
					sidebarToggler.off('click').on('click', function () {
						if (mainWrapper.hasClass('mini-sidebar')) {
							mainWrapper.attr('data-sidebartype', 'full');
							mainWrapper.removeClass('mini-sidebar');
						} else {
							mainWrapper.attr('data-sidebartype', 'mini-sidebar');
							mainWrapper.addClass('mini-sidebar');
						}
					});
					break;
				
				/***********************************/
				/* If the sidebar type has iconbar */
				/***********************************/
				case 'iconbar':
					wrapperEl.attr('data-sidebartype', 'iconbar');
					
					/***********************************************************/
					/* This is for the mini-sidebar if width is less then 1170 */
					/***********************************************************/
					setSidebarType = function () {
						const width = (window.innerWidth > 0) ? window.innerWidth : this.screen.width;
						if (width < 1170) {
							mainWrapper.attr('data-sidebartype', 'mini-sidebar');
							mainWrapper.addClass('mini-sidebar');
						} else {
							mainWrapper.attr('data-sidebartype', 'iconbar');
							mainWrapper.removeClass('mini-sidebar');
						}
					};
					$(window).ready(setSidebarType);
					$(window).off('resize').on('resize', setSidebarType);
					
					/******************************/
					/* This is for sidebartoggler */
					/******************************/
					sidebarToggler.off('click').on('click', function () {
						if (mainWrapper.hasClass('mini-sidebar')) {
							mainWrapper.attr('data-sidebartype', 'iconbar');
							mainWrapper.removeClass('mini-sidebar');
						} else {
							mainWrapper.attr('data-sidebartype', 'mini-sidebar');
							mainWrapper.addClass('mini-sidebar');
						}
					});
					break;
				
				/***********************************/
				/* If the sidebar type has overlay */
				/***********************************/
				case 'overlay':
					wrapperEl.attr('data-sidebartype', 'overlay');
					
					setSidebarType = function () {
						const width = (window.innerWidth > 0) ? window.innerWidth : this.screen.width;
						if (width < 767) {
							mainWrapper.attr('data-sidebartype', 'mini-sidebar');
							mainWrapper.addClass('mini-sidebar');
						} else {
							mainWrapper.attr('data-sidebartype', 'overlay');
							mainWrapper.removeClass('mini-sidebar');
						}
					};
					$(window).ready(setSidebarType);
					$(window).off('resize').on('resize', setSidebarType);
					
					/******************************/
					/* This is for sidebartoggler */
					/******************************/
					sidebarToggler.off('click').on('click', function () {
						if (mainWrapper.hasClass("show-sidebar")) {
							/* mainWrapper.attr('data-sidebartype','iconbar'); */
							/* mainWrapper.removeClass('mini-sidebar'); */
						} else {
							/* mainWrapper.attr('data-sidebartype','mini-sidebar'); */
							/* mainWrapper.addClass('mini-sidebar'); */
						}
					});
					break;
				
				/* Stylish */
				case 'stylish-menu':
					wrapperEl.attr('data-sidebartype', 'stylish-menu');
					
					/***********************************************************/
					/* This is for the mini-sidebar if width is less then 1170 */
					/***********************************************************/
					setSidebarType = function () {
						const width = (window.innerWidth > 0) ? window.innerWidth : this.screen.width;
						if (width < 1170) {
							mainWrapper.attr('data-sidebartype', 'stylish-menu');
							mainWrapper.addClass('mini-sidebar');
						} else {
							mainWrapper.attr('data-sidebartype', 'stylish-menu');
							mainWrapper.removeClass('mini-sidebar');
						}
					};
					$(window).ready(setSidebarType);
					$(window).off('resize').on('resize', setSidebarType);
					break;
				default:
			}
		},
		
		/********************************/
		/* ManageSidebarColor functions */
		/********************************/
		ManageSidebarColor: function () {
			/* Logo bg attribute */
			function setSidebarBg() {
				const leftSidebarEl = $(`#${wrapperId} .left-sidebar`);
				const sbg = settings.SidebarColor;
				
				if (sbg !== undefined && sbg !== '') {
					leftSidebarEl.attr('data-sidebarbg', sbg);
				} else {
					leftSidebarEl.attr('data-sidebarbg', 'skin1');
				}
			}
			
			setSidebarBg();
		},
		ManageStylishSidebar: function () {
			function setStylishSidebarBg() {
				const sideMiniPanelEl = $(`#${wrapperId} .side-mini-panel`);
				const stylishSidebarBg = settings.StylishSidebarColor;
				
				if (stylishSidebarBg !== undefined && stylishSidebarBg !== null && stylishSidebarBg !== '') {
					sideMiniPanelEl.attr('data-stylishsidebarbg', stylishSidebarBg);
				} else {
					sideMiniPanelEl.attr('data-stylishsidebarbg', 'skin1');
				}
			}
			
			setStylishSidebarBg();
		},
		
		/***********************************/
		/* ManageSidebarPosition functions */
		/***********************************/
		ManageSidebarPosition: function () {
			const sidebarPosition = settings.SidebarPosition;
			const headerPosition = settings.HeaderPosition;
			const wrapperEl = $(`#${wrapperId}`);
			
			switch (settings.Layout) {
				case 'vertical':
					if (sidebarPosition === true) {
						wrapperEl.attr('data-sidebar-position', 'fixed');
						$('#sidebar-position').prop('checked', !0);
					} else {
						wrapperEl.attr('data-sidebar-position', 'absolute');
						$('#sidebar-position').prop('checked', !1);
					}
					if (headerPosition === true) {
						wrapperEl.attr('data-header-position', 'fixed');
						$('#header-position').prop('checked', !0);
					} else {
						wrapperEl.attr('data-header-position', 'relative');
						$('#header-position').prop('checked', !1);
					}
					break;
				case 'horizontal':
					if (sidebarPosition === true) {
						wrapperEl.attr('data-sidebar-position', 'fixed');
						$('#sidebar-position').prop('checked', !0);
					} else {
						wrapperEl.attr('data-sidebar-position', 'absolute');
						$('#sidebar-position').prop('checked', !1);
					}
					if (headerPosition === true) {
						wrapperEl.attr('data-header-position', 'fixed');
						$('#header-position').prop('checked', !0);
					} else {
						wrapperEl.attr('data-header-position', 'relative');
						$('#header-position').prop('checked', !1);
					}
					break;
				default:
			}
		},
		
		/*******************************/
		/* ManageBoxedLayout functions */
		/*******************************/
		ManageBoxedLayout: function () {
			const boxedLayout = settings.BoxedLayout;
			const wrapperEl = $('#' + wrapperId);
			
			switch (settings.Layout) {
				case 'vertical':
					if (boxedLayout === true) {
						wrapperEl.attr('data-boxed-layout', 'boxed');
						$('#boxed-layout').prop('checked', !0);
					} else {
						wrapperEl.attr('data-boxed-layout', 'full');
						$('#boxed-layout').prop('checked', !1);
					}
					break;
				case 'horizontal':
					if (boxedLayout === true) {
						wrapperEl.attr('data-boxed-layout', 'boxed');
						$('#boxed-layout').prop('checked', !0);
					} else {
						wrapperEl.attr('data-boxed-layout', 'full');
						$('#boxed-layout').prop('checked', !1);
					}
					break;
				default:
			}
		},
	};
	
	AdminSettings.AdminSettingsInit();
};
