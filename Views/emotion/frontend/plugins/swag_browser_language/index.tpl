{namespace name="frontend/swag_browser_language/main"}

{block name="frontend_index_logo" append}
    {if $show_modal}
        <div class="modal" style="width: 530px; display: block; margin-left: -265px; position: fixed; top: 40px; padding-left:5px">
            <div>
                <div class="heading">
                    <h2>
                        {s name=modal/main_title}Automatic forwarding{/s}
                    </h2>
                    <a class="modal_close" title="{s name=modal/close}Close window{/s}">{s name=modal/close}Close window{/s}</a>
                </div>
                <div style="padding: 10px 25px">
                    <p>{s name=modal/text}We automatically redirected you to the shop in your language.<br>If you don't want that, you can move back to the main shop.{/s}</p>
                </div>
                <div class="actions" style="padding:0 15px 15px 15px;">
                    <a class="button-middle large modal_close" title="{s name=modal/back}Back to main shop{/s}" href="{url controller='SwagBrowserLanguage' action='index'}">
                        {s name=modal/back}Back to main shop{/s}
                    </a>
                    <a class="button-right large right modal_close" rel="nofollow" title="{s name=modal/close}Close window{/s}">
                        {s name=modal/close}Close window{/s}
                    </a>
                </div>
            </div>
        </div>
    {/if}
{/block}