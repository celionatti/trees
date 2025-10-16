<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;



class User extends Model
{
    protected $fillable = ['name', 'email', 'password'];
    protected $hidden = ['password', 'remember_token'];
    
    // Example usage:
    // $user = User::find(1);
    // $user->name = 'New Name';
    // $user->save();
    //
    // $users = User::where('status', '=', 'active')->get();
    // $user = User::create(['name' => 'John', 'email' => 'john@example.com']);
}