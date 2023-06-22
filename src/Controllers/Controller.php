<?php

namespace Nebula\Controllers;

use GalaxyPDO\DB;
use Nebula\Container\Container;
use Symfony\Component\HttpFoundation\Request;
use Twig;

class Controller
{
    protected DB $db;
    protected Container $container;
    protected ?array $request;

    public function __construct()
    {
    }

    public function container(): Container
    {
        return Container::getInstance();
    }

    public function db(): DB
    {
        return $this->container()->get(DB::class);
    }

    protected function filterRequest(?Request $request): ?array
    {
        $filtered_request = null;
        if ($request) {
            $filtered_request = [
                ...$request->request,
                ...$request->query,
                ...$request->files,
            ];
        }
        return $filtered_request;
    }

    /**
     * Render a twig template
     * @param array<int,mixed> $data
     */
    protected function render(string $path, array $data = []): string
    {
        $twig = $this->container()->get(Twig\Environment::class);
        return $twig->render($path, $data);
    }
}
