<?php

namespace App\Http\Controllers;

use App\Models\Alert;
use App\Models\Claim;
use App\Models\Message;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

// Chat serah terima, diakses lewat jQuery AJAX (polling tiap 5 detik)
class MessageController extends Controller
{
    public function index(Request $request, Claim $claim)
    {
        $this->authorizeChat($claim);

        $messages = $claim->messages()->with('user')
            ->where('id', '>', (int) $request->query('after', 0))
            ->orderBy('id')
            ->get()
            ->map(fn (Message $m) => [
                'id' => $m->id,
                'body' => $m->body,
                'mine' => $m->user_id === Auth::id(),
                'name' => $m->user->isAdmin() ? 'Admin' : $m->user->short_name,
                'time' => $m->created_at->format('H:i'),
            ]);

        return response()->json(['data' => $messages], 200);
    }

    public function store(Request $request, Claim $claim)
    {
        $this->authorizeChat($claim);

        $validated = $request->validate([
            'body' => 'required|string|min:1|max:1000',
        ]);

        $message = $claim->messages()->create([
            'user_id' => Auth::id(),
            'body' => $validated['body'],
        ]);

        // Beri tahu pihak lain, tanpa menumpuk notifikasi yang belum dibaca
        $url = route('claims.show', $claim);
        $recipients = collect([$claim->user_id, $claim->report->user_id])->reject(fn ($id) => $id === Auth::id())->unique();
        foreach ($recipients as $userId) {
            $hasUnread = Alert::where('user_id', $userId)->where('type', 'message')->where('url', $url)->whereNull('read_at')->exists();
            if (! $hasUnread) {
                Alert::send($userId, 'message', 'Pesan baru dari '.Auth::user()->short_name, $claim->report->title, $url);
            }
        }

        return response()->json([
            'data' => [
                'id' => $message->id,
                'body' => $message->body,
                'mine' => true,
                'name' => Auth::user()->short_name,
                'time' => $message->created_at->format('H:i'),
            ],
        ], 201);
    }

    private function authorizeChat(Claim $claim): void
    {
        $claim->loadMissing('report');
        abort_unless($claim->canBeViewedBy(Auth::user()) && $claim->chatOpen(), 403, 'Chat terbuka setelah klaim disetujui.');
    }
}
