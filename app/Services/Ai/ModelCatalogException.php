<?php

namespace App\Services\Ai;

use RuntimeException;

/*
 * A provider's model list could not be loaded (adniro:ai). The message is safe to show to
 * super admins: keys are redacted.
 */

final class ModelCatalogException extends RuntimeException
{
}
