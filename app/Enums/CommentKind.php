<?php

namespace App\Enums;

/**
 * Discriminates between a plain discussion comment and future structured kinds
 * (e.g. an embedded Loomio-style poll). The `poll` case is a forward-looking
 * seam — it is not yet accepted by the v1 API.
 */
enum CommentKind: string
{
    case Comment = 'comment';
    case Poll = 'poll';
}
