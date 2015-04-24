{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_before_page" append}
    {if $show_modal}
        <div class="modal--box">
            <div>
                <div class="modal--heading">
                    <h2>
                        {s namespace="frontend/swag_browser_language/main" name="modal/main_title"}Automatic forwarding{/s}
                    </h2>
                    <a class="btn is--small action--remove modal--close" title="{s namespace="frontend/swag_browser_language/main" name="modal/close"}Close window{/s}">
                        <i class="icon--cross"></i>
                    </a>
                </div>
                <div class="modal--content">
                    <p>{s namespace="frontend/swag_browser_language/main" name="modal/text"}We automatically redirected you to the shop in your language.<br>If you don't want that, you can move back to the main shop.{/s}</p>
                </div>
                <div class="modal--actions">
                    <a class="btn is--secondary is--left is--icon-left is--large modal--close" title="{s namespace="frontend/swag_browser_language/main" name="modal/back"}Back to main shop{/s}" href="{url controller='SwagBrowserLanguage' action='index'}">
                        {s namespace="frontend/swag_browser_language/main" name="modal/back"}Back to main shop{/s}
                    </a>
                    <a class="btn is--primary right is--icon-right is--large modal--close" rel="nofollow" title="{s namespace="frontend/swag_browser_language/main" name="modal/close"}Close window{/s}">
                        {s namespace="frontend/swag_browser_language/main" name="modal/close"}Close window{/s}
                        <i class="icon--arrow-right"></i>
                    </a>
                </div>
            </div>
        </div>
    {/if}
{/block}