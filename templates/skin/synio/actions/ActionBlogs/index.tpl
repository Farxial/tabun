{include file='header.tpl' sMenuHeadItemSelect="blogs"}

<h2 class="page-header">{$aLang.blogs}</h2>

<form
    action=""
    method="POST"
    onsubmit="return false;"
    class="search-item"
    data-bazooka="blogs_search"
>
	<div class="search-input-wrapper">
		<input
            type="text"
            placeholder="{$aLang.blogs_search_title_hint}"
            autocomplete="off"
            name="blog_title"
            class="input-text"
            value=""
        >
	</div>
</form>

<div id="blogs-list-search"></div>

<div id="blogs-list-original">
	{router page='blogs' assign=sBlogsRootPage}
	{include file='blog_list.tpl' bBlogsUseOrder=true sBlogsRootPage=$sBlogsRootPage}
	{include file='paging.tpl' aPaging=$aPaging}
</div>

{include file='footer.tpl' scripts=["blogs"]}