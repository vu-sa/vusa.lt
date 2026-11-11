<?php

namespace App\Enums;

/** Plain comments, titled topics and embedded polls. */
enum CommentKind: string
{
    case Comment = 'comment';
    case Poll = 'poll';
    case Topic = 'topic';
}
