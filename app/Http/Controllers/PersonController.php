<?php

namespace App\Http\Controllers;

use App\Models\Person;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PersonController extends Controller
{
    /**
     * Lista todas as pessoas cadastradas
     */
    public function index(): JsonResponse
    {
        $persons = Person::all();
        
        return response()->json([
            'success' => true,
            'data' => $persons
        ]);
    }

    public function sample(Request $request): JsonResponse
    {
        $person = Person::create([
            'nome' => 'teste',
            'telefone' => '123',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pessoa cadastrada com sucesso',
            'data' => $person
        ], 201);
    }    

    /**
     * Cadastra uma nova pessoa
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'telefone' => 'required|string|max:20',
        ]);

        $person = Person::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Pessoa cadastrada com sucesso',
            'data' => $person
        ], 201);
    }
}
