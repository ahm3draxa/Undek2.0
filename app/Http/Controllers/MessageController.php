<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\NotifiesThreadParticipants;
use App\Models\Project;
use App\Models\QuoteRequest;
use App\Models\User;
use App\Support\Organization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MessageController extends Controller
{
    use NotifiesThreadParticipants;

    public function quoteRequestIndex(Request $request, QuoteRequest $quoteRequest): JsonResponse
    {
        $this->authorizeQuoteRequest($request, $quoteRequest);

        return $this->threadResponse($quoteRequest->messages(), $request);
    }

    public function storeQuoteRequestMessage(Request $request, QuoteRequest $quoteRequest): JsonResponse
    {
        $this->authorizeQuoteRequest($request, $quoteRequest);

        return $this->storeMessage($quoteRequest, $request, 'client');
    }

    public function projectIndex(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        return $this->threadResponse($project->clientMessages(), $request);
    }

    public function storeProjectMessage(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProject($request, $project);

        return $this->storeMessage($project, $request, 'client');
    }

    /**
     * Internal-only channel for the CEO/admins and the project team to
     * coordinate and track progress — never visible to the client.
     */
    public function projectTeamIndex(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProjectTeam($request, $project);

        return $this->threadResponse($project->teamMessages(), $request);
    }

    public function storeProjectTeamMessage(Request $request, Project $project): JsonResponse
    {
        $this->authorizeProjectTeam($request, $project);

        return $this->storeMessage($project, $request, 'team');
    }

    private function threadResponse(MorphMany $relation, Request $request): JsonResponse
    {
        $query = $relation->getQuery();

        if ($after = $request->integer('after')) {
            $query->where('id', '>', $after);
        }

        // The underlying relations apply a `latest('id')` ordering (used elsewhere
        // for "most recent first" lookups), so a plain ->oldest('id') here would
        // just stack a second, ignored ORDER BY on the same column — reorder()
        // clears it first to actually get oldest-to-newest for the chat feed.
        $messages = $query->reorder('id', 'asc')->get();

        return response()->json([
            'messages' => $messages->map(fn ($message) => $this->formatMessage($message, $request->user())),
        ]);
    }

    private function storeMessage(QuoteRequest|Project $parent, Request $request, string $channel): JsonResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $message = $this->postToThread($parent, $request->user(), $data['body'], 'message', $channel);

        return response()->json([
            'message' => $this->formatMessage($message, $request->user()),
        ], 201);
    }

    private function formatMessage($message, $user): array
    {
        $sender = $message->user;

        return [
            'id' => $message->id,
            'user_id' => $message->user_id,
            'type' => $message->type,
            'body' => $message->body,
            'created_at' => $message->created_at->toIso8601String(),
            'created_at_human' => $message->created_at->diffForHumans(),
            'user_name' => $sender->name ?? 'Deleted user',
            'user_initials' => $sender?->initials() ?? '?',
            'user_avatar' => $sender?->avatarUrl(),
            'user_role' => $sender ? $this->roleLabel($sender) : null,
            'user_role_color' => $sender ? $this->roleColor($sender) : 'gray',
            'is_mine' => $message->user_id === $user->id,
        ];
    }

    /**
     * A short, human label for who this person is — their job title (e.g.
     * "Co-Founder", "SEO Specialist") so a client can tell who they're
     * talking to at a glance; clients are simply "Client".
     */
    private function roleLabel(User $sender): string
    {
        return $sender->isStaff() ? $sender->title() : 'Client';
    }

    private function roleColor(User $sender): string
    {
        return match (true) {
            $sender->hasAnyRole([Organization::OWNER, Organization::ADMIN]) => 'violet',
            $sender->isStaff() => 'cyan',
            default => 'amber',
        };
    }

    private function authorizeQuoteRequest(Request $request, QuoteRequest $quoteRequest): void
    {
        $user = $request->user();

        $allowed = $user->can('manage all work')
            || $quoteRequest->client_id === $user->id
            || $quoteRequest->assigned_to === $user->id;

        abort_unless($allowed, 403);
    }

    private function authorizeProject(Request $request, Project $project): void
    {
        $user = $request->user();

        abort_unless($project->client_id === $user->id || $project->hasInternalAccess($user), 403);
    }

    /**
     * Staff only — explicitly excludes the client even if somehow matched above.
     */
    private function authorizeProjectTeam(Request $request, Project $project): void
    {
        $user = $request->user();

        abort_unless($project->hasInternalAccess($user), 403);
    }
}
