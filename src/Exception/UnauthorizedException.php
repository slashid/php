<?php

namespace SlashId\Php\Exception;

/**
 * Exception thrown when API returns a 401 HTTP error code, usually due to wrong or missing Organization ID or API Key.
 */
class UnauthorizedException extends ApiExceptionBase {}
