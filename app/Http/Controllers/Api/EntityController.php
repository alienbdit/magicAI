<?php

namespace App\Http\Controllers\Api;

use App\Domains\Entity\Models\Entity;
use App\Http\Controllers\Controller;
use App\Models\Token;
use Illuminate\Http\Request;

class EntityController extends Controller
{

    public function getAllEntities(Request $request)
    {
        $entities = Entity::all();
        $tokens = Token::all();

        foreach ($entities as $entity) {
            $entity->token_type = $entity->key->tokenType();
            $entity->tokens = $tokens->where('entity_id', $entity->id)->first();
            $entity->key_name = $entity->key->keyAsString();
            $entity->key_value = $entity->key->valueAsString();
        }

        return response()->json($entities);
    }
}
