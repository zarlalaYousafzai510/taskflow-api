<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
   public function replies()
   {
       return $this->hasMany(TicketReply::class)->with('user:id,name,role')->oldest();
   }

   protected $fillable = [
    'title',
    'description',
    'status',
    'user_id',
]; 
}
