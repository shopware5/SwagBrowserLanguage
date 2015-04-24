;(function ($, undefined) {

    $.plugin('swModalBox', {

        /**
         * The default options.
         * Mainly contains the selectors for the events.
         * You can set those by using data-attributes in HTML e.g. "data-quantitySelector='abc'"
         */
        defaults: {

            modalBox: '.modal--box',

            close: '.modal--close'
        },

        /** Plugin constructor */
        init: function () {
            var me = this;

            me.$modalBox = $(me.opts.modalBox);
            me.$close = $(me.opts.close);

            me.registerEvents();
        },

        /**
         * Method to register all the events
         */
        registerEvents: function () {
            var me = this;

            me._on(me.$close, 'click', $.proxy(me.onClickClose, me));
        },

        /**
         * Method to handle close of modal box
         *
         * @param event
         */
        onClickClose: function() {
            var me = this;

            me.$modalBox.fadeOut();
        },


        /** Destroys the plugin */
        destroy: function() {
            this._destroy();
        }

    });

    $('.modal--box').swModalBox();
})(jQuery);