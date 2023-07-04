<?php

namespace Nebula\Config;

use Nebula\Kernel\Env;

class Application
{
    private array $config;
    public function __construct()
    {
        $env = Env::getInstance()->env();
        $this->config = [
            "name" => $env["APP_NAME"],
            "url" => $env["APP_URL"],
            "debug" => strtolower($env["APP_DEBUG"]) === "true",
        ];
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}