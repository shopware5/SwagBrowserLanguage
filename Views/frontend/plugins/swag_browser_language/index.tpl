{namespace name="frontend/swag_browser_language/main"}

{block name="frontend_index_logo" append}
    {if $show_modal}
        <div class="modal" style="width: 530px; display: block; margin-left: -265px; position: fixed; top: 40px; padding-left:5px">
            <div>
                <div class="heading">
                    <h2>
                        {s name=modal/main_title}Automatische Weiterleitung{/s}
                    </h2>
                    <a class="modal_close" title="{s name=modal/close}Fenster schließen{/s}">{s name=modal/close}Fenster schließen{/s}</a>
                </div>
                <div style="padding: 10px 25px">
                    <p>{s name=modal/text}Wir haben Sie automatisch auf den Shop Ihrer Sprache weitergeleitet.<br>War das nicht gewünscht, können sie hier auch zum Hauptshop zurückkehren.{/s}</p>
                </div>
                <div class="actions" style="padding:0 15px 15px 15px;">
                    <a class="button-middle large modal_close" title="{s name=modal/close}Zurück zum Hauptshop{/s}" href="{url controller='SwagBrowserLanguage' action='index'}">
                        {s name=modal/back}Zurück zum Hauptshop{/s}
                    </a>
                    <a class="button-right large right modal_close" rel="nofollow" title="{s name=modal/close}Fenster schließen{/s}">
                        {s name=modal/close}Fenster schließen{/s}
                    </a>
                </div>
            </div>
        </div>
    {/if}
{/block}