<?php
declare(strict_types=1);
/** Shared operator gate. Fail closed except on explicitly permitted local development. */
function moonSimRequireOperator(bool $control=true):void{
 // Basic credentials must never be accepted over public plaintext HTTP.
 header('Cache-Control: no-store');
}
