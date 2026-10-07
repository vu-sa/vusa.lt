<?php

namespace App\Exceptions;

/**
 * Graph answered 410 for a stored deltaLink: the change feed must restart from a full listing.
 */
class SharepointDeltaExpiredException extends \RuntimeException {}
