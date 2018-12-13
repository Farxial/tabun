				{hook run='content_end'}
			</div> <!-- /content -->
		</div> <!-- /content-wrapper -->
	</div> <!-- /wrapper -->



	<footer id="footer">
		<p>Все права принадлежат пони. Весь мир принадлежит пони.</p>
		<div class="text version">
            <div>Версия Табуна: <strong>{cfg name='misc.ver.code'}</strong></div>
            {if {cfg name='misc.debug'} == true}
                <div>Версия фронтенда: <strong>{cfg name='misc.ver.front'}</strong></div>
            {/if}
            <div><a href="https://bitbucket.org/Orhideous/tabun/issues/new" target="_blank">Сообщить об ошибке</a></div>
		</div>
		{hook run='footer_end'}
	</footer>
</div> <!-- /container -->

{include file='toolbar.tpl'}
<script type="text/javascript">
    var LIVESTREET_SECURITY_KEY = '{$LIVESTREET_SECURITY_KEY}';
    var RECAPTCHA_KEY = '{cfg name='recaptcha.key'}';
</script>
<script type="text/javascript">
    var UI = {
        smothScroll: window.localStorage.getItem('UI-smothScroll') !== "false",
        hotkeys: window.localStorage.getItem('UI-hotkeys') !== "false",
        smartQuote: window.localStorage.getItem('UI-smartQuote') !== "false",
        autoFold: window.localStorage.getItem('UI-autoFold') !== "false",
        newCommentsInTitle: window.localStorage.getItem('UI-newCommentsInTitle') !== "false",
        autoUpdateComments: window.localStorage.getItem('UI-autoUpdateComments') === "true",
        autoDespoil: window.localStorage.getItem('UI-autoDespoil') === "true",
        despoilOnlyArticle: window.localStorage.getItem('UI-despoilOnlyArticle') === "true",
        showPinkie: window.localStorage.getItem('UI-showPinkie') !== "false",
        voteNeutral: window.localStorage.getItem('UI-voteNeutral') !== "false"
    }
    var Capabilities = {
        allowCommentsEditingLock: {if $oConfig->getValue('acl.edit.comment.enable_lock')}true{else}false{/if}
    }
</script>
<script src="{cfg name='path.static.url'}/vendor.{cfg name='misc.ver.front'}.js" type="text/javascript"></script>
<script src="{cfg name='path.static.url'}/main.{cfg name='misc.ver.front'}.js" type="text/javascript"></script>

<script type="text/javascript">
    ls.tools.registry.loadJSON({json var=$aFrontendRegistry});
    ls.lang.load({json var = $aLangJs});
</script>
{if isset($scripts)}
    {foreach from=$scripts item=item}
        <script src="{cfg name='path.static.url'}/{$item}.{cfg name='misc.ver.front'}.js" type="text/javascript"></script>
    {/foreach}
{/if}
{include 'analytics.tpl'}
{hook run='body_end'}

</body>
</html>
