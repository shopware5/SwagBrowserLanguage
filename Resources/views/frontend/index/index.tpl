{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_before_page" append}
    {block name="frontend_index_before_page_browser_language"}
        <div class="language--redirect-container"
             data-redirectUrl="{url module=widgets controller=SwagBrowserLanguage action=redirect}"
             data-controllerName="{$Controller}"
             data-moduleName="{$module}"
             data-modalTitle="Automatic forwarding available"
             data-modalURL="{url module=widgets controller=SwagBrowserLanguage action=getModal}">
        </div>
    {/block}
{/block}