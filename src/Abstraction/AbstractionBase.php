<?php

namespace SlashId\Php\Abstraction;

use SlashId\Php\SlashIdSdk;

class AbstractionBase {

    public function __construct(
        protected SlashIdSdk $sdk
    )
    {}

}
