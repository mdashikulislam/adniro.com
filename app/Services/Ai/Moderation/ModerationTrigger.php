<?php

namespace App\Services\Ai\Moderation;

/*
 * Why a moderation check was scheduled (adniro:ai). Stored in post_moderations.trigger.
 */

final class ModerationTrigger
{
	public const CREATED = 'created';
	public const EDITED = 'edited';
	public const PICTURES = 'pictures';
	public const FIELDS = 'fields';
	public const VERIFIED = 'verified';
	public const CHANGED_DURING_CHECK = 'changed';
	public const SWEEP = 'sweep';
	public const RERUN = 'rerun';
	public const CLEANUP = 'cleanup';
}
