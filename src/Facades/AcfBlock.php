<?php

namespace Genero\Sage\AcfBlocks\Facades;

use Illuminate\Support\Facades\Facade;

class AcfBlock extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'acfblock';
    }
}
