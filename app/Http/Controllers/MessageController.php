<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\QuoteRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function quoteRequestIndex(Request $request, QuoteRequest $quoteRequest): JsonResponse
    {
        $this->authorizeQuoteRequest($request, $quoteRequest);

        return $this->threadResponse($quoteRequest, $request);
    }

    public function storeQuoteRequestMessage(Request $request, QuoteRequest $quoteRequest): JsonResponse
    {
        $this->authorizeQuoteRequest($request, $quoteRequest);

        return $this->storeMessage($quoteRequest, $request);
    }

    public function projectIndex(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        return $this->threadResponse($project, $request);
    }

    public function storeProjectMessage(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        return $this->storeMessage($project, $request);
    }

    private function threadResponse(QuoteRequest|Project $parent, Request $request): JsonResponse
    {
        $query = $parent->messages()->getQuery();

        if ($after = $request->integer('after')) {
            $query->where('id', '>', $after);
        }

        $messages = $query->oldest('id')->get();

        return response()->json([
            'messages' => $messages->map(fn ($message) => $this->formatMessage($message, $request->user())),
        ]);
    }

    private function storeMessage(QuoteRequest|Project $parent, Request $request): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $parent->messages()->create([
            'user_id' => $request->user()->id,
            'body' => $data['body'],
        ]);

        $message->setRelation('user', $request->user());

        return response()->json([
            'message' => $this->formatMessage($message, $request->user()),
        ], 201);
    }

    private function formatMessage($message, $user): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'created_at' => $message->created_at->toIso8601String(),
            'created_at_human' => $message->created_at->diffForHumans(),
            'user_name' => $message->user->name ?? 'Deleted user',
            'is_mine' => $message->user_id === $user->id,
        ];
    }

    private function authorizeQuoteRequest(Request $request, QuoteRequest $quoteRequest): void
    {
        $user = $request->user();

        $allowed = $user->hasAnyRole(['ceo', 'admin'])
            || $quoteRequest->client_id === $user->id
            || $quoteRequest->assigned_to === $user->id;

        abort_unless($allowed, 403);
    }

    private function authorizeProject(Request $request, Project $project): void
    {
        $user = $request->user();

        $allowed = $user->hasAnyRole(['ceo', 'admin'])
            || $project->client_id === $user->id
            || $project->lead_expert_id === $user->id
            || $project->teamMembers()->where('user_id', $user->id)->exists();

        abort_unless($allowed, 403);
    }
}
