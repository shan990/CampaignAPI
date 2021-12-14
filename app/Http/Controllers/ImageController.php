<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ImageController extends Controller
{
    function validateImage($image){
        //considering that the API is always going to return success
        return ['success'=>true, 'message'=>'Image successfully validated'];
    }
}
