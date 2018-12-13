$ = require "jquery"
{scrollTo} = require "jquery"
{keys, map, filter, first, forEach} = require "lodash"
{gettext, ngettext} = require "core/lang.coffee"
{ajax} = require "core/ajax.coffee"
{error, notice} = require "core/messages.coffee"
{textPreview, registry, prepareJSON, spoilerHandler, contentMediaParser, contentRemoveBadChars} = require "core/tools.coffee"
blocks = require "lib/blocks.coffee"
routes = require("lib/routes").default
{commentFor} = require "lib/markitup.coffee"


types =
  topic:
    url_add: routes.topic.comment
    url_response: routes.topic.respond
  talk:
    url_add: routes.talk.comment
    url_response: routes.talk.respond

classes =
  form_loader: 'loader'
  new: 'comment-new'
  current: 'comment-current'
  deleted: 'comment-deleted'
  wrapper: 'comment-wrapper'
  self: 'comment-self'
  folded: 'folded'
  comment: 'comment'
  comment_goto_parent: 'goto-comment-parent'
  comment_hidden: 'comment-hidden'
  comment_folded: 'comment-folded'
  level: 'comment-level-'

hideClasses = [classes.self, classes.new, classes.deleted, classes.current]

iCurrentShowFormComment = 0
currentViewedComment = null

allComments = document.getElementsByClassName classes.comment
newComments = document.getElementsByClassName classes["new"]
foldedComments = document.getElementsByClassName classes.comment_folded
newCounter = document.getElementById "new_comments_counter"
allCounter = document.getElementById "count-comments"
commentForm = document.getElementById "form_comment_text"
updateButton = document.getElementById "update-comments"
message = document.getElementById "hidden-message"
originalTitle = document.title
topicAuthor = document.querySelector ".topic-info a[rel=author]"

toggleCommentFormState = (state) ->
  submitButton = document.getElementById "comment-button-submit"

  if state
    commentForm.classList.remove classes.form_loader
    commentForm.readOnly = false
    submitButton.disabled = false
  else
    commentForm.classList.add classes.form_loader
    commentForm.readOnly = true
    submitButton.disabled = true
  null

add = (formId, targetId, targetType) ->
  toggleCommentFormState false

  _success = (result) ->
    unless result
      return error gettext("server_error"), gettext("try_later")
    if result.bStateError
      return error gettext("common_error"), result.sMsg
    else
      commentForm.value = ""
      load targetId, targetType, false

  _complete = ({responseJSON:{bStateError}}) ->
    toggleCommentFormState true
    unless bStateError
      toggleCommentForm iCurrentShowFormComment, true

  ajax types[targetType].url_add, prepareJSON(document.getElementById(formId)), _success, _complete

toggleCommentForm = (idComment, bNoFocus) ->
  reply = document.getElementById 'reply'
  unless reply then return

  preview = document.getElementById "comment_preview_#{iCurrentShowFormComment}"
  preview?.parentNode?.removeChild preview

  if iCurrentShowFormComment == idComment and 'h-hidden' not in reply.classList
    reply.classList.add 'h-hidden'
    return

  commentNode = document.getElementById "comment_id_#{idComment}"
  commentNode.parentNode.insertBefore reply, commentNode.nextSibling
  reply.classList.remove 'h-hidden'

  commentForm.value = ""
  document.getElementById("form_comment_reply").value = idComment
  iCurrentShowFormComment = idComment
  unless bNoFocus
    commentForm.focus()


toggleEditForm = (idComment, bOpen) ->
  contentWrapper = document.getElementById "comment_content_id_#{idComment}"
  if !document.querySelector "#comment_id_#{idComment} .buttons-ready"
    document.querySelector("#comment_id_#{idComment} .comment-edit-bw").outerHTML =
      '<a class="link-dotted comment-delete">Удалить</a>
      <a class="link-dotted comment-edit-bw buttons-ready">Изменить</a>
      <a class="link-dotted comment-save-edit-bw">Сохранить</a>
      <a class="link-dotted comment-preview-edit-bw">Предпросмотр</a>
      <a class="link-dotted comment-cancel-edit-bw">Отмена</a>'
  if bOpen
    preview = document.createElement "div"
    preview.className = "text preview"
    preview.id = "comment_preview_edit_#{idComment}"
    currentText = contentWrapper.querySelector ".text.current"
    editForm = document.createElement "div"
    editForm.id = "comment_edit_#{idComment}"
    editForm.className = "edit-form"
    edit = document.createElement "textarea"
    edit.className = "markitup-editor"
    edit.id = "comment_edit_input_#{idComment}"
    edit.style.height = (currentText.getBoundingClientRect().height * 1.2 + 40) + "px"
    edit.value = currentText.innerHTML.replace(/<br[\s]*\/?>\r?\n/gmi, "\n").trim()
    editForm.appendChild preview
    editForm.appendChild edit
    bCommentSelf = contentWrapper.parentNode?.classList.contains "comment-self"
    if Capabilities.allowCommentsEditingLock and !bCommentSelf and document.querySelector("#comment_id_#{idComment} .modify-notice>*")?.dataset.locked != "1"
      lockCB = document.createElement "input"
      lockCB.type = "checkbox"
      lockLabel = document.createElement "label"
      lockLabel.classList.add "lock-edit"
      lockLabel.appendChild lockCB
      lockLabel.appendChild document.createTextNode " " + gettext "comment_lock_edit"
      editForm.appendChild lockLabel
    contentWrapper.parentNode?.classList.add "editable"
    contentWrapper.appendChild editForm
    commentFor edit
    edit.focus()
  else
    closeEditForm idComment, contentWrapper
  false

closeEditForm = (idComment, contentWrapper) ->
  $("#comment_edit_#{idComment}").remove()
  contentWrapper.parentNode?.classList.remove "editable"

load = (idTarget, typeTarget, bFlushNew=true) ->
  idCommentLast = parseInt(newCounter.dataset.idCommentLast) || 0
  updateButton.classList.add 'active'
  params =
    idCommentLast: idCommentLast
    idTarget: idTarget
    typeTarget: typeTarget

  _success = (result) ->
    if result.bStateError
      return error gettext("common_error"), result.sMsg

    if bFlushNew
      while newComments.length
        newComments[0].classList.remove classes["new"]

    forEach result.comments, (comment, id) ->
      unless document.getElementById "comment_id_"+id
        inject comment

    setCountNewComment()
    setCountAllComment()

    if keys(result.comments) > 0
      curItemBlock = blocks.getCurrentItem 'stream'
      if curItemBlock?.dataset.type == 'comment'
        blocks.load curItemBlock, 'stream'
      newCounter.dataset.idCommentLast = result.iMaxIdComment

  _complete = -> updateButton.classList.remove 'active'

  ajax types[typeTarget].url_response, params, _success, _complete


inject = ({pid, id, html}) ->
  newComment = document.createElement 'div'
  newComment.classList.add classes.wrapper
  newComment.id = "comment_wrapper_id_#{id}"
  newComment.innerHTML = html
  level = 0

  if pid
    target = document.getElementById "comment_wrapper_id_#{pid}"
    forEach target.classList, (className) ->
      if className.match classes.level
        level = parseInt(className.replace(classes.level,"")) + 1
  else
    target = document.getElementById "comments"

  newComment.classList.add (classes.level + level)
  target.appendChild newComment
  commentAuthor = newComment.querySelector ".comment-author"
  if commentAuthor.textContent == topicAuthor.textContent
    commentAuthor.classList.add "comment-topic-author"
    commentAuthor.setAttribute "title", "Автор"
  if newComment.getElementsByClassName(classes.self).length
    showComment id

toggle = (obj, commentId) ->
  url = routes.comment.delete
  params = idComment: commentId

  _success = (result) ->
    unless result
      return error gettext("server_error"), gettext("try_later")
    if result.bStateError
      return error gettext("common_error"), result.sMsg

    notice null, result.sMsg
    comment = document.getElementById "comment_id_#{commentId}"
    forEach hideClasses, (className) -> comment.classList.remove className
    if result.bState
      comment.classList.add classes.deleted
    obj.text result.sTextToggle

  ajax url, params, _success


saveEdit = (idComment) ->
  url = routes.comment.edit
  editForm = document.getElementById "comment_edit_#{idComment}"
  params =
    idComment: idComment
    newText: editForm?.querySelector("textarea")?.value
    setLock: if editForm?.querySelector('label>input[type="checkbox"]')?.checked then "1" else "0"

  _success = (result) ->
    unless result
      return error gettext("server_error"), gettext("try_later")
    if result.newText
      document.querySelector("#comment_content_id_#{idComment} .text.current").innerHTML = result.newText
    if result.notice
      if document.querySelector("#comment_id_#{idComment} .modify-notice")
        document.querySelector("#comment_id_#{idComment} .modify-notice").innerHTML = result.notice
      else
        document.querySelector("#comment_id_#{idComment} .comment-info").innerHTML += '<span class="modify-notice">'+result.notice+'</span>'
    if result.bStateError
      return error result.sMsgTitle, result.sMsg
    else
      toggleEditForm idComment, false
      return notice result.sMsgTitle, result.sMsg

  ajax url, params, _success
  false

previewEdit = (idComment) ->
  preview_id = "comment_preview_edit_#{idComment}"
  document.getElementById(preview_id).innerHTML = ""
  textPreview "comment_edit_input_#{idComment}", false, preview_id, true
  return false

preview = ->
  unless commentForm.value
    return

  old_preview = document.getElementById "comment_preview_#{iCurrentShowFormComment}"
  old_preview?.parentNode?.removeChild old_preview

  new_preview = document.createElement "div"
  new_preview.className = "comment-preview text"
  new_preview.id = "comment_preview_#{iCurrentShowFormComment}"

  reply = document.getElementById("reply")
  reply.parentNode.insertBefore new_preview, reply

  textPreview 'form_comment_text', false, "comment_preview_#{iCurrentShowFormComment}"


setCountNewComment = () ->
  unless newCounter then return
  if newComments.length
    if UI.newCommentsInTitle
      document.title = newComments.length + ' | ' + originalTitle
    newCounter.textContent = newComments.length
    newCounter.classList.remove "h-hidden"
  else
    if UI.newCommentsInTitle
      document.title = originalTitle
    newCounter.classList.add "h-hidden"


setCountAllComment = () ->
  document.getElementById("name-count-comments").textContent = ngettext "comment", "comments", allComments.length
  allCounter.textContent = allComments.length

showComment = (commentId, highlightParent) ->
  "use strict"
  if commentId then comment = document.getElementById "comment_id_" + commentId
  unless comment then comment = newComments[0]
  unless comment then return
  commentWrapper = comment.parentNode
  parentWrapper = commentWrapper.parentNode
  parentComment = parentWrapper.children[0]
  if UI.autoDespoil && !highlightParent
    spoilerHandler $(comment).find(".spoiler"), 'despoil'
  if UI.autoFold || highlightParent
    toFold = null
    if parentWrapper.id != "comments" then toFold = $(commentWrapper).prevAll(".comment-wrapper")
    hiddenCount = $(toFold).find(".comment").length
    if highlightParent
      oldPos = comment.getClientRects()[0].top
      while foldedComments.length
        foldedComments[0].classList.remove classes.comment_folded
    else
      while foldedComments.length
        foldedComments[0].classList.remove classes.comment_folded
      oldPos = comment.getClientRects()[0].top
    forEach toFold, (wrapper) ->
      wrapper.classList.add classes.comment_folded
    if hiddenCount
      parentWrapper.insertBefore message, toFold[0]
      message.classList.remove "h-hidden"
    else
      message.classList.add "h-hidden"
    window.scrollBy 0, comment.getClientRects()[0].top - oldPos
    if hiddenCount
      message.children[0].textContent = hiddenCount
      message.children[1].textContent = ngettext "comment", "comments", hiddenCount
  if currentViewedComment != null
    currentViewedComment.classList.remove classes.current
  if highlightParent
    parentComment.classList.add classes.current
    currentViewedComment = parentComment
  else
    shift = (window.innerHeight - comment.getClientRects()[0].height) / 2
    if shift < 0 then shift = 0
    if UI.smothScroll
      $.scrollTo comment, 300, {offset: -shift}
    else
      window.scrollBy 0, comment.getClientRects()[0].top - shift
    comment.classList.remove classes["new"]
    comment.classList.add classes.current
    currentViewedComment = comment
  setCountNewComment()

initEvent = ->
  if commentForm
    $(commentForm).on 'keyup', ({keyCode, which, ctrlKey}) ->
      key = keyCode or which
      if ctrlKey and key == 13
        $('#comment-button-submit').click()
    $("#comment_subscribe").on 'change', () ->
      ls.subscribe.toggle this.dataset.target_type+'_new_comment', this.dataset.target_id, '', this.checked
    $(".reply-header>a").on 'click', () ->
      ls.comments.toggleCommentForm 0
    $("#comment-button-submit").on 'click', () ->
      commentForm.value = contentRemoveBadChars contentMediaParser commentForm.value
      ls.comments.add 'form_comment', this.dataset.target_id, this.dataset.target_type
    $("#comment-button-preview").on 'click', () ->
      commentForm.value = contentRemoveBadChars contentMediaParser commentForm.value
      ls.comments.preview()
    $(document)
      .on('click', ".comments-allowed .reply-link", () ->
        ls.comments.toggleCommentForm this.parentNode.dataset.id)
      .on('click', ".is-admin .comment-delete,.is-admin .comment-repair", () ->
        ls.comments.toggle this, this.parentNode.dataset.id)
      .on('click', ".comment-self .comment-edit-bw, .is-admin .comment-edit-bw, .is-moder .comment-edit-bw", () ->
        ls.comments.toggleEditForm this.parentNode.dataset.id, true)
      .on('click', ".comment-save-edit-bw", () ->
        editForm = document.getElementById "comment_edit_input_" + this.parentNode.dataset.id
        editForm.value = contentRemoveBadChars contentMediaParser editForm.value
        ls.comments.saveEdit this.parentNode.dataset.id)
      .on('click', ".comment-preview-edit-bw", () ->
        editForm = document.getElementById "comment_edit_input_" + this.parentNode.dataset.id
        editForm.value = contentRemoveBadChars contentMediaParser editForm.value
        ls.comments.previewEdit this.parentNode.dataset.id)
      .on('click', ".comment-cancel-edit-bw", () ->
        ls.comments.toggleEditForm this.parentNode.dataset.id, false)

  $("#hidden-message>a").on 'click', () ->
    this.parentNode.classList.add 'h-hidden'
    $('.'+classes.comment_folded).removeClass classes.comment_folded
  $(document)
    .on('click', ".comment-level-1 .goto-comment-parent", (event) ->
      ls.comments.showComment this.parentNode.dataset.id, true
      event.preventDefault())
    .on('click', ".folding", () ->
      $(this).nextAll().toggleClass 'h-hidden')

  if updateButton
    $(updateButton).on 'click', () ->
      ls.comments.load this.dataset.target_id, this.dataset.target_type
    if UI.autoUpdateComments
      autoUpdate = ->
        if document.visibilityState != 'hidden' then return
        if !$("#reply").hasClass("h-hidden") then return
        if $(updateButton).hasClass("active") then return
        load updateButton.dataset.target_id, updateButton.dataset.target_type, false
      setInterval autoUpdate, 60000
    if newCounter
      $(newCounter).on 'click', () ->
        ls.comments.showComment()
      if UI.hotkeys
        $(document).keydown (e) ->
          key = e.keyCode or e.which
          if [32,45].indexOf(key) != -1
            if ["TEXTAREA","INPUT"].indexOf(document.activeElement.nodeName) == -1
              e.preventDefault()
              $(newCounter).click()
          else if [13,46].indexOf(key) != -1
            if ["TEXTAREA","INPUT"].indexOf(document.activeElement.nodeName) == -1
              e.preventDefault()
              $("#update-comments").click()

init = ->
  initEvent()
  setCountNewComment()
  if commentForm && UI.smartQuote
    $(document)
      .on('mouseup', (e) ->
        #проверяем нажата ли левая кнопка
        if e.which != 1 then return
        selection = window.getSelection()
        text = selection
          .toString()
          .replace /&/g, "&amp;"
          .replace /</g, "&lt;"
          .replace />/g, "&gt;"
          .replace /"/g, "&quot;"
          .replace /'/g, "&#039;"
        if !text then return
        #получаем всех родителей
        parents = $(selection.anchorNode.parentElement)
        parents = $(parents).add($(parents).parents())
        #проверка на допустимость цитирования
        if $(parents).filter("textarea").length then return
        if !$(parents).filter(".comment-content,.topic-content").length then return
        #ищем родительский комментарий
        parentID = $(parents).filter(".comment")[0].dataset.id || 0;
        contentRect = document.getElementById("content").getBoundingClientRect()
        x = e.clientX - contentRect.left + 10;
        y = e.clientY - contentRect.top - 9;
        quote = document.getElementById "quote"
        if text != quote.dataset.quote
          quote.dataset.quote = text
          quote.dataset.parent_id = parentID
          $(quote).css 'left', x+'px'
          $(quote).css 'top', y+'px'
          $(quote).show()
      )
      .on('mousedown', ->
        $("#quote").hide()
      )
      .on('mouseup', '#quote', (e) ->
        e.stopPropagation()
        #выбираем форму для вставки
        targetForm = $("textarea[id^='comment_edit']")[0]
        if !targetForm
          targetForm = commentForm
          if $("#reply").hasClass "h-hidden"
            iCurrentShowFormComment = this.dataset.parent_id
            toggleCommentForm iCurrentShowFormComment, false
          else
            iCurrentShowFormComment = $("#reply").siblings(".comment").dataset.id || 0
        else
          iCurrentShowFormComment = $(targetForm).id.replace "comment_edit_input_", ""
        #ищем каретку в форме редактирования
        caret = targetForm.selectionStart
        if isNaN caret
          targetForm.value += '<blockquote>'+this.dataset.quote+'</blockquote>'
        else
          targetForm.value = 
            targetForm.value.substring(0,caret) +
            '<blockquote>'+this.dataset.quote+'</blockquote>' +
            targetForm.value.substring(caret)
        $(this).hide()
        #если форма редактирования не видна, мотаем
        targetFormPosition = $(targetForm).offset().top
        windowPosition = $(window).scrollTop()
        if (targetFormPosition + targetForm.getClientRects()[0].height < windowPosition) || (targetFormPosition > (windowPosition + $(window).height()))
          if iCurrentShowFormComment
            scrollTo document.getElementById "comment_id_"+iCurrentShowFormComment, 300, {offset: -250}
          else
            scrollTo targetForm, 300, {offset: -250}
      )
      .on('mousedown','#quote', (e) ->
        if e.which != 1
          $(this).hide()
        e.stopPropagation()
      )


module.exports = {
  init
  toggleCommentForm
  toggle
  toggleEditForm
  previewEdit
  saveEdit
  add
  preview
  load
  showComment
}
