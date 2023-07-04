<?php

namespace Nebula\Admin;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Nebula\Models\User;
use stdClass;

class Auth
{
    public static function rememberMe(User $user): bool
    {
        $duration = time() + 30 * 24 * 60 * 60;
        // Generate a random token for the user
        $token = bin2hex(random_bytes(16));

        // Store the token in a cookie that expires after $duration
        setcookie("remember_token", $token, $duration, "/");

        // Store the token in the user row
        $user->remember_me = md5($token);
        return $user->update();
    }

    public static function authenticate(stdClass $data): ?User
    {
        $user = User::findByAttribute("email", $data->email);
        return password_verify($data->password, $user?->password)
            ? $user
            : null;
    }

    public static function signOut(): void
    {
        self::destroyRememberCookie();
        session()->destroy();
        $route = app()->findRoute("auth.sign_in");
        $response = new RedirectResponse($route->getPath());
        $response->send();
        exit();
    }

    public static function destroyRememberCookie(): void
    {
        if (isset($_COOKIE["remember_token"])) {
            unset($_COOKIE["remember_token"]);
            setcookie("remember_token", "", -1, "/");
        } else {
        }
    }

    public static function register(stdClass $data): ?User
    {
        $user = new User();
        $user->name = $data->name;
        $user->email = $data->email;
        $user->password = self::hashPassword($data->password);
        $result = $user->insert();
        if ($result) {
            mailer()
                ->setSubject("New account created")
                ->setTo($user->email)
                ->setTemplate("admin/auth/email/new-registration.html", [
                    "name" => $user->name,
                    "email" => $user->email,
                ])
                ->send();
        }
        return $result;
    }

    public static function signIn(User $user): void
    {
        $route = app()->findRoute("admin.index");
        if (!isset($_COOKIE["remember_token"])) {
            session()->set("user", $user->id);
        }
        // Clear the password reset token / ts
        $user->reset_token = null;
        $user->reset_expires_at = null;
        $user->update();
        $response = new RedirectResponse($route->getPath());
        $response->send();
        exit();
    }

    public static function generateToken(): string
    {
        $length = 32;
        $characters =
            "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $token = "";

        for ($i = 0; $i < $length; $i++) {
            $token .= $characters[random_int(0, strlen($characters) - 1)];
        }

        return $token;
    }

    public static function forgotPassword(string $email): void
    {
        $user = User::findByAttribute("email", $email);
        if ($user) {
            $expires = strtotime("+ 15 minute");
            $token = self::generateToken();
            $user->reset_token = $token;
            $user->reset_expires_at = $expires;
            $user->update();
            $password_reset_route = app()->buildRoute("auth.password_reset", $user->uuid, $token);
            $url = app()->routePathURL($password_reset_route);
            mailer()
                ->setSubject("Password reset requested")
                ->setTo($user->email)
                ->setTemplate("admin/auth/email/forgot-password.html", [
                    "name" => $user->name,
                    "url" => $url
                ])
                ->send();
        }
    }

    public static function hashPassword(string $password): string|bool
    {
        return password_hash($password, PASSWORD_ARGON2I);
    }

    public static function validateForgotPassword(User $user, string $token): bool
    {
        // Valid for 15 minutes
        return $user?->reset_token === $token && $user?->reset_expires_at - time() > 0;
    }

    public static function changePassword(User $user, string $password): bool
    {
        $user->password = self::hashPassword($password);
        $result = $user->update();
        if ($result) {
            mailer()
                ->setSubject("Password changed successfully")
                ->setTo($user->email)
                ->setTemplate("admin/auth/email/password-change.html", [
                    "name" => $user->name,
                ])
                ->send();
        }
        return $result;
    }
}
