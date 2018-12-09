{strip}
{assign var="oCommentAuthor" value=$oComment->getUser()}
{assign var="oCommentAuthorLogin" value=$oCommentAuthor->getLogin()}
{assign var="oCommentAuthorId" value=$oComment->getUserId()}
{assign var="oCommentId" value=$oComment->getId()}
{assign var="oCommentRating" value=$oComment->getRating()}
{assign var="oCommentDate" value=$oComment->getDate()}
{if $oUserCurrent}
    {assign var="oCommentVote" value=$oComment->getVote()}
    {assign var="oCommentVoteCount" value=$oComment->getCountVote()}
    {if $oCommentAuthorId == $oUserCurrent->getId()}
        {assign var="oCommentSelf" value="comment-self"}
    {/if}
    {if $sDateReadLast <= $oCommentDate}
        {assign var="oCommentNew" value="comment-new"}
    {/if}
{/if}

{if $oCommentRating <= $oConfig->GetValue('module.user.bad_rating')}
    {if $oUserCurrent and $oUserCurrent->isAdministrator()}
        <section data-id="{$oCommentId}" id="comment_id_{$oCommentId}" class="comment comment-deleted comment-bad">
            <a id="comment{$oCommentId}"></a>
            <div class="text current">{$oComment->getText()}</div>
            <div class="comment-info" data-id="{$oCommentId}">
                <a href="/profile/{$oCommentAuthorLogin}" data-user_id="{$oCommentAuthorId}">
                    <img src="{$oCommentAuthor->getProfileAvatarPath(24)}" class="comment-avatar"/>
                </a>
                <a class="comment-author" href="/profile/{$oCommentAuthorLogin}">{$oCommentAuthorLogin}</a>
                <time class="comment-date" datetime="{date_format date=$oCommentDate format='c'}" title="{$oCommentDate}">
                    {date_format date=$oCommentDate format="j F Y, H:i"}
                </time>
                <a class="comment-link" href="#comment{$oCommentId}" title="{$aLang.comment_url_notice}"></a>
                {if $oComment->getPid()}
                    <a href="#comment{$oComment->getPid()}" class="goto goto-comment-parent" title="{$aLang.comment_goto_parent}">↑</a>
                {/if}
                <span>{$aLang.comment_was_hidden}</span>
            </div>
    {else}
        <section data-id="{$oCommentId}" id="comment_id_{$oCommentId}" class="comment comment-deleted">
            <div class="text current"><em>{$aLang.comment_was_hidden}</em></div>
            <div class="comment-info" data-id="{$oCommentId}"></div>
    {/if}
{elseif $oComment->getDelete()}
    {if $oUserCurrent and $oUserCurrent->isAdministrator()}
        <section data-id="{$oCommentId}" id="comment_id_{$oCommentId}" class="comment comment-deleted comment-bad">
            <a id="comment{$oCommentId}"></a>
            <div class="text current">{$oComment->getText()}</div>
            <div class="comment-info" data-id="{$oCommentId}">
                <a href="/profile/{$oCommentAuthorLogin}" data-user_id="{$oCommentAuthorId}">
                    <img src="{$oCommentAuthor->getProfileAvatarPath(24)}" class="comment-avatar"/>
                </a>
                <a class="comment-author" href="/profile/{$oCommentAuthorLogin}">{$oCommentAuthorLogin}</a>
                <time class="comment-date" datetime="{date_format date=$oCommentDate format='c'}" title="{$oCommentDate}">
                    {date_format date=$oCommentDate format="j F Y, H:i"}
                </time>
                <a class="comment-link" href="#comment{$oCommentId}" title="{$aLang.comment_url_notice}"></a>
                {if $oComment->getPid()}
                    <a href="#comment{$oComment->getPid()}" class="goto goto-comment-parent" title="{$aLang.comment_goto_parent}">↑</a>
                {/if}
                <a class="comment-repair link-dotted">{$aLang.comment_repair}</a>
                <span>{$aLang.comment_was_delete}</span>
            </div>
    {else}
        <section data-id="{$oCommentId}" id="comment_id_{$oCommentId}" class="comment comment-deleted">
            <a id="comment{$oCommentId}"></a>
            <div class="text current"><em>{$aLang.comment_was_delete}</em></div>
            <div class="comment-info" data-id="{$oCommentId}"></div>
    {/if}
{else}
    <section data-id="{$oCommentId}" id="comment_id_{$oCommentId}" class="comment {if $oComment->isBad()}comment-bad{/if} {$oCommentSelf} {$oCommentNew}">
        <a id="comment{$oCommentId}"></a>
        <div id="comment_content_id_{$oCommentId}" class="comment-content">
            <div class="text current">{$oComment->getText()}</div>
        </div>
        <div class="comment-info" data-id="{$oCommentId}">
            <a href="/profile/{$oCommentAuthorLogin}" data-user_id="{$oCommentAuthorId}">
                <img src="{$oCommentAuthor->getProfileAvatarPath(24)}" class="comment-avatar"/>
            </a>
            <a class="comment-author {if $iAuthorId == $oCommentAuthorId}comment-topic-author {if $sAuthorNotice}" title="{$sAuthorNotice}{/if}{/if}" href="/profile/{$oCommentAuthorLogin}">{$oCommentAuthorLogin}</a>
            <time class="comment-date" datetime="{date_format date=$oCommentDate format='c'}" title="{$oCommentDate}">
                {date_format date=$oCommentDate format="j F Y, H:i"}
            </time>
            <a class="comment-link" href="#comment{$oCommentId}" title="{$aLang.comment_url_notice}"></a>
            {if $oComment->getPid()}
                <a href="#comment{$oComment->getPid()}" class="goto goto-comment-parent" title="{$aLang.comment_goto_parent}">↑</a>
            {/if}
            {if $oUserCurrent}
                {if !$bNoCommentFavourites}
                <div class="comment-favourite">
                    {if $oComment->getCountFavourite() > 0}
                        <div class="favourite link-dotted{if $oComment->getIsFavourite()} active{/if}" data-target_id="{$oCommentId}" data-target_type="comment">
                            {if $oComment->getIsFavourite()}
                                {$aLang.comment_favourite_add_already}
                            {else}
                                {$aLang.comment_favourite_add}
                            {/if}
                        </div>
                        <span class="favourite-count" id="fav_count_comment_{$oCommentId}">{$oComment->getCountFavourite()}</span>
                    {else}
                        <div class="favourite link-dotted" data-target_id="{$oCommentId}" data-target_type="comment">{$aLang.comment_favourite_add}</div>
                        <span class="favourite-count" id="fav_count_comment_{$oCommentId}"></span>
                    {/if}
                </div>
                <div id="vote_area_comment_{$oCommentId}" class="vote comment-vote
                    {if $oCommentVoteCount > 0}
                        {if $oCommentRating > 0} vote-count-positive
                        {elseif $oCommentRating < 0} vote-count-negative
                        {else} vote-count-mixed
                        {/if}
                    {/if}
                    {if $oCommentVote} voted
                        {if $oCommentVote->getDirection() > 0} voted-up
                        {else} voted-down
                        {/if}
                    {elseif !isset($oCommentSelf)}
                        {if (strtotime($oCommentDate)>=time()-$oConfig->GetValue('acl.vote.comment.limit_time'))} vote-enabled{/if}
                    {/if}
                    ">
                    <div class="vote-item vote-up" data-direction="1" data-target_id="{$oCommentId}" data-target_type="comment"></div>
                    <span class="vote-count" id="vote_total_comment_{$oCommentId}" data-target_id="{$oCommentId}" data-target_type="comment" data-count="{$oCommentVoteCount}">{if $oCommentRating > 0}+{/if}{$oCommentRating}</span>
                    <div class="vote-item vote-down" data-direction="-1" data-target_id="{$oCommentId}" data-target_type="comment"></div>
                </div>
                {/if}
                <a class="reply-link link-dotted">{$aLang.comment_answer}</a>
                <a class="link-dotted comment-edit-bw {if (strtotime($oCommentDate)<=time()-$oConfig->GetValue('acl.edit.comment.limit_time'))} edit-timeout{/if}">{$aLang.comment_edit}</a>
                {hook run='comment_action' comment=$oComment}
                {include file='comment_modify_notice.tpl'}
            {/if}
        </div>
{/if}
    </section>
    <div class="folding"></div>
{/strip}