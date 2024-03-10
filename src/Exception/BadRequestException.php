<?php

namespace SlashId\Php\Exception;

/**
 * Exception thrown when API returns a 400 HTTP error code.
 *
 * This will happen due to errors in the request, such as malformed ID or invalid data in the body of the request. To
 * see the error message from the webservice, call ->getMessage().
 */
class BadRequestException extends ApiExceptionBase
{}
