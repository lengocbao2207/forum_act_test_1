/** @param {jQuery} $ jQuery Object */
!function($, window, document, _undefined)
{
	//TODO: Enable jQuery plugin and compressor in editor template.

	/**
	 * Enables quick reply for a message form
	 * @param $form
	 */
	XenForo.QuickReply = function($form)
	{
		if ($('#messageList').length == 0)
		{
			return console.error('Quick Reply not possible for %o, no #messageList found.', $form);
		}

		var submitEnableCallback = XenForo.MultiSubmitFix($form);

		/**
		 * Scrolls QuickReply into view and focuses the editor
		 */
		this.scrollAndFocus = function()
		{
			$(document).scrollTop($form.offset().top);

			var ed = XenForo.getEditorInForm($form);
			if (!ed)
			{
				return false;
			}

			if (ed.$editor)
			{
				ed.focus(true);
			}
			else
			{
				ed.focus();
			}

			return this;
		};

		$form.data('QuickReply', this).bind(
		{
			/**
			 * Fires just before the form would be AJAX submitted,
			 * to detect whether or not the 'more options' button was clicked,
			 * and to abort AJAX submission if it was.
			 *
			 * @param event e
			 * @return
			 */
			AutoValidationBeforeSubmit: function(e)
			{
				if ($(e.clickedSubmitButton).is('input[name="more_options"]'))
				{
					e.preventDefault();
					e.returnValue = true;
				}
			},

			/**
			 * Fires after the AutoValidator form has successfully validated the AJAX submission
			 *
			 * @param event e
			 */
			AutoValidationComplete: function(e)
			{
				if (e.ajaxData._redirectTarget)
				{
					window.location = e.ajaxData._redirectTarget;
				}

				$('input[name="last_date"]', $form).val(e.ajaxData.lastDate);

				if (submitEnableCallback)
				{
					submitEnableCallback();
				}

				$form.find('input:submit').blur();

				new XenForo.ExtLoader(e.ajaxData, function()
				{
					$('#messageList').find('.messagesSinceReplyingNotice').remove();

					$(e.ajaxData.templateHtml).each(function()
					{
						if (this.tagName)
						{
							$(this).xfInsert('appendTo', $('#messageList'));
						}
					});
				});

				var $textarea = $('#QuickReply').find('textarea');
				$textarea.val('');
				var ed = $textarea.data('XenForo.BbCodeWysiwygEditor');
				if (ed)
				{
					ed.resetEditor();
				}

				$form.trigger('QuickReplyComplete');

				return false;
			},

			BbCodeWysiwygEditorAutoSaveComplete: function(e)
			{
				var $messageList = $('#messageList'),
					$notice = $messageList.find('.messagesSinceReplyingNotice');

				if (e.ajaxData.newPostCount && e.ajaxData.templateHtml)
				{
					if ($notice.length)
					{
						$notice.remove();
						$(e.ajaxData.templateHtml).appendTo($messageList).show().xfActivate();
					}
					else
					{
						$(e.ajaxData.templateHtml).xfInsert('appendTo', $messageList);
					}
				}
				else
				{
					$notice.remove();
				}
			}
		});
	};

	// *********************************************************************

	/**
	 * Controls to initialise Quick Reply with a quote
	 *
	 * @param jQuery a.ReplyQuote, a.MultiQuote
	 */
	XenForo.QuickReplyTrigger = function($trigger)
	{
		/**
		 * Activates quick reply and quotes the post to which the trigger belongs
		 *
		 * @param e event
		 *
		 * @return boolean false
		 */
		$trigger.click(function(e)
		{
			console.info('Quick Reply Trigger Click');

			var $form = null,
				xhr = null,
				queryData = null;

			if ($trigger.is('.MultiQuote'))
			{
				$form = $($trigger.data('form'));

				queryData =
				{
					postIds: $($trigger.data('inputs')).map(function()
					{
						return this.value;
					}).get()
				};
			}
			else
			{
				$form = $('#QuickReply');
				$form.data('QuickReply').scrollAndFocus();
			}

			if (!xhr)
			{
				xhr = XenForo.ajax
				(
					$trigger.data('posturl') || $trigger.attr('href'),
					queryData,
					function(ajaxData, textStatus)
					{
						if (XenForo.hasResponseError(ajaxData))
						{
							return false;
						}

						delete(xhr);

						var ed = XenForo.getEditorInForm($form);
						if (!ed)
						{
							return false;
						}

						if (ed.$editor)
						{
							ed.insertHtml(ajaxData.quoteHtml);
							if (ed.$editor.data('xenForoElastic'))
							{
								ed.$editor.data('xenForoElastic')();
							}
						}
						else
						{
							ed.val(ed.val() + ajaxData.quote);
						}

						if ($trigger.is('.MultiQuote'))
						{
							// reset cookie and checkboxes
							$form.trigger('MultiQuoteComplete');
						}
					}
				);
			}

			return false;
		});
	};

	// *********************************************************************

	XenForo.InlineMessageEditor = function($form)
	{
		new XenForo.MultiSubmitFix($form);

		$form.bind(
		{
			AutoValidationBeforeSubmit: function(e)
			{
				if ($(e.clickedSubmitButton).is('input[name="more_options"]'))
				{
					e.preventDefault();
					e.returnValue = true;
				}
			},
			AutoValidationComplete: function(e)
			{
				var overlay = $form.closest('div.xenOverlay').data('overlay'),
					target = overlay.getTrigger().data('target');

				if (XenForo.hasTemplateHtml(e.ajaxData, 'messagesTemplateHtml') || XenForo.hasTemplateHtml(e.ajaxData))
				{
					e.preventDefault();
					overlay.close().getTrigger().data('XenForo.OverlayTrigger').deCache();

					XenForo.showMessages(e.ajaxData, overlay.getTrigger(), 'instant');
				}
				else
				{
					console.warn('No template HTML!');
				}
			}
		});
	};

	// *********************************************************************

	XenForo.NewMessageLoader = function($ctrl)
	{
		$ctrl.click(function(e) {
			e.preventDefault();

			XenForo.ajax(
				$ctrl.data('href') || $ctrl.attr('href'),
				{},
				function(ajaxData) {
					if (XenForo.hasResponseError(ajaxData))
					{
						return false;
					}

					var $form = $('#QuickReply'),
						$messageList = $('#messageList');

					$('input[name="last_date"]', $form).val(ajaxData.lastDate);

					new XenForo.ExtLoader(ajaxData, function()
					{
						$messageList.find('.messagesSinceReplyingNotice').remove();

						$(ajaxData.templateHtml).each(function()
						{
							if (this.tagName)
							{
								$(this).xfInsert('appendTo', $messageList);
							}
						});
					});
				}
			)
		});
	};

	// *********************************************************************

	XenForo.MessageLoader = function($ctrl)
	{
		$ctrl.click(function(e)
		{
			e.preventDefault();

			var messageIds = [];

			$($ctrl.data('messageselector')).each(function(i, msg)
			{
				messageIds.push(msg.id);
			});

			if (messageIds.length)
			{
				XenForo.ajax
				(
					$ctrl.attr('href'),
					{
						messageIds: messageIds
					},
					function(ajaxData, textStatus)
					{
						XenForo.showMessages(ajaxData, $ctrl, 'fadeDown');
					}
				);
			}
			else
			{
				console.warn('No messages found to load.'); // debug message, no phrasing
			}
		});
	};

	// *********************************************************************

	XenForo.showMessages = function(ajaxData, $ctrl, method)
	{
		var showMessage = function(selector, templateHtml)
		{
			switch (method)
			{
				case 'instant':
				{
					method =
					{
						show: 'xfShow',
						hide: 'xfHide',
						speed: 0
					};
					break;
				}

				case 'fadeIn':
				{
					method =
					{
						show: 'xfFadeIn',
						hide: 'xfFadeOut',
						speed: XenForo.speed.fast
					};
					break;
				}

				case 'fadeDown':
				default:
				{
					method =
					{
						show: 'xfFadeDown',
						hide: 'xfFadeUp',
						speed: XenForo.speed.normal
					};
				}
			}

			$(selector)[method.hide](method.speed / 2, function()
			{
				$(templateHtml).xfInsert('replaceAll', selector, method.show, method.speed);
			});
		};

		if (XenForo.hasResponseError(ajaxData))
		{
			return false;
		}

		if (XenForo.hasTemplateHtml(ajaxData, 'messagesTemplateHtml'))
		{
			new XenForo.ExtLoader(ajaxData, function()
			{
				$.each(ajaxData.messagesTemplateHtml, showMessage);
			});
		}
		else if (XenForo.hasTemplateHtml(ajaxData))
		{
			// single message
			new XenForo.ExtLoader(ajaxData, function()
			{
				showMessage($ctrl.data('messageselector'), ajaxData.templateHtml);
			});
		}
	};

	// *********************************************************************

	XenForo.PollVoteForm = function($form)
	{
		$form.bind('AutoValidationComplete', function(e)
		{
			e.preventDefault();

			if (XenForo.hasTemplateHtml(e.ajaxData))
			{
				var $container = $($form.data('container'));

				$form.xfFadeUp(XenForo.speed.normal, function()
				{
					$form.empty().remove();

					var $html = $(e.ajaxData.templateHtml);
					if ($html.is($form.data('container')))
					{
						$html = $html.children();
					}
					else if ($html.find($form.data('container')).length)
					{
						$html = $html.find($form.data('container'));
					}

					$html.xfInsert('appendTo', $container);
				}, XenForo.speed.normal, 'swing');
			}
		});
	};

	// *********************************************************************

	XenForo.MultiQuote = function($button) { this.__construct($button); };
	XenForo.MultiQuote.prototype =
	{
		__construct: function($button)
		{
			this.$button = $button;
			this.$form = $button.closest('form');
			this.cookieName = $button.data('mq-cookie') || 'MultiQuote';
			this.cookieValue = [];
			this.submitUrl = $button.data('submiturl');
			this.$controls = new jQuery();

			this.getCookieValue();
			this.setButtonState();

			var self = this;

			this.$form.bind('MultiQuoteComplete', $.context(this, 'reset'));
			this.$form.bind('MultiQuoteRemove MultiQuoteAdd', function(e, data)
			{
				if (data && data.messageId)
				{
					self.toggleControl(data.messageId, e.type == 'MultiQuoteAdd');
				}
			});
		},

		getCookieValue: function()
		{
			var cookieString = $.getCookie(this.cookieName);

			this.cookieValue = (cookieString == null ? [] : cookieString.split(','));
		},

		setButtonState: function()
		{
			this.getCookieValue();

			if (this.cookieValue.length)
			{
				this.$button.show();
			}
			else
			{
				this.$button.hide();
			}
		},

		addControl: function($control)
		{
			$control.click($.context(this, 'clickControl'));

			this.getCookieValue();

			this.setControlState($control, ($.inArray($control.data('messageid') + '', this.cookieValue) >= 0), true);

			this.$controls = this.$controls.add($control);
		},

		setControls: function()
		{
			var MQ = this;

			MQ.getCookieValue();

			this.$controls.each(function()
			{
				MQ.setControlState($(this), ($.inArray($(this).data('messageid') + '', MQ.cookieValue) >= 0));
			});
		},

		setControlState: function($control, isActive, isInitial)
		{
			var text, $button = this.$button, classExpected;
			if (isActive)
			{
				text = $button.data('remove') || '-';
				classExpected = true;
			}
			else
			{
				text = $button.data('add') || '+';
				classExpected = false;
			}

			if (!isInitial || $control.hasClass('active') !== classExpected)
			{
				$control
					.toggleClass('active', isActive)
					.find('span.symbol').text(text);
			}
		},

		clickControl: function(e)
		{
			e.preventDefault();

			var $control = $(e.target).closest('a.MultiQuoteControl'),
				newActive = !$control.is('.active'),
				message = this.$button.data(newActive ? 'add-message' : 'remove-message');
			this.toggleControl($control.data('messageid'), newActive);

			if (message)
			{
				XenForo.alert(message, '', 2000);
			}
		},

		toggleControl: function(itemId, active)
		{
			this.getCookieValue();

			itemId += '';

			var i = $.inArray(itemId, this.cookieValue),
				$control;

			$control = this.$controls.filter(function()
			{
				return $(this).data('messageid') == itemId;
			}).first();

			if (active)
			{
				if ($control.length)
				{
					this.setControlState($control, true);
				}

				// add to cookie
				if (i < 0)
				{
					this.cookieValue.push(itemId);
				}
			}
			else
			{
				if ($control.length)
				{
					this.setControlState($control, false);
				}

				// remove from cookie
				if (i >= 0)
				{
					this.cookieValue.splice(i, 1);
				}
			}

			if (this.cookieValue.length > 0)
			{
				$.setCookie(this.cookieName, this.cookieValue.join(','));
			}
			else
			{
				$.deleteCookie(this.cookieName);
			}

			this.setButtonState();
		},

		reset: function()
		{
			$.deleteCookie(this.cookieName);
			this.cookieValue = [];

			this.setControls();
			this.setButtonState();
		}
	};

	// *********************************************************************

	/**
	 * Handles adding and removing messages from multi-quote
	 */
	XenForo.MultiQuoteControl = function($link)
	{
		var mqSelector = $link.data('mq-target') || '#MultiQuote',
			mq = $(mqSelector).data('XenForo.MultiQuote');
		if (!mq)
		{
			return;
		}

		mq.addControl($link);
	};

	/**
	 * Handles removal of quotes from the multi-quote overlay
	 */
	XenForo.MultiQuoteRemove = function($link)
	{
		$link.click(function()
		{
			var $container = $link.closest('.MultiQuoteItem'),
				messageId = $container.find('.MultiQuoteId').val(),
				$watcherForm = $($('#MultiQuoteForm').data('form')),
				$overlay = $link.closest('.xenOverlay');

			if (messageId)
			{
				$watcherForm.trigger('MultiQuoteRemove', {messageId: messageId});
			}

			$container.remove();

			if ($overlay.length && !$overlay.find('.MultiQuoteItem').length)
			{
				$overlay.overlay().close();
			}
		});
	};

	XenForo.MultiQuoteInsert = function($trigger)
	{
		/**
		 * Activates quick reply and quotes the post to which the trigger belongs
		 *
		 * @param e event
		 *
		 * @return boolean false
		 */
		$trigger.click(function(e)
		{
			var $form = $($trigger.data('form')),
				xhr = null;

			if (!xhr)
			{
				xhr = XenForo.ajax(
					$trigger.attr('href'),
					{
						postIds: $($trigger.data('inputs')).map(function()
						{
							return this.value;
						}).get()
					},
					function(ajaxData, textStatus)
					{
						if (XenForo.hasResponseError(ajaxData))
						{
							return false;
						}

						delete(xhr);

						var ed = XenForo.getEditorInForm($form);
						if (!ed)
						{
							return false;
						}

						if (ed.$editor)
						{
							ed.insertHtml(ajaxData.quoteHtml);
							if (ed.$editor.data('xenForoElastic'))
							{
								ed.$editor.data('xenForoElastic')();
							}
						}
						else
						{
							ed.val(ed.val() + ajaxData.quote);
						}

						if ($trigger.is('.MultiQuote'))
						{
							// reset cookie and checkboxes
							$form.trigger('MultiQuoteComplete');
						}
					}
				);
			}

			return false;
		});
	};

	// *********************************************************************

	XenForo.Sortable = function($container)
	{
		$container.sortable(
		{
			forcePlaceholderSize: true

		}).bind(
			{
				'sortupdate': function(e) {},
				'dragstart' : function(e)
				{
					console.log('drag start, %o', e.target);
				},
				'dragend' : function(e) { console.log('drag end'); }
			}
		);
	};

	// *********************************************************************

	XenForo.register('#QuickReply', 'XenForo.QuickReply');

	XenForo.register('a.ReplyQuote, a.MultiQuote', 'XenForo.QuickReplyTrigger');

	XenForo.register('form.InlineMessageEditor', 'XenForo.InlineMessageEditor');

	XenForo.register('a.MessageLoader', 'XenForo.MessageLoader');
	XenForo.register('a.NewMessageLoader', 'XenForo.NewMessageLoader');

	XenForo.register('form.PollVoteForm', 'XenForo.PollVoteForm');

	XenForo.register('.MultiQuoteWatcher', 'XenForo.MultiQuote');
	XenForo.register('a.MultiQuoteControl', 'XenForo.MultiQuoteControl');
	XenForo.register('a.MultiQuoteRemove', 'XenForo.MultiQuoteRemove');

	XenForo.register('.Sortable', 'XenForo.Sortable');

}
(jQuery, this, document);
