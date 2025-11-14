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
        $count = (int) $request->query('count', 5);
        $count = max(1, min($count, 50));

        $persons = Person::factory()->count($count)->create();

        return response()->json([
            'success' => true,
            'message' => 'Registros de exemplo gerados e persistidos no banco',
            'data' => $persons,
        ], 201);
    }

    public function clear(Request $request): JsonResponse
    {
        Person::truncate();

        return response()->json([
            'success' => true,
            'message' => 'Pessoas removidas com sucesso',
        ], 200);
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
