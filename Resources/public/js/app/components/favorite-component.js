/*jslint nomen:true*/
/*global define*/
define([
    'underscore',
    './base/bookmark-component',
    '../views/base/button-view',
    'oroui/js/app/views/base/collection-view',
    '../views/base/item-view'
], function (_, MainView, ButtonView, CollectionView, ItemView) {
    'use strict';

    var FavoriteView;

    FavoriteView = MainView.extend({
        createSubViews: function (options) {
            var collection, button,
                tabView, TabItemView, tabOptions;

            collection = this.collection;

            // button view
            button = new ButtonView({
                autoRender: true,
                el: 'favButton',
                collection: collection
            });
            this.subview('button', button);

            // tab view
            TabItemView = ItemView.extend({
                template: options.tabItemTemplate
            });
            tabOptions = _.extend(options.tabOptions, {
                autoRender: true,
                el: 'favTab',
                collection: collection,
                itemView: TabItemView
            });
            tabView = new CollectionView(tabOptions);
            this.subview('tab', tabView);
        },

        actualizeAttributes: function (model) {
            model.set('type', 'favorite');
            model.set('position', this.collection.length);
        }
    });

    return FavoriteView;
});