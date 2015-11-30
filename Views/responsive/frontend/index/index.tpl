{extends file="parent:frontend/index/index.tpl"}

{block name="frontend_index_before_page" append}
    {block name="frontend_index_before_page_browser_language"}
    	{include file="frontend/plugins/swag_browser_language/before_page.tpl"}
    {/block}
{/block}