// translate message
function trans(message) {
	if (typeof(translations[message]) != 'undefined') {
		return translations[message];
	} else {
		return message;
	}
}

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
 * Form collection
 */
//Model collection
var FormCollection = function(collection, button_add, rows, remove_selector, handler) {
	var that = this;
	this.collection = collection;
	this.index = rows.length;
	this.rows = [];
	this.remove_selector = remove_selector;
	this.button_add = button_add.click(function() {
		that.add();
	});
	this.row_prototype = collection.data('prototype');
	this.handler = handler;
	for (var i = 0; i < rows.length; i++) {
		var row = new FormCollectionRow($(rows[i]));
		row.setCollection(this);
		this.rows.push(row);
	}
};
FormCollection.prototype = {
	add: function() {
		var row = new FormCollectionRow($(this.row_prototype.replace(/__name__(label__)?/g, this.index + 1)));
		this.addRowObject(row);
		return row;
	},
	addRowObject: function(row) {
		row.setCollection(this);
		// notify observers
		this.handler.notify(row.row);
		// add row
		this.rows.push(row);
		this.button_add.parent().before(row.row);
		// increment index
		this.index++;
	}
};
// Model collection row
var FormCollectionRow = function(row) {
	this.row = row;
	this.collection = null;
};
FormCollectionRow.prototype = {
	remove: function() {
		this.row.remove();
		var rows = [];
		// remove row in collection
		for (var i = 0; i < this.collection.rows.length; i++) {
			if (this.collection.rows[i] !== this) {
				rows.push(this.collection.rows[i]);
			}
		}
		this.collection.rows = rows;
	},
	setCollection: function(collection) {
		this.collection = collection;
		// add handler for remove button
		var that = this;
		this.row.find(collection.remove_selector).click(function() {
			that.remove();
		});
	}
};

var FormCollectionContainer = function() {
	this.collections = [];
};
FormCollectionContainer.prototype = {
	add: function(collection) {
		this.collections[collection.collection.attr('id')] = collection;
	},
	get: function(name) {
		return this.collections[name];
	},
	remove: function(name) {
		delete this.collections[name];
	}
};

/**
 * Form image
 */
// Model Field
var FormImageModelField = function(field, image, button, controller) {
	this.field = field;
	this.image = image;
	this.popup = null;
	var that = this;
	this.button = button.click(function() {
		if (that.popup) {
			that.change();
		} else {
			controller.getPopup(that, function(popup) {
				that.popup = popup;
				that.change();
			});
		}
	});
};
FormImageModelField.prototype = {
	change: function() {
		this.popup.show();
	},
	// update field data
	update: function(data) {
		this.field.val(data.path);
		this.image.attr('src', data.image);
	}
}
// Model Popup
var FormImageModelPopup = function(popup, remote, local, field) {
	this.remote = remote;
	this.local = local;
	this.popup = popup;
	this.field = field;
	this.form = popup.body.find('form')
	this.popup.hide();
};
FormImageModelPopup.prototype = {
	show: function() {
		// unbund old hendlers and bind new
		var that = this;
		this.form.unbind('submit').bind('submit', function() {
			that.upload();
			return false;
		});
		// show popup
		this.popup.show();
	},
	upload: function() {
		var that = this;
		// send form as ajax and call onUpload handler
		this.form.ajaxSubmit({
			dataType: 'json',
			success: function(data) {
				that.field.update(data);
				that.popup.hide();
				that.form.resetForm();
			},
			error: function(data, error, message) {
				// for normal error
				if (data.status == 404) {
					data = JSON.parse(data.responseText);
					if (typeof(data.error) !== 'undefined' && data.error) {
						message = data.error;
					}
				}
				alert(message);
			}
		});
	}
};
// Image controller
var FormImageController = function(image) {
	var field = new FormImageModelField(
		image.find('input'),
		image.find('img'),
		image.find('.change-button'),
		this
	);
};
FormImageController.prototype = {
	getPopup: function(field, init) {
		init = init || function() {};
		// on load popup
		var init_popup = function (popup) {
			// create model
			popup = new FormImageModelPopup(
				popup,
				$('#image-popup-remote'),
				$('#image-popup-local'),
				field
			);
			init(popup);
		};

		// create popup
		if (popup = PopupContainer.get('image')) {
			init_popup(popup);
		} else {
			PopupContainer.load('image', {
				url: field.field.closest('.f-image').data('popup'),
				success: init_popup
			});
		}
	}
};



/**
 * Form local path
 */
// model field
var FormLocalPathModelField = function(path, button, controller) {
	this.path = path;
	this.button = button;
	this.popup = null;

	var that = this;
	this.button.click(function() {
		if (that.popup) {
			that.change();
		} else {
			controller.getPopup(that, function(popup) {
				that.popup = popup;
				that.change();
			})
		}
	});
	this.path.change(function() {
		that.correctPath();
	}).change();
};
FormLocalPathModelField.prototype = {
	change: function() {
		this.correctPath();
		this.popup.change(this.path.val());
		this.popup.show();
	},
	// correct the end symbol of path
	correctPath: function() {
		var value = this.path.val();
		if (value.length && !(/[\\\/]$/.test(value))) {
			if (value[0] == '/') {
				this.path.val(value += '/');
			} else {
				this.path.val(value += '\\');
			}
		}
		// if the root folder is set then the path must always start with him
		var root = this.path.data('root');
		if (root) {
			var reg = new RegExp('^'+root.replace(/\//g, '\\\/'));
			if (!value.length || !reg.test(value)) {
				this.path.val(root);
			}
		}
	}
};

// model folder
var FormLocalPathModelFolder = function(folder, path) {
	this.path = path;
	this.popup = null;

	var that = this;
	this.folder = folder.click(function() {
		that.select();
		return false;
	});
};
FormLocalPathModelFolder.prototype = {
	select: function() {
		this.popup.change(this.folder.attr('href'));
	},
	setPopup: function(popup) {
		this.popup = popup;
	}
};

// model pop-up
var FormLocalPathModelPopup = function(popup, path, button, folders, prototype, field) {
	this.popup = popup;
	this.path = path;
	this.button = button;
	this.field = field;
	this.form = popup.body.find('form');
	this.folders = folders;
	this.folder_prototype = prototype;
	this.folder_models = [];

	var that = this;
	this.popup.hide();
	// apply chenges
	this.button.click(function() {
		that.apply();
		return false;
	});
};
FormLocalPathModelPopup.prototype = {
	show: function() {
		// unbund old hendlers and bind new
		var that = this;
		this.form.unbind('submit').bind('submit', function() {
			that.change();
			return false;
		});
		this.path.unbind('change keyup').bind('change keyup', function() {
			that.change();
			return false;
		});
		// show popup
		this.popup.show();
	},
	change: function(value) {
		if (typeof(value) !== 'undefined') {
			this.path.val(value);
		}
		// return if not full path
		if (this.path.val().length) {
			// if the root folder is set then the path must always start with him
			var root = this.field.path.data('root');
			if (root) {
				var reg = new RegExp('^'+root.replace(/\//g, '\\\/'));
				if (!reg.test(this.path.val())) {
					this.path.val(root);
				}
			}
			if (!(/[\\\/]$/.test(this.path.val()))) {
				return false;
			}
		}

		// start updating
		this.popup.body.addClass('updating');

		var that = this;
		// send form as ajax
		this.form.ajaxSubmit({
			dataType: 'json',
			data: {'root': that.field.path.data('root')},
			success: function(data) {
				that.path.val(data.path);
				// remove old folders
				that.clearFoldersList();

				// create folders
				for (var i in data.folders) {
					// prototype of new item
					var new_item = that.folder_prototype
						.replace('__name__', data.folders[i].name)
						.replace('__link__', data.folders[i].path);
					that.addFolder(new FormLocalPathModelFolder($(new_item), that.path));
				}
			},
			error: function(data, error, message) {
				alert(message);
			},
			complete: function() {
				that.popup.body.removeClass('updating');
			}
		});
	},
	clearFoldersList: function() {
		this.folder_models = [];
		this.folders.text('');
	},
	addFolder: function(folder) {
		folder.setPopup(this);
		this.folder_models.push(folder);
		this.folders.append(folder.folder);
	},
	apply: function() {
		this.field.path.val(this.path.val());
		this.popup.hide();
	}
};
// Form local path controller
var FormLocalPathController = function(path) {
	// create field model
	var field = new FormLocalPathModelField(
		path.find('input'),
		path.find('.change-path'),
		this
	);
};
FormLocalPathController.prototype = {
	getPopup: function(field, init) {
		init = init || function() {};
		// on load popup
		var init_popup = function (popup) {
			var folders = popup.body.find('.folders');
			// create model
			popup = new FormLocalPathModelPopup(
				popup,
				popup.body.find('#local_path_popup_path'),
				popup.body.find('.change-path'),
				folders,
				folders.data('prototype'),
				field
			);
			init(popup);
		};

		// create popup
		if (popup = PopupContainer.get('local-path')) {
			init_popup(popup);
		} else {
			PopupContainer.load('local-path', {
				url: field.path.closest('.f-local-path').data('popup'),
				success: init_popup
			});
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
	html: $('html'),
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
		Cap.html.removeClass('scroll-lock');
	},
	// show cup and observers
	show: function(observer) {
		Cap.element.show();
		observer.show();
		Cap.html.addClass('scroll-lock');
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
}

var PopupContainer = {
	popup_loader: null,
	xhr: null,
	list: [],
	container: $('body'),
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
				PopupContainer.container.append(PopupContainer.list[name].body);
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
				PopupContainer.container.append(popup.body);

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
}

/**
 * Notice
 */
var NoticeModel = function(container, block, close_url, see_later_url, close, see_later) {
	this.container = container;
	this.block = block;
	this.close_url = close_url;
	this.see_later_url = see_later_url;
	this.close_button = close;
	this.see_later_button = see_later;

	var that = this;
	this.close_button.click(function(){
		that.close();
	});
	this.see_later_button.click(function(){
		that.seeLater();
	});
};
NoticeModel.prototype = {
	close: function() {
		var that = this;
		this.block.animate({opacity: 0}, 400, function() {
			// report to backend
			$.ajax({
				type: 'POST',
				url: that.close_url,
				success: function() {
					// remove this
					that.block.remove();
					delete that.container.notice;
					// load new notice
					that.container.load();
				}
			});
		});
	},
	seeLater: function() {
		var that = this;
		this.block.animate({opacity: 0}, 400, function() {
			// report to backend
			$.ajax({
				type: 'POST',
				url: that.see_later_url,
				success: function() {
					// remove this
					that.block.remove();
					delete that.container.notice;
				}
			});
		});
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
			success: function(data) {
				if (data) {
					that.show(data)
				}
			}
		});
	},
	show: function(data) {
		data.notice;
		var block = $(data.content);
		this.notice = new NoticeModel(
			this,
			block,
			data.close,
			data.see_later,
			block.find('.bt-close'),
			block.find('.bt-see-later')
		);
		this.container.append(this.notice.block);
	}
};

/**
 * Check all
 */
var CheckAllModel = function(checker, list) {
	this.checker = checker;
	this.list = list;
	var that = this;
	this.checker.click(function(){
		that.change();
	});
};
CheckAllModel.prototype = {
	change: function() {
		if (this.checker.is(':checked')) {
			this.all();
		} else {
			this.neither();
		}
	},
	all: function() {
		for (var i in this.list) {
			this.list[i].check();
		}
	},
	neither: function() {
		for (var i in this.list) {
			this.list[i].uncheck();
		}
	}
};
// Check all node
var CheckAllNodeModel = function(checkbox) {
	this.checkbox = checkbox;
};
CheckAllNodeModel.prototype = {
	check: function() {
		this.checkbox.prop('checked', true);
	},
	uncheck: function() {
		this.checkbox.prop('checked', false);
	}
};
// Check all in table
var TableCheckAllController = function(checker) {
	var checkboxes = checker.parents('table').find('.'+checker.data('target'));
	var list = [];
	for (var i = 0; i < checkboxes.length; i++) {
		list.push(new CheckAllNodeModel($(checkboxes[i])));
	}
	new CheckAllModel(checker, list);
}

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
 * Form refill field
 */
var FormRefill = function(form, button, controller, handler, sources) {
	this.form = form;
	this.button = button;
	this.controller = controller;
	this.handler = handler;
	this.sources = sources;

	var that = this;
	this.button.click(function() {
		if (that.button.data('can-refill') == 1) {
			that.refill();
		} else {
			that.search();
		}
		return false;
	});
};
FormRefill.prototype = {
	refill: function() {
		var that = this;
		this.showPopup(
			'refill-form-' + this.controller.field.attr('id'),
			this.button.attr('href'),
			function (popup) {
				popup.body.find('form').submit(function() {
					that.update(popup);
					return false;
				});
			}
		);
	},
	search: function() {
		var that = this;
		this.showPopup(
			'refill-search',
			this.button.attr('href'),
			function (popup) {
				popup.body.find('a').each(function() {
					new FormRefillSearchItem(that, popup, $(this));
				});
			}
		);
	},
	refillFromSearch: function(url) {
		var that = this;
		this.showPopup(
			'refill-form-' + this.controller.field.attr('id'),
			url,
			function (popup) {
				popup.body.find('form').submit(function() {
					that.update(popup);
					return false;
				});
			}
		);
	},
	showPopup: function(name, url, handler) {
		handler = handler || function() {};
		var that = this;

		if (popup = PopupContainer.get(name)) {
			handler(popup);
			popup.show();
		} else {
			PopupContainer.lazyload(name, {
				url: url,
				method: 'POST', // request is too large for GET
				data: this.form.serialize(),
				success: function(popup) {
					that.handler.notify(popup.body);
					handler(popup);
				}
			});
		}
	},
	update: function(popup) {
		this.controller.update(popup);
		// add source link
		var source = popup.body.find('input[type=hidden]');
		if (source && (value = source.val())) {
			this.canRefill();
			this.sources.add().row.find('input').val(value);
		}
		popup.hide();
	},
	canRefill: function() {
		this.form.find('a[data-plugin='+this.button.data('plugin')+']').each(function() {
			var button = $(this);
			button.attr('href', button.data('link-refill')).data('can-refill', 1);
		});
	}
};
var FormRefillSimple = function(field) {
	this.field = field;
};
FormRefillSimple.prototype = {
	update: function(popup) {
		this.field.val(popup.body.find('#'+this.field.attr('id')).val());
	}
};
var FormRefillCollection = function(field, collection, container) {
	this.field = field;
	this.collection = collection; // FormCollection
	this.container = container; // FormCollectionContainer
};
FormRefillCollection.prototype = {
	update: function(popup) {
		// remove old rows
		while (this.collection.rows.length) {
			this.collection.rows[0].remove();
		}
		// add new rows
		var collection = this.container.get(this.field.attr('id'));
		for (var i = 0; i < collection.rows.length; i++) {
			this.collection.addRowObject(new FormCollectionRow(collection.rows[i].row.clone()));
		}
	}
};
var FormRefillSearchItem = function(form, popup, link) {
	var that = this;
	this.form = form;
	this.popup = popup;
	this.link = link.click(function() {
		that.refill();
		return false;
	});
	
};
FormRefillSearchItem.prototype = {
	refill: function() {
		this.popup.hide();
		var source = decodeURIComponent(this.link.attr('href')).replace(/^.*(?:\?|&)source=([^&]+).*$/, '$1');
		if (source) {
			this.form.canRefill();
			this.form.sources.add().row.find('input').val(source);
			this.form.refill();
		} else {
			this.form.refillFromSearch(this.link.attr('href'));
		}
	}
};

/**
 * Toggle block visible
 */
var ToggleBlock = function(button) {
	var block = $(button.data('target'));
	button.click(function() {
		block.toggle();
		return false;
	});
};

var UpdateLogBlock = function(block) {
	this.block = block;
	this.from = block.data('from');
	this.message = block.data('message');
	this.redirect = block.data('redirect') || '/';
	this.end_message = new RegExp(block.data('end-message')+'$');
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
					if (that.end_message.test(data)) {
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
		top.location = this.redirect;
	}
};


// Form storage model
var FormStorage = function(storage, source, target) {
	this.storage = storage;
	this.source = source;
	this.target = target;

	var that = this;
	this.storage.change(function() {
		that.change();
	}).change();
};
FormStorage.prototype = {
	change: function() {
		var that = this;
		$.ajax({
			url: this.source,
			data: {'id': this.storage.val()},
			success: function(data) {
				if (data.required) {
					that.require(data.path);
				} else {
					that.unrequire();
				}
			}
		});
	},
	unrequire: function() {
		this.target.removeAttr('required').removeAttr('data-root').val('').change();
	},
	require: function(path) {
		this.target.attr({'required': 'required', 'data-root': path}).change();
	}
};