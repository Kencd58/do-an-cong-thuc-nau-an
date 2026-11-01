<?php

namespace App\Http\Requests;

class UpdateRecipeRequest extends StoreRecipeRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null
            && (int) $this->user()->id === (int) $this->route('recipe')->userId;
    }
}
