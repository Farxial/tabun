<?php

return [
    'comment' => [
        'expose_from_date' => 1545339600,   // strtotime('2018-12-21 +03:00')
        'user_required_level' => 6,
        'superuser_required_level' => 1,
        'date_sort_mode' => SORT_ASC,
    ],
    'topic' => [
        'expose_from_date' => 1545339600,   // strtotime('2018-12-21 +03:00')
        'user_required_level' => 1,
        'superuser_required_level' => 1,
        'date_sort_mode' => SORT_ASC,
    ],
    'blog' => [
        'expose_from_date' => 1545339600,   // strtotime('2018-12-21 +03:00')
        'user_required_level' => 1,
        'superuser_required_level' => 1,
        'date_sort_mode' => SORT_DESC,
    ],
    'user' => [
        'expose_from_date' => 1545339600,   // strtotime('2018-12-21 +03:00')
        'user_required_level' => 1,
        'superuser_required_level' => 1,
        'date_sort_mode' => SORT_DESC,
    ],
];
// expose_from_date            — Дата, начиная с которой голоса открываются для user_required_level, unix timestamp
// user_required_level         — Требуемый уровень* пользователей для просмотра открытых голосов, а также просто запроса списка
// superuser_required_level    — Требуемый уровень* для просмотра скрытых голосов
// date_sort_mode              — Режим сортировки голосов по дате, ASC/DESC
// * Уровень пользователя должен быть МЕНЬШЕ или равен требуемому.
// Уровни пользователей:
//     0 — никто
//     1 — администраторы сайта
// Значения от 2 до 7 включительно имеют разный эффект для комментариев, топиков и блогов, но не для пользователей.
//     2 — (не используется)
//     3 — администраторы сайта, администраторы блогов
//     4 — администраторы сайта, администраторы блогов, модераторы блогов
//     5 — администраторы сайта, администраторы блогов, модераторы блогов, автор объекта (если он может видеть объект)
//     6 — все пользователи, которые имеют доступ к объекту на чтение
//     7 — все пользователи
//     8 — все
// См. также ModuleACL::CheckSimpleAccessLevel
