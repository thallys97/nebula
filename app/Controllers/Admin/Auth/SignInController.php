<?php

namespace App\Controllers\Admin\Auth;

use Nebula\Controller\Controller;
use StellarRouter\{Get, Post, Group};

#[Group(prefix: "/admin")]
class SignInController extends Controller
{
  #[Get("/sign-in", "sign-in.index")]
  public function index(): string
  {
    return twig("admin/auth/sign-in.html", []);
  }

  #[Post("/sign-in", "sign-in.post")]
  public function post(): string
  {
    if ($this->validate([
      "email" => ["required", "email"],
      "password" => ["required"],
    ])) {
    } else {
      dd($this->errors);
    }
    // Validation failed, show the sign in form
    return $this->index();
  }
}