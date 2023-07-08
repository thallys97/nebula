<?php
/**
 * A collection of helper utility funcitons
 */

// DO NOT NAMESPACE THIS FILE

use Nebula\Email\EmailerSMTP;
use Nebula\Kernel\Web;
use Nebula\Validation\Validate;
use Nebula\Util\TwigExtension;

/**
 * Debug output
 */
function dump($o)
{
    $template = <<<PRE
<pre class='pre-debug'>
    <strong>DEBUG</strong><br>
    <small>File: %s</small><br>
    <small>Function: %s</small><br>
    <div style='padding-left: 32px;'>%s</div>
</pre>
PRE;
    $debug = debug_backtrace()[1];
    $line = $debug["line"];
    $file = $debug["file"];
    $function = $debug["function"];
    $dump = print_r($o ?? "null", true);
    printf($template, $file . ":" . $line, $function, $dump);
}

/**
 * Debug output and die
 */
function dd($o)
{
    dump($o);
    die();
}

/**
 * Return a valid UUID
 */
function uuid($data = null)
{
    $data = $data ?? random_bytes(16);
    assert(strlen($data) == 16);

    // Set version to 0100
    $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
    // Set bits 6-7 to 10
    $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);
    // Output the 36 character UUID.
    return vsprintf("%s%s-%s-%s-%s-%s%s%s", str_split(bin2hex($data), 4));
}

/**
 * Web App
 */
function app()
{
    return Web::getInstance();
}

function config(string $target)
{
    $config_dir = __DIR__ . "/../Config/";
    return match($target) {
        "app" => require($config_dir.'Application.php'),
        "auth" => require($config_dir.'Authentication.php'),
        "container" => require($config_dir.'Container.php'),
        "database" => require($config_dir.'Database.php'),
        "email" => require($config_dir.'Email.php'),
        "paths" => require($config_dir.'Paths.php'),
        default => throw new Error("config error: target not found = $target")
    };
}

/**
 * App request
 */
function request()
{
    return app()->getRequest();
}

function db()
{
    return app()->getDatabase();
}

/**
 * App route
 */
function route()
{
    return app()->getRoute();
}

/**
 * App session
 */
function session()
{
    return app()->getSession();
}

/**
 * App user
 */
function user()
{
    return app()->getUser();
}

/**
 * Return a twig template for output
 */
function twig($path, $data = [])
{
    $twig = app()
        ->container()
        ->get(Twig\Environment::class);
    $twig->addExtension(new TwigExtension());

    // App user
    $user = user();
    $data["user"] = $user;
    if ($user) {
        $user->gravatar =
            "http://www.gravatar.com/avatar/" . md5($user->email) . "?s=32";
    }

    // Form validation errors are passed directly to twig
    //$data["form_errors"] = Validate::$errors;
    $data["js_form_errors"] = json_encode(Validate::$errors);

    // Application-specific vars
    $data["app"] = config("app");

    return $twig->render($path, $data);
}

/**
 * App mailer
 */
function mailer(): EmailerSMTP
{
    return app()
        ->getContainer()
        ->get(EmailerSMTP::class);
}

/**
 * Validate a request array
 */
function validate(array $request_data, array $rules)
{
    return Validate::request($request_data, $rules);
}
