{block name="frontend_index_browser_language_modal"}
	<div class="modal--box" style="height: 50%; padding-left: 10px; padding-top: 10px; padding-right: 10px">
		{block name="frontend_index_browser_language_modal_content"}
			<div class="modal--content">
				<p>{s namespace="frontend/swag_browser_language/main" name="modal/recommendation"}Recommended shop:{/s}<b> {$destinationShop}</b></p>
				<p>{s namespace="frontend/swag_browser_language/main" name="modal/text"}We automatically redirected you to the shop in your language.<br>If you don't want that, you can move back to the main shop.{/s}</p>
			</div>
		{/block}
		{block name="frontend_index_browser_language_modal_subShops"}
			{s namespace="frontend/swag_browser_language/main" name="modal/choose"}Or choose a shop below:{/s}
			<form name="modal--shops">
				<select name="modal--combo-shops" class="language--select">
					{foreach $shops as $key => $shop}
						<option value="{$key}" {if $destinationId===$key}selected="selected"{/if}>{$shop}</option>
					{/foreach}
				</select>
			</form>
			<br/>
		{/block}
		{block name="frontend_index_browser_language_modal_actions"}
			<div class="modal--actions">
				<a class="btn is--primary right is--icon-right is--large modal--go-button" title="{s namespace="frontend/swag_browser_language/main" name="modal/go"}Go to this shop{/s}">
					{s namespace="frontend/swag_browser_language/main" name="modal/go"}Go to shop '{$destinationShop}'{/s}
				</a>
				<div class="btn is--secondary is--left is--icon-left is--large modal--close-button" rel="nofollow" title="{s namespace="frontend/swag_browser_language/main" name="modal/close"}Close window{/s}">
					{s namespace="frontend/swag_browser_language/main" name="modal/close"}Close window{/s}
					<i class="icon--arrow-right"></i>
				</div>
			</div>
		{/block}
	</div>
{/block}