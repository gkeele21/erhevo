<?php

namespace App\Services\FamilyHistory;

use RuntimeException;

/** A GEDCOM file we can't use; the message is safe to show the user. */
class GedcomException extends RuntimeException
{
}
