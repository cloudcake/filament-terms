<?php

namespace Cloudcake\Terms\Contracts;

interface RenamableColumns
{
    /**
     * Retrieve columns that are renamable.
     * 
     * @return array
     */
    public static function renamable(): array;
}