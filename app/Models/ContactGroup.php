<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactGroup extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'name', 'description', 'color'];

    public function user() { return $this->belongsTo(User::class); }

    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_group_contact');
    }

    public function campaigns()
    {
        return $this->hasMany(Campaign::class);
    }
}
