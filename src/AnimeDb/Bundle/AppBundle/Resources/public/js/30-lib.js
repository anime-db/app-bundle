/**
 * Translate message
 */
function trans(message) {
	if (typeof(translations[message]) != 'undefined') {
		return translations[message];
	} else {
		return message;
	}
}


/**
 * Notifies all observers of change
 */
var BlockLoadHandler = function() {
	this.observers = [];
};
BlockLoadHandler.prototype = {
	registr: function(observer) {
		if (typeof(observer.update) == 'function') {
			this.observers.push(observer);
		} else if (typeof(observer) == 'function') {
			this.observers.push({update:observer});
		}
	},
	unregistr: function(observer) {
		for (var i in this.observers) {
			if (this.observers[i] === observer) {
				this.observers.splice(i, 1);
			}
		}
	},
	notify: function(block) {
		for (var i in this.observers) {
			this.observers[i].update(block);
		}
	}
};


/**
 * Cap for block site
 */
var Cap = {
	element: null,
	button: null,
	observers: [],
	html: null,
	setElement: function(element) {
		Cap.element = element;
		if (!Cap.button) {
			Cap.setButton(element);
		}
	},
	setButton: function(button) {
		if (Cap.button) {
			Cap.button.off('click.cap');
		}
		Cap.button = button.on('click.cap', function() {
			Cap.hide();
		});
	},
	getHtml: function() {
		if (Cap.html === null) {
			Cap.html = $('html');
		}
		return Cap.html;
	},
	// hide cup and observers
	hide: function(observer) {
		if (typeof(observer) !== 'undefined') {
			observer.hide();
		} else {
			for (var i in Cap.observers) {
				Cap.observers[i].hide();
			}
		}
		Cap.element.hide();
		Cap.getHtml().removeClass('scroll-lock');
	},
	// show cup and observers
	show: function(observer) {
		Cap.element.show();
		observer.show();
		Cap.getHtml().addClass('scroll-lock');
	},
	// need methods 'show' and 'hide'
	registr: function(observer) {
		Cap.observers.push($.extend({
			show: function() {},
			hide: function() {}
		}, observer));
	},
	unregistr: function(observer) {
		for (var i in Cap.observers) {
			if (Cap.observers[i] === observer) {
				Cap.observers.splice(i, 1);
			}
		}
	}
};


/**
 * Popup
 */
var Popup = function(body) {
	var that = this;
	this.body = body;
	this.close = body.find('.bt-popup-close').click(function() {
		that.hide();
	});
	Cap.registr(this);
};
Popup.prototype = {
	show: function() {
		Cap.show(this.body);
	},
	hide: function() {
		Cap.hide(this.body);
	}
};

var PopupContainer = {
	popup_loader: null,
	xhr: null,
	list: [],
	container: null,
	getContainer: function() {
		if (PopupContainer.container === null) {
			PopupContainer.container = $('body');
		}
		return PopupContainer.container;
	},
	load: function(name, options) {
		options = $.extend({
			success: function() {},
			error: function(xhr, status) {
				if (status != 'abort' && confirm(trans('Failed to get the data. Want to try again?'))) {
					$.ajax(options);
				}
			}
		}, options||{});

		if (typeof(PopupContainer.list[name]) != 'undefined') {
			options.success(PopupContainer.list[name]);
		} else {
			// init popup on success load popup content
			var success = options.success;
			options.success = function(data) {
				PopupContainer.list[name] = new Popup($(data));
				success(PopupContainer.list[name]);
				PopupContainer.getContainer().append(PopupContainer.list[name].body);
			}

			PopupContainer.sendRequest(options);
		}
	},
	get: function(name) {
		if (typeof(PopupContainer.list[name]) != 'undefined') {
			return PopupContainer.list[name];
		} else {
			return null;
		}
	},
	setPopupLoader: function(el) {
		PopupContainer.popup_loader = new Popup(el);
	},
	sendRequest: function(options) {
		if (PopupContainer.xhr === null) {
			Cap.registr({
				show:function(){},
				hide:function(){
					PopupContainer.xhr.abort();
				},
			});
		} else {
			PopupContainer.xhr.abort();
		}
		PopupContainer.xhr = $.ajax(options);
	},
	/**
	 * Lazy loading body of popup
	 */
	lazyload: function(name, options) {
		options = $.extend({
			success: function() {},
			error: function(xhr, status) {
				if (status != 'abort' && confirm(trans('Failed to get the data. Want to try again?'))) {
					$.ajax(options);
				} else {
					PopupContainer.popup_loader.hide();
				}
			}
		}, options||{});

		if (typeof(PopupContainer.list[name]) != 'undefined') {
			options.success(PopupContainer.list[name]);
		} else {
			PopupContainer.popup_loader.show();

			// init popup on success load popup content
			var success = options.success;
			options.success = function(data) {
				var popup = new Popup(PopupContainer.popup_loader.body.clone().hide());
				popup.body.attr('id', name).find('.content').append(data);
				PopupContainer.getContainer().append(popup.body);

				PopupContainer.list[name] = popup;
				success(popup);

				// animate show popup
				var width = popup.body.width();
				var height = popup.body.height();
				PopupContainer.popup_loader.body.find()
				PopupContainer.popup_loader.body.addClass('resize').animate({
					'width': width,
					'height': height
				}, 400, function() {
					popup.show();
					// reset style
					PopupContainer.popup_loader.body.removeClass('resize').removeAttr('style').hide();
				});
			}

			PopupContainer.sendRequest(options);
		}
	}
};


/**
 * Notice container
 */
var NoticeContainerModel = function(container, from) {
	this.container = container;
	this.from = from;
	this.notice = null;
	this.load();
};
NoticeContainerModel.prototype = {
	load: function() {
		var that = this;
		this.notice = null;
		$.ajax({
			url: this.from,
			ifModified: true,
			complete: function(jqXHR, textStatus) {
				var data;
				if (
					(textStatus == 'success' || textStatus == 'notmodified') &&
					(data = $.parseJSON(jqXHR.responseText))
				) {
					that.show(data)
				}
			}
		});
	},
	show: function(data) {
		data.notice;
		var block = $(data.content);
		var message = block.find('.b-message');
		this.container.append(block);
		this.notice = new NoticeModel(
			this,
			block,
			data.close,
			data.see_later,
			block.find('.bt-close'),
			block.find('.bt-see-later'),
			message.width() - block.find('.b-message-wrapper').width(),
			message,
			block.find('.bt-notice-scroll-left'),
			block.find('.bt-notice-scroll-right')
		);
	}
};


/**
 * Confirm delete
 */
var ConfirmDeleteModel = function(link) {
	this.massage = link.data('massage') || trans('Are you sure want to delete this item(s)?');
	this.link = link;
	var that = this;
	link.click(function() {
		return that.remove();
	});
};
ConfirmDeleteModel.prototype = {
	remove: function() {
		return confirm(this.massage);
	}
};


/**
 * Toggle block visible
 */
var ToggleBlock = function(button) {
	var block = $(button.data('target'));
	button.click(function() {
		block.slideToggle(150);
		return false;
	});
};


/**
 * Update log block controller
 *
 * Control over the application upgrade and change tracking
 */
var UpdateLogBlock = function(block) {
	this.block = block;
	this.from = block.data('from');
	this.message = block.data('message');
	this.redirect = block.data('redirect') || '/';
	this.end_message = block.data('end-message');
	this.update();
};
UpdateLogBlock.prototype = {
	update: function() {
		var that = this;
		$.ajax({
			url: that.from,
			success: function(data) {
				if (that.block.text() != data) {
					that.block.text(data).animate({scrollTop: that.block[0].scrollHeight}, 'slow');
					if (data.indexOf(that.end_message)) {
						that.complete();
						return;
					}
				}
				setTimeout(function() {
					that.update();
				}, 400);
			}
		});
	},
	complete: function() {
		alert(this.message);
		window.location.replace(this.redirect);
	}
};


/**
 * Keep hover a mouse on the element
 *
 * While maintaining stationary cursor over an element it adds
 * class and removes it after you drag the cursor
 * 
 * Takes a timeout from the attribute data-expect or 1000 seconds
 */
var KeepHover = function(el) {
	var that = this;
	this.timer;
	this.expect = el.data('expect') ? el.data('expect') : 1000;
	this.el = el.hover(function() {
		that.startTimer();
	}, function() {
		that.stopTimer();
		that.removeKeep();
	}).mousemove(function() {
		that.stopTimer();
		that.startTimer();
	});
};
KeepHover.prototype = {
	startTimer: function() {
		var that = this;
		this.timer = setTimeout(function() {
			that.setKeep();
		}, this.expect);
	},
	stopTimer: function() {
		clearTimeout(this.timer);
	},
	setKeep: function() {
		this.el.addClass('keep-hover')
	},
	removeKeep: function() {
		this.el.removeClass('keep-hover')
	}
};


/**
 * Progress bar
 */
var ProgressBar = function(bar, label) {
	this.bar = bar;
	this.label = label;
	this.from = bar.data('from');
	this.message = bar.data('message');
	this.redirect = bar.data('redirect');

	var that = this;
	// init jQuery UI progressbar
	this.bar.progressbar({
		value: false,
		change: function() {
			that.label.text(that.bar.progressbar('value')+'%');
		},
		complete: function() {
			that.complete();
		}
	});

	that.update();
};
ProgressBar.prototype = {
	update: function() {
		var that = this;
		$.ajax({
			url: this.from,
			dataType: 'json',
			success: function(data) {
				that.bar.progressbar('value', data.status);

				if (data.status == 100) {
					that.complete();
				} else {
					setTimeout(function() {
						that.update();
					}, 400);
				}
			}
		});
	},
	complete: function() {
		this.label.text(this.message);

		if (this.redirect) {
			// give the user the ability to see the completion message before redirecting
			var that = this;
			setTimeout(function() {
				window.location.replace(that.redirect);
			}, 500);
		}
	}
};