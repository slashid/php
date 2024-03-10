<?php

namespace SlashId\Php\Exception;

/**
 * Exception thrown when API returns a 409 HTTP error code.
 *
 * This will happen when you try to create an object in the webservice and there is already an object with a unique
 * value. Try checking if the object already exists before creating a request or use a PUT when available. To see the
 * error message from the webservice, call ->getMessage().
 */
class ConflictException extends ApiExceptionBase
{}
