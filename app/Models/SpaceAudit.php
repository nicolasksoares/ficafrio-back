<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SpaceAudit extends Model
{
    protected $fillable = ['space_id', 'admin_id', 'old_status', 'new_status', 'reason'];
}