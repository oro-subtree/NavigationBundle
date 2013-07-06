var navigation = navigation || {};
navigation.pinbar = navigation.pinbar || {};

navigation.pinbar.MainView = navigation.MainViewAbstract.extend({
    options: {
        maxItems: 10,
        tabTitle: 'Pinbar',
        tabIcon: 'icon-folder-close',
        el: '.pin-bar',
        listBar: '.list-bar',
        minimizeButton: '.top-action-box .minimize-button',
        closeButton: '.top-action-box .close-button',
        history: [],
        defaultUrl: '/',
        tabId: 'pinbar',
        collection: navigation.pinbar.Items
    },

    requireCleanup: true,
    massAdd: false,

    templates: {
        noItemsMessage: _.template($("#template-no-pins-message").html())
    },

    initialize: function() {
        this.$listBar = this.getBackboneElement(this.options.listBar);
        this.$minimizeButton = Backbone.$(this.options.minimizeButton);
        this.$closeButton = Backbone.$(this.options.closeButton);

        this.listenTo(this.options.collection, 'add', function(item) {this.setItemPosition(item)});
        this.listenTo(this.options.collection, 'remove', this.onPageClose);
        this.listenTo(this.options.collection, 'reset', this.addAll);
        this.listenTo(this.options.collection, 'all', this.render);

        this.listenTo(this.options.collection, 'positionChange', this.renderItem);
        this.listenTo(this.options.collection, 'stateChange', this.handleItemStateChange);
        this.listenTo(this.options.collection, 'urlChange', this.renderItem);

        this.$minimizeButton.click(_.bind(this.minimizePage, this));
        this.$closeButton.click(_.bind(this.closePage, this));

        this.registerTab();
        this.cleanup();
        this.render();
    },

    /**
     * Get previous maximized URL
     *
     * @return {*}
     */
    getLatestUrl: function() {
        if (this.options.history.length) {
            return _.last(this.options.history);
        } else {
            return this.options.defaultUrl;
        }
    },

    /**
     * Get backbone DOM element
     *
     * @param el
     * @return {*}
     */
    getBackboneElement: function(el) {
        return el instanceof Backbone.$ ? el : this.$(el);
    },

    /**
     * Handle item minimize/maximize state change
     *
     * @param item
     */
    handleItemStateChange: function(item) {
        if (!this.massAdd) {
            var url = null;
            var goBack = false;
            if (item.get('maximized')) {
                url = item.get('url');
                this.removeFromHistory(item);
                this.options.history.push(this.cleanupUrl(url));
            } else {
                goBack = true;
            }
            if (this.cleanupUrl(url) != this.cleanupUrl(this.getCurrentPageItemData().url)) {
                item.save(null, {success: _.bind(function () {
                    if (!goBack) {
                        Oro.Navigation.prototype.setLocation(url);
                    } else {
                        this.goToLatestOpenedPage();
                    }
                }, this)});
            }
        }
    },

    /**
     * Remove item from history
     *
     * @param item
     */
    removeFromHistory: function(item) {
        var currentItemUrl = this.cleanupUrl(item.get('url'))
        this.options.history = _.filter(this.options.history, function (url) {
            return url != currentItemUrl;
        });
    },

    /**
     * Handle page close
     */
    onPageClose: function(item) {
        this.removeFromHistory(item);
        if (item.get('url') == this.getCurrentPageItemData().url) {
            this.goToLatestOpenedPage();
        }
        this.reorder();
    },

    /**
     * Go to latest maximized page
     */
    goToLatestOpenedPage: function()
    {
        if (window.history.length) {
            window.history.back();
        } else {
            if (Oro.hashNavigationEnabled()) {
                Oro.Navigation.prototype.setLocation(this.getLatestUrl());
            } else {
                window.location.href = this.getLatestUrl();
            }
        }
    },

    /**
     * Handle minimize page.
     *
     * @param e
     */
    minimizePage: function(e) {
        var pinnedItem = this.getItemForCurrentPage();
        if (pinnedItem.length) {
            _.each(pinnedItem, function(item) {
                this.removeFromHistory(item);
                item.set('maximized', false);
            }, this);
        } else {
            var newItem = this.getNewItemData(Backbone.$(e.currentTarget));
            newItem.url = this.cleanupUrl(newItem.url);
            var currentItem = new navigation.pinbar.Item(newItem);
            this.options.collection.unshift(currentItem);
            this.handleItemStateChange(currentItem);
        }
    },

    /**
     * Handle click on page close button
     */
    closePage: function()
    {
        var pinnedItem = this.getItemForCurrentPage();
        if (pinnedItem.length) {
            _.each(pinnedItem, function(item) {item.destroy({wait: false});});
        } else {
            this.goToLatestOpenedPage();
        }
    },

    /**
     * Mass add items
     */
    addAll: function() {
        this.massAdd = true;
        this.markCurrentPageMaximized();
        this.options.collection.each(this.setItemPosition, this);
        this.massAdd = false;
    },

    /**
     * Mark current page as maximized to be able to minimize.
     */
    markCurrentPageMaximized: function()
    {
        var currentPageItems = this.getItemForCurrentPage();
        if (currentPageItems.length) {
            _.each(currentPageItems, function(item) {
                item.set('maximized', new Date().toISOString());
            });
        }
    },

    /**
     * Set item position if given or reorder items.
     *
     * @param {navigation.pinbar.Item} item
     * @param {Integer} position
     */
    setItemPosition: function(item, position) {
        if (_.isUndefined(position)) {
            this.reorder();
        } else {
            item.set({position: position});
        }
    },

    /**
     * Change position property of model based on current order
     */
    reorder: function() {
        this.options.collection.each(function(item, position) {
            item.set({position: position});
        });
    },

    /**
     * Choose container and add item to it.
     *
     * @param {navigation.pinbar.Item} item
     */
    renderItem: function(item) {
        var position = item.get('position');
        var type = position >= this.options.maxItems ? 'tab': 'list';

        if (item.get('display_type') != type) {
            this.cleanup();
            item.set('display_type', type);

            var view = new navigation.pinbar.ItemView({
                type: type,
                model: item
            });

            if (type == 'tab') {
                this.addItemToTab(view, !this.massAdd);
                /**
                 * Backbone event. Fired when tab is changed
                 * @event tab:changed
                 */
                Oro.Events.trigger("tab:changed", this.options.tabId);
            } else {
                var rowEl = view.render().el;
                if (this.massAdd || position > 0) {
                    this.$listBar.append(rowEl);
                } else {
                    this.$listBar.prepend(rowEl);
                }
            }
        }
    },

    /**
     * Checks if pinbar tab in 3 dots menu is used
     *
     * @return {Boolean}
     */
    needPinbarTab: function() {
        return (this.options.collection.length > this.options.maxItems);
    },

    /**
     * Clean up all pinbar items from menus
     */
    cleanup: function()
    {
        if (this.requireCleanup) {
            this.$listBar.empty();
            this.cleanupTab();
            this.requireCleanup = false;
        }
    },

    /**
     * Renders pinbar empty message if no items
     * Show/hide tabs section in ... menu on each event
     */
    render: function() {
        if (!this.massAdd) {
            if (this.options.collection.length == 0) {
                this.requireCleanup = true;
                this.$listBar.html(this.templates.noItemsMessage());
                /**
                 * Backbone event. Fired when pinbar help link is shown
                 * @event pinbar_help:shown
                 */
                Oro.Events.trigger("pinbar_help:shown");
            }

            this.checkTabContent();
            /**
             * Backbone event. Fired when tab is changed
             * @event tab:changed
             */
            Oro.Events.trigger("tab:changed", this.options.tabId);
        }
    }
});
