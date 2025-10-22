/*******************************************/
/* This is for the chat customizer setting */
/*******************************************/
$(function () {
	const chatEl = $('#chat');
	
	$('#chat .message-center a').on('click', function () {
		const name = $(this).find('.mail-contnet h5').text();
		const img = $(this).find('.user-img img').attr('src');
		const id = $(this).attr('data-user-id');
		const status = $(this).find('.profile-status').attr('data-status');
		const userChatEl = $('.chat-windows #user-chat' + id);
		
		if ($(this).hasClass('active')) {
			$(this).toggleClass('active');
			userChatEl.hide();
		} else {
			$(this).toggleClass('active');
			if (userChatEl.length) {
				userChatEl.removeClass('mini-chat').show();
			} else {
				let msg = msg_receive('I watched the storm, so beautiful yet terrific.');
				msg += msg_sent('That is very deep indeed!');
				var html = "<div class='user-chat' id='user-chat" + id + "' data-user-id='" + id + "'>";
				html += "<div class='chat-head'>" +
					"<img src='" + img + "' data-user-id='" + id + "' alt=''>" +
					"<span class='status " + status + "'></span>" +
					"<span class='name'>" + name + "</span>" +
					"<span class='opts'>" +
					"<i class='ti-close closeit' data-user-id='" + id + "'></i>" +
					"<i class='ti-minus mini-chat' data-user-id='" + id + "'></i>" +
					"</span>" +
					"</div>";
				html += "<div class='chat-body'><ul class='chat-list'>" + msg + "</ul></div>";
				html += "<div class='chat-footer'><input type='text' data-user-id='" + id + "' placeholder='Type & Enter' class='form-control'></div>";
				html += "</div>";
				$('.chat-windows').append(html);
			}
		}
	});
	
	$(document).on('click', '.chat-windows .user-chat .chat-head .closeit', function (e) {
		const id = $(this).attr('data-user-id');
		const userChatEl = $('.chat-windows #user-chat' + id);
		
		userChatEl.hide();
		$('#chat .message-center .user-info#chat_user_' + id).removeClass('active');
	});
	
	$(document).on('click', '.chat-windows .user-chat .chat-head img, .chat-windows .user-chat .chat-head .mini-chat', function (e) {
		const id = $(this).attr('data-user-id');
		const userChatEl = $('.chat-windows #user-chat' + id);
		
		if (!userChatEl.hasClass("mini-chat")) {
			userChatEl.addClass("mini-chat");
		} else {
			userChatEl.removeClass("mini-chat");
		}
	});
	
	$(document).on('keypress', '.chat-windows .user-chat .chat-footer input', function (e) {
		let userChatElSelector;
		if (e.keyCode === 13 || e.keyCode === '13') {
			const id = $(this).attr('data-user-id');
			userChatElSelector = '.chat-windows #user-chat' + id;
			let msg = $(this).val();
			msg = msg_sent(msg);
			
			$(userChatElSelector + ' .chat-body .chat-list').append(msg);
			$(this).val('');
			$(this).focus();
		}
		
		if (userChatElSelector) {
			$(userChatElSelector + ' .chat-body').perfectScrollbar({
				suppressScrollX: true
			});
		}
	});
	
	const chatWindowsEl = $('.chat-windows');
	$('.page-wrapper').on('click', function (e) {
		chatWindowsEl.addClass('hide-chat');
		chatWindowsEl.removeClass('show-chat');
	});
	$('.service-panel-toggle').on('click', function (e) {
		chatWindowsEl.addClass('show-chat');
		chatWindowsEl.removeClass('hide-chat');
	});
});

function msg_receive(msg) {
	const d = new Date();
	const h = d.getHours();
	const m = d.getMinutes();
	
	return `<li class="msg_receive">
        <div class="chat-content">
            <div class="box bg-light-info">${msg}</div>
        </div>
        <div class="chat-time">${h}:${m}</div>
    </li>`;
}

function msg_sent(msg) {
	const d = new Date();
	const h = d.getHours();
	const m = d.getMinutes();
	
	return `<li class="odd msg_sent">
        <div class="chat-content">
            <div class="box bg-light-info">${msg}</div><br>
        </div>
        <div class="chat-time">${h}:${m}</div>
    </li>`;
}
