{strip}
<a class="close-button" href="javascript:;">{$aLang.close}</a>
<div class="vote-list-wrapper">
	<div class="vote-list">
	{foreach from=$aVotes item=oVote}
		<div class="vote-list-item">
		{assign var="oUser" value=$LS->User_GetUserById($oVote->getVoterId())}
		{assign var="fVoteValue" value=$oVote->getValue()}
		{assign var="sVoteDate" value=$oVote->getDate()}
		
		{if $oUser and (strtotime($sVoteDate) > $iExposeFromDate or $bSuperuserAccessGranted)}
			<a href="/profile/{$oUser->getLogin()}" target="_blank" class="vote-list-item-component ls-user has-avatar">
				<img class="vote-avatar" src="{$oUser->getProfileAvatarPath()}" />
				{$oUser->getLogin()}
			</a>
		{else}
			<span class="vote-list-item-component ls-user undefined">—</span>
		{/if}
			<time class="vote-list-item-component" datetime="{date_format date=$sVoteDate format='c'}">{date_format date=$sVoteDate format="j F Y, H:i:s"}</time>
			<span class="vote-list-item-component vote" data-value="{if $fVoteValue < 0}−{-$fVoteValue}{else}+{+$fVoteValue}{/if}"></span>
		</div>
	{/foreach}
	</div>
</div>
{/strip}
