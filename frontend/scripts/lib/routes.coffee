module.exports =
  stream:
    comment: '/ajax/stream/comment/'
    topic: '/ajax/stream/topic/'
    subscribe: '/stream/subscribe/'
    unsubscribe: '/stream/unsubscribe/'
    switchEventType: '/stream/switchEventType/'
    subscribeByLogin: '/stream/subscribeByLogin/'
    getMore: '/stream/get_more/'
    getMoreAll: '/stream/get_more_all/'
    getMoreUser: '/stream/get_more_user/'
  feed:
    subscribe: '/feed/subscribe/'
    unsubscribe: '/feed/unsubscribe/'
    subscribeByLogin: '/feed/subscribeByLogin/'
    getMore: '/feed/get_more/'
  blogs:
    top: '/ajax/blogs/top'
    joined: '/ajax/blogs/join'
    self: '/ajax/blogs/self'
    search: '/blogs/ajax-search/'
  blog:
    add: '/blog/add/'
    join: '/blog/ajaxblogjoin/'
    invite: '/blog/ajaxaddbloginvite/'
    reinvite: '/blog/ajaxrebloginvite/'
    remove: '/blog/ajaxremovebloginvite/'
    info: '/blog/ajaxbloginfo/'
  topic:
    add: '/topic/add/'
    comment: '/blog/ajaxaddcomment/'
    respond: '/blog/ajaxresponsecomment/'
  comment:
    delete: '/ajax/comment/delete/'
  talk:
    add: '/talk/add/'
    comment: '/talk/ajaxaddcomment/'
    respond: '/talk/ajaxresponsecomment/'
    addUser: '/talk/ajaxaddtalkuser/'
    removeUser: '/talk/ajaxdeletetalkuser/'
    unBlacklistUser: '/talk/ajaxdeletefromblacklist/'
  preview:
    text: '/ajax/preview/text/'
    topic: '/ajax/preview/topic/'
  vote:
    comment: '/ajax/vote/comment/'
    topic: '/ajax/vote/topic/'
    blog: '/ajax/vote/blog/'
    user: '/ajax/vote/user/'
    question: '/ajax/vote/question/'
  favourite:
    topic: '/ajax/favourite/topic/'
    talk: '/ajax/favourite/talk/'
    comment: '/ajax/favourite/comment/'
    saveTags: '/ajax/favourite/save-tags/'
  geo:
    regions: '/ajax/geo/get/regions/'
    cities: '/ajax/geo/get/cities/'
  subscribe:
    toggle: '/subscribe/ajax-subscribe-toggle/'
  profile:
    addNote: '/profile/ajax-note-save/'
    removeNote: '/profile/ajax-note-remove/'
    acceptFriendship: '/profile/ajaxfriendaccept/'
    addFriend: '/profile/ajaxfriendadd/'
    removeFriend: '/profile/ajaxfrienddelete/'
    login: '/login/ajax-login/'
    registration: '/registration/ajax-registration/'
    validateFields: '/registration/ajax-validate-fields/'
    reminder: '/login/ajax-reminder/'
    reactivate: '/login/ajax-reactivation/'
  image:
    uploadAvatar: '/settings/profile/upload-avatar/'
    resizeAvatar: '/settings/profile/resize-avatar/'
    removeAvatar: '/settings/profile/remove-avatar/'
    cancelAvatar: '/settings/profile/cancel-avatar/'
    uploadFoto: '/settings/profile/upload-foto/'
    resizeFoto: '/settings/profile/resize-foto/'
    removeFoto: '/settings/profile/remove-foto/'
    cancelFoto: '/settings/profile/cancel-foto/'
    uploadImage: '/upload/image/'
  tag:
    autocomplete: '/ajax/autocompleter/tag/'
    main: '/tag/'
  people:
    autocomplete: '/ajax/autocompleter/user/'
    search: '/people/ajax-search/'
  userfields: '/admin/userfields/'