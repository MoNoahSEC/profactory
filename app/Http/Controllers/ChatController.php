<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ChatController extends Controller
{
    /**
     * Display the WhatsApp-style Chat UI.
     */
    public function index(Request $request): View
    {
        $currentUser = Auth::user();

        // Get all other users with their roles
        $users = User::with('roles')
            ->where('id', '!=', $currentUser->id)
            ->get()
            ->map(function ($user) use ($currentUser) {
                // Last message between currentUser and this user
                $lastMsg = ChatMessage::betweenUsers($currentUser->id, $user->id)
                    ->latest()
                    ->first();

                // Unread messages count sent by this user to currentUser
                $unread = ChatMessage::where('sender_id', $user->id)
                    ->where('receiver_id', $currentUser->id)
                    ->where('is_read', false)
                    ->count();

                $user->last_message = $lastMsg;
                $user->unread_count = $unread;
                $user->role_name    = $user->roles->first()?->name ?? 'مستخدم';
                return $user;
            })
            ->sortByDesc(function ($user) {
                return $user->last_message?->created_at ?? $user->created_at;
            })
            ->values();

        // Group Chat last message
        $groupLastMsg = ChatMessage::groupMessages()->latest()->first();

        return view('chat.index', compact('users', 'groupLastMsg', 'currentUser'));
    }

    /**
     * Get messages for a specific conversation (Direct or Group).
     */
    public function getMessages(Request $request): JsonResponse
    {
        $currentUser = Auth::user();
        $targetId    = $request->query('user_id'); // 0 or null means Group Chat
        $afterId     = $request->query('after_id');

        $query = ChatMessage::with('sender:id,name,email');

        if (empty($targetId) || $targetId === '0' || $targetId === 'group') {
            // Group Chat
            $query->groupMessages();
        } else {
            // Direct 1-on-1 Chat
            $targetUserId = (int) $targetId;
            $query->betweenUsers($currentUser->id, $targetUserId);

            // Automatically mark received messages as read
            ChatMessage::where('sender_id', $targetUserId)
                ->where('receiver_id', $currentUser->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }

        if ($afterId && is_numeric($afterId) && $afterId > 0) {
            $query->where('id', '>', $afterId);
        } else {
            // Initial load: limit to last 80 messages
            $messagesCount = (clone $query)->count();
            if ($messagesCount > 80) {
                $query->skip($messagesCount - 80)->take(80);
            }
        }

        $messages = $query->orderBy('id', 'asc')->get();

        return response()->json([
            'success'  => true,
            'messages' => $messages,
        ]);
    }

    /**
     * Send a new message (text, attachment, voice).
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $request->validate([
            'receiver_id' => 'nullable',
            'message'     => 'nullable|string|max:5000',
            'attachment'  => 'nullable|file|max:20480', // 20MB max
        ]);

        $currentUser = Auth::user();
        $receiverId  = $request->input('receiver_id');
        if (empty($receiverId) || $receiverId === '0' || $receiverId === 'group') {
            $receiverId = null;
        } else {
            $receiverId = (int) $receiverId;
        }

        $messageText = trim($request->input('message', ''));
        $attachmentPath = null;
        $attachmentType = null;
        $attachmentName = null;

        if ($request->hasFile('attachment')) {
            $file = $request->file('attachment');
            $attachmentName = $file->getClientOriginalName();
            $mime = $file->getMimeType();

            if (str_starts_with($mime, 'image/')) {
                $attachmentType = 'image';
            } elseif (str_starts_with($mime, 'audio/')) {
                $attachmentType = 'audio';
            } else {
                $attachmentType = 'document';
            }

            $attachmentPath = $file->store('chat_attachments', 'public');
        }

        if (empty($messageText) && empty($attachmentPath)) {
            return response()->json([
                'success' => false,
                'message' => 'يرجى كتابة رسالة أو إرفاق ملف.',
            ], 422);
        }

        $chatMessage = ChatMessage::create([
            'sender_id'       => $currentUser->id,
            'receiver_id'     => $receiverId,
            'message'         => $messageText ?: null,
            'attachment_path' => $attachmentPath,
            'attachment_type' => $attachmentType,
            'attachment_name' => $attachmentName,
            'is_read'         => false,
        ]);

        $chatMessage->load('sender:id,name,email');

        return response()->json([
            'success' => true,
            'message' => $chatMessage,
        ]);
    }

    /**
     * Mark all unread messages from a sender as read.
     */
    public function markAsRead(Request $request): JsonResponse
    {
        $currentUser = Auth::user();
        $senderId    = (int) $request->input('sender_id');

        if ($senderId > 0) {
            ChatMessage::where('sender_id', $senderId)
                ->where('receiver_id', $currentUser->id)
                ->where('is_read', false)
                ->update([
                    'is_read' => true,
                    'read_at' => now(),
                ]);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Get total unread count for badges.
     */
    public function getUnreadCount(): JsonResponse
    {
        $currentUser = Auth::user();
        if (! $currentUser) {
            return response()->json(['unread_count' => 0]);
        }

        $totalUnread = ChatMessage::where('receiver_id', $currentUser->id)
            ->where('is_read', false)
            ->count();

        return response()->json([
            'unread_count' => $totalUnread,
        ]);
    }

    /**
     * Delete a chat message.
     */
    public function destroy(ChatMessage $message): JsonResponse
    {
        $currentUser = Auth::user();

        // Only sender or Admin can delete
        if ($message->sender_id !== $currentUser->id && ! $currentUser->hasRole('Admin')) {
            return response()->json(['success' => false, 'error' => 'غير مصرح لك بحذف هذه الرسالة'], 403);
        }

        if ($message->attachment_path) {
            Storage::disk('public')->delete($message->attachment_path);
        }

        $message->delete();

        return response()->json(['success' => true]);
    }
}
