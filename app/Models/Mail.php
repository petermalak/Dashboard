<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @method static create(array $input)
 */
class Mail extends Model
{
    use HasFactory;
    protected $fillable = ['sender','receiver','subject','message', 'sent_time','scheduled'];
    public static $cast = [
        'receiver' => 'required',
        'subject' => 'required',
        'message' => 'required',
    ];
}
