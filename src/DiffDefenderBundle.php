<?php

namespace Busanstu\DiffDefenderBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class DiffDefenderBundle extends Bundle
{
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
