;(function ($, undefined) {
    var sessionStorage = StorageManager.getSessionStorage();

    $.plugin('swRedirectLang', {

        /**
         * The default options.
         */
        defaults: {
            controllerName: 'index',

            moduleName: 'frontend',

            modalTitle: 'You can be redirected',

            modalURL: ''
        },

        /** Plugin constructor */
        init: function () {
            var me = this;

            me.applyDataAttributes(false);

            $.subscribe('plugin/swModal/onOpenAjax', $.proxy(me.onOpenModal, me));

            if(!sessionStorage.getItem('swBrowserLanguage_redirected')) {
                me.handleRedirect();
            }
        },

        onOpenModal: function() {
            $('.modal--language-select').swSelectboxReplacement();
        },

        showModal: function() {
            var me = this,
                url = me.opts.modalURL;

            if (!url) {
                return;
            }

            $.modal.open(url, {
                title: me.opts.modalTitle,
                mode: 'ajax',
                sizing: 'content'
            });

            $($.modal._$modalBox).one('click.swag_browser_language', '.modal--close-button', function () {
                $.modal.close();
            });

            $($.modal._$modalBox).one('click.swag_browser_language', '.modal--go-button', function () {
                me.redirect(sessionStorage.getItem("swBrowserLanguage_destinationId"));
            });

            $($.modal._$modalBox).on('change.swag_browser_language', '*[name="modal--combo-shops"]', function (event) {
                var $this = $(event.target),
                    val = $this.val();

                sessionStorage.setItem("swBrowserLanguage_destinationId", val);
                me.redirect(val);
            });
        },

        handleRedirect: function() {
            var me = this;

            sessionStorage.setItem("swBrowserLanguage_redirected", true);

            $.ajax({
                method: 'post',
                data: me.opts,
                url: me.$el.attr('data-redirectUrl'),
                success: function (response) {
                    var data = JSON.parse(response);

                    if(data.destinationId) {
                        sessionStorage.setItem("swBrowserLanguage_destinationId", data.destinationId);
                    }

                    me.showModal();
                }
            });

            $.publish('swRedirect');
        },

        redirect: function(shopId) {

            $('<form>', {
                'action': '',
                'method': 'post',
                'html': $('<input>', {
                    'type': 'hidden',
                    'value': shopId,
                    'name': '__shop'
                })
            }).appendTo('body').submit();
        },

        /** Destroys the plugin */
        destroy: function() {
            this._destroy();
        }

    });

    $(document).ready(function() {
        $('.language--redirect-container').swRedirectLang();
    });
})(jQuery);