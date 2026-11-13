<?php

declare(strict_types=1);

namespace CnA\View\Engine;

use RuntimeException;

class ViewNotFoundException extends RuntimeException
{
    public function __construct(string $view, array $searchedPaths = [])
    {
        $pathsStr = !empty($searchedPaths) ? ' Searched locations: [' . implode(', ', $searchedPaths) . ']' : '';
        parent::__construct("View template [{$view}] could not be found.{$pathsStr}");
    }
}
