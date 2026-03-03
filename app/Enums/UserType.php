<?php

namespace App\Enums;

enum UserType: string
{
    case Cliente = 'cliente';
    case Admin = 'admin';
}