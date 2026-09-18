<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'phone', 'country_code', 'email', 'status'];

    public function user() { return $this->belongsTo(User::class); }

    public function groups()
    {
        return $this->belongsToMany(ContactGroup::class, 'contact_group_contact');
    }
}
