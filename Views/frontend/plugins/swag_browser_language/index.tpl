{block name="frontend_index_logo" append}
    {if $show_modal}
        <div class="modal" style="width: 530px; display: block; margin-left: -265px; position: fixed; top: 40px;">
            <div>
                <div class="heading">
                    <h2>
                        <span class="">Automatische Weiterleitung</span>
                    </h2>
                    <a class="modal_close" title="Fenster schließen">Fenster schließen</a>
                </div>
                <div>
                    <p>Wir haben Sie automatisch auf den Shop Ihrer Sprache weitergeleitet. War das nicht gewünscht, können sie hier auch zum Hauptshop zurückkehren.</p>
                </div>
                <div class="actions">
                    <a class="button-middle large modal_close" title="Zurück zum Hauptshop" href="{url controller='SwagBrowserLanguage' action='index'}">
                        <span class="frontend_checkout_ajax_add_article">Zurück zum Hauptshop</span>
                    </a>
                    <a class="button-right large right modal_close" rel="nofollow" title="Fenster schließen">
                        <span class="frontend_checkout_ajax_add_article">Fenster schließen</span>
                    </a>
                    <div class="clear"> </div>
                </div>
            </div>
        </div>
    {/if}
{/block}