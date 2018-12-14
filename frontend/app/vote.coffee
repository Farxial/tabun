$ = require "jquery"
classNames = require "classnames"
{capitalize, has, isFunction} = require "lodash"

{ajax} = require "core/ajax.coffee"
lang = require "core/lang.coffee"
{error, notice} = require "core/messages.coffee"
routes = require("lib/routes").default

prefix =
  area: 'vote_area_'
  total: 'vote_total_'
  count: 'vote_count_'

voteTargets =
  comment: 'idComment'
  topic: 'idTopic'
  blog: 'idBlog'
  user: 'idUser'

vote = (idTarget, objVote, value, type) ->
  unless voteTargets[type]
    return false
  unless objVote.parentNode.classList.contains("vote-enabled")
    return false

  objVote = $(objVote)
  params = {}
  params['value'] = value
  params[voteTargets[type]] = idTarget

  ajax routes.vote[type], params, (result) ->
    onVote idTarget, objVote, value, type, result


onVote = (idTarget, objVote, value, type, result) ->
  if result.bStateError
    error null, result.sMsg
    return
  notice null, result.sMsg

  iRating = parseFloat result.iRating
  iCountVote = parseFloat result.iCountVote

  divVoting = $ "##{prefix.area}#{type}_#{idTarget}"
  divTotal = $ "##{prefix.total}#{type}_#{idTarget}"
  divCount = $ "##{prefix.count}#{type}_#{idTarget}"

  divVoting.removeClass classNames "vote-count-positive", "vote-count-negative", "vote-count-zero", "vote-count-mixed", "not-voted", "vote-enabled"
  divVoting.addClass classNames "voted",
    "voted-up": value > 0
    "voted-down": value < 0
    "voted-zero": value == 0
    "vote-count-positive": iRating > 0
    "vote-count-negative": iRating < 0
    "vote-count-zero": iRating == 0 and (iCountVote == 0 or type == "topic")
    "vote-count-mixed": iRating == 0 and (iCountVote > 0 and type != "topic")

  if divCount.length > 0 and result.iCountVote then divCount.text parseInt(result.iCountVote)
  divTotal.text if iRating > 0 then "+#{iRating}" else if iRating < 0 then result.iRating else 0
  divTotal[0].dataset.count = iCountVote

  method = "onVote#{capitalize type}"
  if has(@, method) && isFunction @[method]
    @[method] idTarget, objVote, value, type, result

onVoteUser = (idTarget, objVote, value, type, result) ->
  $("#user_skill_#{idTarget}").text result.iSkill

getVotes = (targetId, targetType, el) ->
  if el.dataset.target_type == "topic" && el.parentNode.classList.contains("not-voted") && UI.voteNeutral
    return false

  params = {}
  params['targetId'] = targetId
  params['targetType'] = targetType

  ajax routes.vote.getVotes, params, onGetVotes.bind({"control":el,"targetType":targetType})
  el.dataset.queryState = "query"
  return false

__makeProfileLink = (path, data) ->
  el = document.createElement "a"
  if path != null and data.name != null
    el.href = "/profile/"+path
    el.target = "_blank"
    el.className = "ls-user has-avatar"
    avatar = document.createElement "img"
    avatar.src = data.avatar
    el.appendChild avatar
    el.appendChild document.createTextNode(data.name)
  else
    el.href = "javascript://"
    el.className = "ls-user undefined"
    el.appendChild document.createTextNode("—")
  return el

onGetVotes = (result) ->
  if result.bStateError
    error null, result.sMsg
  else
    if result.iVoteCount > 0
      vl_box = document.createElement "div"
      vl_box.className = "vote-list-box hidden for-"+this.targetType
      vl_box.innerHTML = result.sHtmlVoteList
      vl_wrapper = vl_box.querySelector ".vote-list-wrapper"
      this.control.parentNode.parentNode.parentNode.insertBefore vl_box, this.control.parentNode.parentNode.nextSibling
      setTimeout DOMTokenList.prototype.remove.bind(vl_box.classList), 10, "hidden"
      if vl_wrapper.scrollHeight > vl_wrapper.clientHeight
        vl_wrapper.style.width = (vl_wrapper.clientWidth + 24) + "px"
        vl_wrapper.style.overflowY = "scroll"
      
      context = {
        "target":vl_box,
        "eventTarget":window
      }
      context.callback = onVotesListLeaved.bind context
      context.eventTarget.addEventListener "click", context.callback
    else
      notice null, lang.gettext("no_votes_"+this.targetType)
    
    if parseInt(this.control.dataset.count) != result.iVoteCount
      this.control.parentNode.classList.remove "vote-count-negative"
      this.control.parentNode.classList.remove "vote-count-positive"
      this.control.parentNode.classList.remove "vote-count-mixed"
      if result.fVoteSum > 0
        this.control.textContent = "+" + result.fVoteSum.toString()
        this.control.parentNode.classList.add "vote-count-positive"
      else
        this.control.textContent = result.fVoteSum.toString()
        if result.fVoteSum < 0
          this.control.parentNode.classList.add "vote-count-negative"
        else
          this.control.parentNode.classList.add "vote-count-mixed"
      
      this.control.dataset.count = result.aVotes.length.toString()
  delete this.control.dataset.queryState

onVotesListLeaved = (e) ->
  if e.target.classList.contains("close-button") or (this.target != e.target and e.target.tagName != "A" and !this.target.contains(e.target))
    this.target.classList.add "hidden"
    setTimeout Node.prototype.removeChild.bind(this.target.parentNode), 500, this.target
    this.eventTarget.removeEventListener e.type, this.callback


module.exports = {vote, getVotes}