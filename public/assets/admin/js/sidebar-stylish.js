$(function () {
	"use strict";
	
	const $mainWrapper = $("#main-wrapper");
	const $toggleStylishSidebar = $("#toggle-stylish-sidebar");
	const $miniNavItemOrToggleStylishSidebar = $(".mini-nav > li, #toggle-stylish-sidebar");
	
	const $miniNavItem = $('.mini-nav > li');
	const $selectedMiniNavItem = $('.mini-nav > li.selected');
	
	// Perfect scrollbar initialisation
	$('.mini-nav, .sidebar-menu').perfectScrollbar();
	
	// ==============================================================
	// This is for the top header part and sidebar part
	// ==============================================================
	const set1 = function () {
		const width = (window.innerWidth > 0) ? window.innerWidth : this.screen.width;
		const topOffset = 0;
		if (width < 1170) {
			$selectedMiniNavItem.addClass('cnt-none');
			$toggleStylishSidebar.hide();
		} else {
			$selectedMiniNavItem.removeClass('cnt-none');
			$toggleStylishSidebar.show();
		}
	};
	$(window).ready(set1);
	$(window).on("resize", set1);
	
	// This is for close icon when navigation open in mobile view
	$(".nav-toggler").click(function () {
		$("body").toggleClass("show-sidebar");
		const $iNavToggler = $(".nav-toggler i");
		$iNavToggler.toggleClass("ti-menu");
		$iNavToggler.addClass("ti-close");
	});
	
	// This is for click on Sidebar open close button
	$miniNavItemOrToggleStylishSidebar.on('click', function () {
		if ($mainWrapper.hasClass("rmv-sidebarmenu")) {
			$mainWrapper.trigger("resize");
			$toggleStylishSidebar.hide();
		} else {
			$mainWrapper.trigger("resize");
			$toggleStylishSidebar.show();
		}
	});
	$miniNavItemOrToggleStylishSidebar.on('click', function () {
		if ($mainWrapper.hasClass("mini-sidebar")) {
			$selectedMiniNavItem.removeClass('cnt-none');
			$toggleStylishSidebar.show();
		}
	});
	
	// SideMenu Toggle
	const $navbarHeader = $(".topbar .navbar-header");
	$(".mini-nav").css("overflow", "hidden").parent().css("overflow", "visible");
	$toggleStylishSidebar.on("click", function () {
		$toggleStylishSidebar.hide();
		$selectedMiniNavItem.addClass('cnt-none');
		$navbarHeader.removeClass("expand-logo");
		$navbarHeader.toggleClass("narrow-logo");
	});
	$miniNavItem.on("click", function () {
		$toggleStylishSidebar.show();
		$selectedMiniNavItem.removeClass('cnt-none');
		$mainWrapper.removeClass('rmv-sidebarmenu');
		$navbarHeader.addClass("expand-logo");
	});
	$miniNavItem.on('click', function () {
		$selectedMiniNavItem.removeClass('selected');
		$(this).addClass('selected');
	});
	
	$(window).resize(function (e) {
		const width = (window.innerWidth > 0) ? window.innerWidth : this.screen.width;
		if (width < 1170) {
			// $("ul.sidebar-menu a").addClass('active').parent().addClass('in').parent().parent().addClass('selected').css("display","none");
			$(".mini-nav > li .sidebarmenu").addClass("collapse-sidebar");
			
			$miniNavItem.on("click", function () {
				$(".sidebarmenu").removeClass("collapse-sidebar");
			});
		} else {
			// Code here
		}
	});
	
	// Add or removed class from $mainWrapper
	if ($(window).width() > 1170) {
		$toggleStylishSidebar.on('click', function () {
			$mainWrapper.toggleClass('mini-sidebar');
		});
	} else {
		// Code here
	}
	
	// ==============================================================
	// Auto select left navbar
	// ==============================================================
	$(function () {
		const url = window.location;
		let element = $('ul.sidebar-menu a').filter(function () {
			return this.href === url;
		}).addClass('active').parent().addClass('active');
		while (true) {
			if (element.is('li')) {
				element = element.parent().addClass('in').parent().parent().addClass('selected');
			} else {
				break;
			}
		}
	});
	
	// SideMenu Style
	$.sideMenu = function (menu) {
		const animationSpeed = 300;
		$(menu).on('click', 'li a', function (e) {
			if ($(this).next().is('.sub-menu') && $(this).next().is(':visible')) {
				$(this).next().slideUp(animationSpeed, function () {
					$(this).next().removeClass('menu-open');
				});
				$(this).next().parent("li").removeClass("active");
			} else if (($(this).next().is('.sub-menu')) && (!$(this).next().is(':visible'))) {
				var parent = $(this).parents('ul').first();
				parent.find('ul:visible').slideUp(animationSpeed).removeClass('menu-open');
				$(this).next().slideDown(animationSpeed, function () {
					$(this).next().addClass('menu-open');
					parent.find('li.active').removeClass('active');
					$(this).parent("li").addClass('active');
				});
			}
		});
	}
	$.sideMenu($('.sidebar-menu'));
});
