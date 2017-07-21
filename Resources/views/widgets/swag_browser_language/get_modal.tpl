{block name="frontend_index_browser_language_modal"}
	<div class="modal--box" style="margin: 10px;">
		{block name="frontend_index_browser_language_modal_content"}
			<div class="modal--content">
				<p>{$snippets.recommendation}<b> {$destinationShop}</b></p>

				<p>{$snippets.text}</p>
			</div>
		{/block}
		{block name="frontend_index_browser_language_modal_subShops"}
			{$snippets.choose}
			<form name="modal--shops">
				<select name="modal--combo-shops" class="language--select modal--language-select">
					{foreach $shops as $key => $shop}
						<option value="{$key}" {if $destinationId===$key}selected="selected"{/if}>{$shop}</option>
					{/foreach}
				</select>
			</form>
			<br/>
		{/block}
		{block name="frontend_index_browser_language_modal_actions"}
			<div class="modal--actions">
				<div class="btn is--secondary is--left is--icon-right is--large modal--close-button" rel="nofollow"
					 title="{$snippets.close}">
					{$snippets.close}
					<i class="icon--arrow-right"></i>
				</div>

				<a class="btn is--primary right is--icon-right is--large modal--go-button" title="{$snippets.go}">
					{$snippets.go}
					<i class="icon--arrow-right"></i>
				</a>
			</div>
		{/block}
	</div>
{/block}