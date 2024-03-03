<?php

namespace SlashId\Php\Abstraction;

use GuzzleHttp\Client;
use SlashId\Php\SlashIdSdk;

class AbstractionBase
{
    public function __construct(
        protected SlashIdSdk $sdk,
        protected Client $client,
    ) {}
}
