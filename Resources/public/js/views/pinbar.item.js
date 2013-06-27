var navigation = navigation || {};
navigation.pinbar = navigation.pinbar || {};

navigation.pinbar.ItemView = Backbone.View.extend({

    options: {
        type: 'list'
    },

    tagName:  'li',

    isRemoved: false,

    templates: {
        list: _.template($("#template-list-pin-item").html()),
        tab: _.template($("#template-tab-pin-item").html())
    },

    events: {
        'click .btn-close': 'unpin',
        'click .close': 'unpin',
        'click .pin-holder div a': 'maximize',
        'click span': 'maximize'
    },

    initialize: function() {
        this.listenTo(this.model, 'destroy', this.removeItem);
        this.listenTo(this.model, 'change:display_type', this.removeItem);
        /**
         * Change active pinbar item after hash navigation request is completed
         */
        Oro.Events.bind(
            "hash_navigation_request:complete",
            function() {
                if (!this.isRemoved && this.checkCurrentUrl()) {
                    this.maximize();
                }
                this.setActiveItem();
            },
            this
        );
    },

    unpin: function()
    {
        this.model.destroy({wait: false});
        return false;
    },

    maximize: function() {
        this.model.set('maximized', new Date().toISOString());
        return false;
    },

    removeItem: function() {
        this.isRemoved = true;
        this.remove();
    },

    checkCurrentUrl: function() {
        var url = '';
        if (Oro.hashNavigationEnabled()) {
            url = Oro.Navigation.prototype.getHashUrl();
        } else {
            url = window.location.pathname;
        }
        return this.cleanupUrl(this.model.get('url')) == this.cleanupUrl(url);
    },

    cleanupUrl: function(url) {
        if (url) {
            url = url.replace(/(\?|&)restore=1/ig, '');
        }
        return url;
    },

    setActiveItem: function() {
        if (this.checkCurrentUrl()) {
            this.$el.addClass('active');
        } else {
            this.$el.removeClass('active');
        }
    },

    render: function () {
        this.$el.html(
            this.templates[this.options.type](this.model.toJSON())
        );
        this.setActiveItem();
        return this;
    }
});
