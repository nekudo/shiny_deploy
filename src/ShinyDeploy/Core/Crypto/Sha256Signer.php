<?php

namespace ShinyDeploy\Core\Crypto;

use Lcobucci\JWT\Signer\Hmac;

class Sha256Signer extends Hmac
{
    public function algorithmId(): string
    {
        return 'HS256';
    }

    public function algorithm(): string
    {
        return 'sha256';
    }

    public function minimumBitsLengthForKey(): int
    {
        return 64;
    }
}
