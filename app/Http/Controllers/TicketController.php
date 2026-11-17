<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TicketController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json($request->user()->tickets()->latest()->get());
    }

    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:10000'],
            'status' => ['sometimes', 'required', 'in:open,closed'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 400);
        }

        $ticket = $request->user()->tickets()->create($validator->validated());

        return response()->json($ticket, 201);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        return response()->json($this->findForUser($request, $id));
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $ticket = $this->findForUser($request, $id);
        $validator = Validator::make($request->all(), [
            'title' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'required', 'string', 'max:10000'],
            'status' => ['sometimes', 'required', 'in:open,closed'],
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'The given data was invalid.', 'errors' => $validator->errors()], 400);
        }

        if ($validator->validated() === []) {
            return response()->json(['message' => 'At least one ticket field must be provided.'], 400);
        }

        $ticket->update($validator->validated());

        return response()->json($ticket->refresh());
    }

    public function destroy(Request $request, int $id): JsonResponse
    {
        $this->findForUser($request, $id)->delete();

        return response()->json(['message' => 'Ticket deleted successfully.']);
    }

    private function findForUser(Request $request, int $id): Ticket
    {
        return $request->user()->tickets()->findOrFail($id);
    }
}
