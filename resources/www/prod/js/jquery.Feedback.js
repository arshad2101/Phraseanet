;
(function (window) {

    var Feedback = function ($container, context) {
        this.container = $($container);

        this.Context = context;

        this.selection = new Selectable(
            $('.user_content .badges', this.container),
            {
                selector: '.badge'
            }
        );

        var $this = this;

        this.container.on('click', '.content .options .select-all', function (event) {
            $this.selection.selectAll();
        });

        this.container.on('click', '.content .options .unselect-all', function (event) {
            $this.selection.empty();
        });

        $('.UserTips', this.container).tooltip();

        this.container.on('click', 'a.user_adder', function (event) {
            var $this = $(this);

            $.ajax({
                type: "GET",
                url: $this.attr('href'),
                dataType: 'html',
                beforeSend: function () {
                    var options = {
                        size: 'Medium',
                        title: $this.html()
                    };
                    p4.Dialog.Create(options, 2).getDomElement().addClass('loading');
                },
                success: function (data) {
                    p4.Dialog.get(2).getDomElement().removeClass('loading').empty().append(data);
                    return;
                },
                error: function () {
                    p4.Dialog.get(2).Close();
                    return;
                },
                timeout: function () {
                    p4.Dialog.get(2).Close();
                    return;
                }
            });

            return false;
        });

        this.container.on('click', '.recommended_users', function (event) {
            var usr_id = $('input[name="usr_id"]', $(this)).val();

            $this.loadUser(usr_id, $this.selectUser);

            return false;
        });

        this.container.on('click', '.recommended_users_list', function (event) {

            var content = $('#push_user_recommendations').html();

            var options = {
                size: 'Small',
                title: $(this).attr('title')
            };

            $dialog = p4.Dialog.Create(options, 2);
            $dialog.setContent(content);

            $dialog.getDomElement().find('a.adder').bind('click', function () {

                $(this).addClass('added');

                var usr_id = $(this).closest('tr').find('input[name="usr_id"]').val();

                $this.loadUser(usr_id, $this.selectUser);

                return false;
            });

            $dialog.getDomElement().find('a.adder').each(function (i, el) {

                var usr_id = $(this).closest('tr').find('input[name="usr_id"]').val();

                if ($('.badge_' + usr_id, $this.container).length > 0) {
                    $(this).addClass('added');
                }

            });


            return false;
        });

        //this.container.on('submit', '#PushBox form[name="FeedBackForm"]', function (event) {
        $('#PushBox form[name="FeedBackForm"]').bind('submit', function () {

            var $this = $(this);

            $.ajax({
                type: $this.attr('method'),
                url: $this.attr('action'),
                dataType: 'json',
                data: $this.serializeArray(),
                beforeSend: function () {

                },
                success: function (data) {
                    if (data.success) {
                        humane.info(data.message);
                        p4.Dialog.Close(1);
                        p4.WorkZone.refresh();
                    }
                    else {
                        humane.error(data.message);
                    }
                    return;
                },
                error: function () {

                    return;
                },
                timeout: function () {

                    return;
                }
            });

            return false;
        });

        $('.FeedbackSend', this.container).bind('click', function () {
            if ($('.badges .badge', $container).length === 0) {
                alert(language.FeedBackNoUsersSelected);
                return;
            }

            var buttons = {};

            buttons[language.send] = function () {
                if ($.trim($('input[name="name"]', $dialog.getDomElement()).val()) === '') {
                    var options = {
                        size: 'Alert',
                        closeButton: true,
                        title: language.warning
                    }
                    var $dialogAlert = p4.Dialog.Create(options, 3);
                    $dialogAlert.setContent(language.FeedBackNameMandatory);

                    return false;
                }

                $dialog.Close();

                $('input[name="name"]', $FeedBackForm).val($('input[name="name"]', $dialog.getDomElement()).val());
                $('input[name="duration"]', $FeedBackForm).val($('select[name="duration"]', $dialog.getDomElement()).val());
                $('textarea[name="message"]', $FeedBackForm).val($('textarea[name="message"]', $dialog.getDomElement()).val());
                $('input[name="recept"]', $FeedBackForm).prop('checked', $('input[name="recept"]', $dialog.getDomElement()).prop('checked'));
                $('input[name="force_authentication"]', $FeedBackForm).prop('checked', $('input[name="force_authentication"]', $dialog.getDomElement()).prop('checked'));

                $FeedBackForm.trigger('submit');
            };

            var options = {
                size: 'Medium',
                buttons: buttons,
                loading: true,
                title: language.send,
                closeOnEscape: true,
                cancelButton: true
            };

            var $dialog = p4.Dialog.Create(options, 2);

            var $FeedBackForm = $('form[name="FeedBackForm"]', $container);

            var html = _.template($("#feedback_sendform_tpl").html());

            $dialog.setContent(html);

            $('input[name="name"]', $dialog.getDomElement()).val($('input[name="name"]', $FeedBackForm).val());
            $('textarea[name="message"]', $dialog.getDomElement()).val($('textarea[name="message"]', $FeedBackForm).val());
            $('.' + $this.Context, $dialog.getDomElement()).show();

            $('form', $dialog.getDomElement()).submit(function () {
                return false;
            });
        });

        $('.user_content .badges', this.container).disableSelection();


        // toggle download feature for users
        this.container.on('click', '.user_content .badges .badge .toggle', function (event) {
            var $this = $(this);

            $this.toggleClass('status_off status_on');

            $this.find('input').val($this.hasClass('status_on') ? '1' : '0');

            return false;
        });

        // toggle feature state of selected users
        this.container.on('click', '.general_togglers .general_toggler', function (event) {
            var feature = $(this).attr('feature');

            var $badges = $('.user_content .badge.selected', this.container);

            var toggles = $('.status_off.toggle_' + feature, $badges);

            if (toggles.length === 0) {
                var toggles = $('.status_on.toggle_' + feature, $badges);
            }
            if (toggles.length === 0) {
                humane.info('No user selected');
            }

            toggles.trigger('click');
            return false;
        });

        this.container.on('click', '.user_content .badges .badge .deleter', function (event) {
            var $elem = $(this).closest('.badge');
            $elem.fadeOut(function () {
                $elem.remove();
            });
            return false;
        });

        this.container.on('click', '.list_manager', function (event) {
            $('#PushBox').hide();
            $('#ListManager').show();
            return false;
        });

        this.container.on('click', 'a.list_loader', function (event) {
            var url = $(this).attr('href');

            var callbackList = function (list) {
                for (var i in list.entries) {
                    this.selectUser(list.entries[i].User);
                }
            };

            $this.loadList(url, callbackList);

            return false;
        });

        $('form.list_saver', this.container).bind('submit', function () {
            var $form = $(this);
            var $input = $('input[name="name"]', $form);

            var users = p4.Feedback.getUsers();

            if (users.length === 0) {
                humane.error('No users');
                return false;
            }

            p4.Lists.create($input.val(), function (list) {
                $input.val('');
                list.addUsers(users);
            });

            return false;
        });

        $('input[name="users-search"]', this.container).autocomplete({
            minLength: 2,
            source: function (request, response) {
                $.ajax({
                    url: '/prod/push/search-user/',
                    dataType: "json",
                    data: {
                        query: request.term
                    },
                    success: function (data) {
                        response(data);
                    }
                });
            },
            focus: function (event, ui) {
                $('input[name="users-search"]').val(ui.item.label);
            },
            select: function (event, ui) {
                if (ui.item.type === 'USER') {
                    $this.selectUser(ui.item);
                } else if (ui.item.type === 'LIST') {
                    for (var e in ui.item.entries) {
                        $this.selectUser(ui.item.entries[e].User);
                    }
                }
                return false;
            }
        })
            .data("ui-autocomplete")._renderItem = function (ul, item) {
            var html = "";

            if (item.type === 'USER') {
                html = _.template($("#list_user_tpl").html(), {

                    item: item
                });
            } else if (item.type === 'LIST') {
                html = _.template($("#list_list_tpl").html(), {
                    item: item
                });
            }

            return  $(html).data("ui-autocomplete-item", item).appendTo(ul);
        };

        return this;
    };

    Feedback.prototype = {
        selectUser: function (user) {
            if (typeof user !== 'object') {
                if (window.console) {
                    console.log('trying to select a user with wrong datas');
                }
            }
            if ($('.badge_' + user.usr_id, this.container).length > 0) {
                humane.info('User already selected');
                return;
            }

            var html = _.template($("#" + this.Context.toLowerCase() + "_badge_tpl").html(), {
                user: user
            });

            p4.Feedback.appendBadge(html);
        },
        loadUser: function (usr_id, callback) {
            var $this = this;

            $.ajax({
                type: 'GET',
                url: '/prod/push/user/' + usr_id + '/',
                dataType: 'json',
                data: {
                    usr_id: usr_id
                },
                success: function (data) {
                    if (typeof callback === 'function') {
                        callback.call($this, data);
                    }
                }
            });
        },
        loadList: function (url, callback) {
            var $this = this;

            $.ajax({
                type: 'GET',
                url: url,
                dataType: 'json',
                success: function (data) {
                    if (typeof callback === 'function') {
                        callback.call($this, data);
                    }
                }
            });
        },
        appendBadge: function (badge) {
            $('.user_content .badges', this.container).append(badge);
        },
        addUser: function ($form, callback) {

            var $this = this;

            $.ajax({
                type: 'POST',
                url: '/prod/push/add-user/',
                dataType: 'json',
                data: $form.serializeArray(),
                success: function (data) {
                    if (data.success) {
                        humane.info(data.message);
                        $this.selectUser(data.user);
                        callback();
                    }
                    else {
                        humane.error(data.message);
                    }
                }
            });
        },
        getSelection: function () {
            return this.selection;
        },
        getUsers: function () {
            return $('.user_content .badge', this.container).map(function () {
                return $('input[name="id"]', $(this)).val();
            });
        }
    };


    var ListManager = function ($container) {

        this.list = null;
        this.container = $container;

        $container.on('click', '.back_link', function() {
            $('#PushBox').show();
            $('#ListManager').hide();
            return false;
        });

        $container.on('click', 'a.list_sharer', function() {

            var $this = $(this),
                options = {
                    size: 'Small',
                    closeButton: true,
                    title: $this.attr('title')
                },
                $dialog = p4.Dialog.Create(options, 2);

            $dialog.load($this.attr('href'), 'GET');

            return false;
        });

        $container.on('click', 'a.user_adder', function() {

            var $this = $(this);

            $.ajax({
                type: "GET",
                url: $this.attr('href'),
                dataType: 'html',
                beforeSend: function () {
                    var options = {
                        size: 'Medium',
                        title: $this.html()
                    };
                    p4.Dialog.Create(options, 2).getDomElement().addClass('loading');
                },
                success: function (data) {
                    p4.Dialog.get(2).getDomElement().removeClass('loading').empty().append(data);
                    return;
                },
                error: function () {
                    p4.Dialog.get(2).Close();
                    return;
                },
                timeout: function () {
                    p4.Dialog.get(2).Close();
                    return;
                }
            });

            return false;
        });


        var initLeft = function () {
            $('a.list_refresh', $container).bind('click', function (event) {

                var callback = function (datas) {
                    $('.all-lists', $container).removeClass('loading').append(datas);
                    initLeft();
                };

                $('.all-lists', $container).empty().addClass('loading');

                p4.Lists.get(callback, 'html');

                return false;
            });

            $('a.list_adder', $container).bind('click', function (event) {

                var makeDialog = function (box) {

                    var buttons = {};

                    buttons[language.valider] = function () {

                        var callbackOK = function () {
                            $('a.list_refresh', $container).trigger('click');
                            p4.Dialog.get(2).Close();
                        };

                        var name = $('input[name="name"]', p4.Dialog.get(2).getDomElement()).val();

                        if ($.trim(name) === '') {
                            alert(language.listNameCannotBeEmpty);
                            return;
                        }

                        p4.Lists.create(name, callbackOK);
                    };

                    var options = {
                        cancelButton: true,
                        buttons: buttons,
                        size: '700x170'
                    };

                    p4.Dialog.Create(options, 2).setContent(box);
                };

                var html = _.template($("#list_editor_dialog_add_tpl").html());

                makeDialog(html);

                return false;
            });

            $('li.list a.list_link', $container).bind('click', function (event) {

                var $this = $(this);

                $this.closest('.lists').find('.list.selected').removeClass('selected');
                $this.parent('li.list').addClass('selected');

                $.ajax({
                    type: 'GET',
                    url: $this.attr('href'),
                    dataType: 'html',
                    success: function (data) {
                        $('.editor', $container).removeClass('loading').append(data);
                        initRight();
                    },
                    beforeSend: function () {
                        $('.editor', $container).empty().addClass('loading');
                    }
                });

                return false;
            });
        };

        var initRight = function () {

            var $container = $('#ListManager .editor');

            $('form[name="list-editor-search"]', $container).bind('submit', function () {

                var $this = $(this);
                var dest = $('.list-editor-results', $container);

                $.ajax({
                    url: $this.attr('action'),
                    type: $this.attr('method'),
                    dataType: "html",
                    data: $this.serializeArray(),
                    beforeSend: function () {
                        dest.empty().addClass('loading');
                    },
                    success: function (datas) {

                        dest.empty().removeClass('loading').append(datas);
                    }
                });
                return false;
            });

            $('form[name="list-editor-search"] select, form[name="list-editor-search"] input[name="ListUser"]', $container).bind('change', function () {
                $(this).closest('form').trigger('submit');
            });

            $('.EditToggle', $container).bind('click', function () {
                $('.content.readonly, .content.readwrite', $('#ListManager')).toggle();
                return false;
            });
            $('.Refresher', $container).bind('click', function () {
                $('#ListManager ul.lists .list.selected a').trigger('click');
                return false;
            });

            $('form[name="SaveName"]', $container).bind('submit', function () {
                var $this = $(this);

                $.ajax({
                    type: $this.attr('method'),
                    url: $this.attr('action'),
                    dataType: 'json',
                    data: $this.serializeArray(),
                    beforeSend: function () {

                    },
                    success: function (data) {
                        if (data.success) {
                            humane.info(data.message);
                            $('#ListManager .lists .list_refresh').trigger('click');
                        }
                        else {
                            humane.error(data.message);
                        }
                        return;
                    },
                    error: function () {

                        return;
                    },
                    timeout: function () {

                        return;
                    }
                });

                return false;
            });


            $('button.deleter', $container).bind('click', function (event) {

                var list_id = $(this).data("list-id");

                var makeDialog = function (box) {

                    var buttons = {};

                    buttons[language.valider] = function () {

                        var callbackOK = function () {
                            $('#ListManager .all-lists a.list_refresh').trigger('click');
                            p4.Dialog.get(2).Close();
                        };

                        var List = new document.List(list_id);
                        List.remove(callbackOK);
                    };

                    var options = {
                        cancelButton: true,
                        buttons: buttons,
                        size: 'Alert'
                    };

                    p4.Dialog.Create(options, 2).setContent(box);
                };

                var html = _.template($("#list_editor_dialog_delete_tpl").html());

                makeDialog(html);

                return false;
            });
        };

        initLeft();

        $('.badges a.deleter', this.container).on('click', function () {
            var badge = $(this).closest('.badge');

            var usr_id = badge.find('input[name="id"]').val();


            var callback = function (list, datas) {
                $('.counter.current, .list.selected .counter', $('#ListManager')).each(function () {
                    $(this).text(parseInt($(this).text()) - 1);
                });

                badge.remove();
            };

            p4.ListManager.getList().removeUser(usr_id, callback);

            return false;
        });

    };

    ListManager.prototype = {
        workOn: function (list_id) {
            this.list = new document.List(list_id);
        },
        getList: function () {
            return this.list;
        },
        appendBadge: function (datas) {
            $('#ListManager .badges').append(datas);
        }
    };


    window.Feedback = Feedback;
    window.ListManager = ListManager;

}(window));
