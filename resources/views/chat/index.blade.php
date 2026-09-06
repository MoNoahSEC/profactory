@extends('layouts.app')

@section('title', 'شات الإدارة والمسؤولين')
@section('page_title', 'شات الإدارة الفوري (واتساب)')

@push('styles')
<style>
    /* ══════════════════════════════════════════════════════
       📱 WHATSAPP WEB STYLE CHAT INTERFACE
       ══════════════════════════════════════════════════════ */
    .whatsapp-container {
        display: flex;
        height: calc(100vh - 140px);
        min-height: 580px;
        background: #f0f2f5;
        border-radius: 18px;
        overflow: hidden;
        border: 1px solid #d1d7db;
        box-shadow: 0 10px 30px rgba(0,0,0,0.08);
        position: relative;
    }

    [data-bs-theme="dark"] .whatsapp-container {
        background: #111b21;
        border-color: #222e35;
    }

    /* --- SIDEBAR (CHATS LIST) --- */
    .whatsapp-sidebar {
        width: 380px;
        min-width: 320px;
        background: #ffffff;
        border-left: 1px solid #e9edef;
        display: flex;
        flex-direction: column;
        height: 100%;
        transition: all 0.3s ease;
        z-index: 10;
    }

    [data-bs-theme="dark"] .whatsapp-sidebar {
        background: #111b21;
        border-color: #222e35;
    }

    .whatsapp-sidebar-header {
        background: #f0f2f5;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #e9edef;
    }

    [data-bs-theme="dark"] .whatsapp-sidebar-header {
        background: #202c33;
        border-color: #222e35;
    }

    .whatsapp-search-box {
        padding: 10px 14px;
        background: #ffffff;
        border-bottom: 1px solid #f0f2f5;
    }

    [data-bs-theme="dark"] .whatsapp-search-box {
        background: #111b21;
        border-color: #222e35;
    }

    .whatsapp-search-input {
        background: #f0f2f5;
        border: none;
        border-radius: 10px;
        padding: 8px 14px 8px 36px;
        font-size: 0.9rem;
        width: 100%;
        color: inherit;
        outline: none;
    }

    [data-bs-theme="dark"] .whatsapp-search-input {
        background: #202c33;
        color: #e9edef;
    }

    .whatsapp-chat-list {
        flex: 1;
        overflow-y: auto;
    }

    .whatsapp-chat-item {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 16px;
        cursor: pointer;
        transition: background 0.2s;
        border-bottom: 1px solid #f5f6f6;
        position: relative;
    }

    [data-bs-theme="dark"] .whatsapp-chat-item {
        border-color: #1f2c34;
    }

    .whatsapp-chat-item:hover {
        background: #f5f6f6;
    }

    [data-bs-theme="dark"] .whatsapp-chat-item:hover {
        background: #202c33;
    }

    .whatsapp-chat-item.active {
        background: #e9edef;
    }

    [data-bs-theme="dark"] .whatsapp-chat-item.active {
        background: #2a3942;
    }

    .whatsapp-avatar {
        width: 48px;
        height: 48px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        font-weight: 700;
        color: #fff;
        flex-shrink: 0;
        position: relative;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }

    .online-indicator {
        position: absolute;
        bottom: 2px;
        left: 2px;
        width: 12px;
        height: 12px;
        background: #25d366;
        border: 2px solid #fff;
        border-radius: 50%;
    }

    .whatsapp-chat-info {
        flex: 1;
        min-width: 0;
    }

    .whatsapp-chat-title {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 3px;
    }

    .whatsapp-chat-name {
        font-weight: 700;
        font-size: 0.95rem;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        color: #111b21;
    }

    [data-bs-theme="dark"] .whatsapp-chat-name {
        color: #e9edef;
    }

    .whatsapp-chat-time {
        font-size: 0.75rem;
        color: #667781;
    }

    .whatsapp-chat-preview {
        font-size: 0.84rem;
        color: #667781;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .whatsapp-unread-badge {
        background: #25d366;
        color: #fff;
        font-size: 0.75rem;
        font-weight: 800;
        min-width: 20px;
        height: 20px;
        border-radius: 10px;
        padding: 0 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        box-shadow: 0 2px 4px rgba(37,211,102,0.3);
    }

    /* --- MAIN CHAT AREA --- */
    .whatsapp-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        height: 100%;
        background: #efeae2;
        position: relative;
    }

    [data-bs-theme="dark"] .whatsapp-main {
        background: #0b141a;
    }

    /* WhatsApp subtle chat background */
    .whatsapp-main::before {
        content: '';
        position: absolute;
        top: 0; left: 0; right: 0; bottom: 0;
        opacity: 0.05;
        background-image: radial-gradient(#128c7e 1px, transparent 1px);
        background-size: 20px 20px;
        pointer-events: none;
    }

    .whatsapp-main-header {
        background: #f0f2f5;
        padding: 10px 18px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-bottom: 1px solid #e9edef;
        z-index: 5;
    }

    [data-bs-theme="dark"] .whatsapp-main-header {
        background: #202c33;
        border-color: #222e35;
    }

    .whatsapp-messages {
        flex: 1;
        overflow-y: auto;
        padding: 20px 24px;
        display: flex;
        flex-direction: column;
        gap: 10px;
        z-index: 2;
    }

    /* Date divider */
    .whatsapp-date-divider {
        align-self: center;
        background: rgba(255,255,255,0.9);
        color: #54656f;
        font-size: 0.75rem;
        font-weight: 600;
        padding: 5px 14px;
        border-radius: 8px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        margin: 8px 0;
    }

    [data-bs-theme="dark"] .whatsapp-date-divider {
        background: #182229;
        color: #8696a0;
    }

    /* Bubbles */
    .whatsapp-bubble {
        max-width: 70%;
        min-width: 90px;
        padding: 8px 12px 6px;
        border-radius: 10px;
        position: relative;
        font-size: 0.93rem;
        line-height: 1.45;
        word-break: break-word;
        box-shadow: 0 1px 2px rgba(0,0,0,0.12);
        animation: fadeInMsg 0.2s ease-out;
    }

    @keyframes fadeInMsg {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }

    /* Outgoing (Sent by me) */
    .whatsapp-bubble.outgoing {
        align-self: flex-start; /* in RTL, left is start, or right */
        background: #d9fdd3;
        color: #111b21;
        border-top-left-radius: 0;
    }

    [data-bs-theme="dark"] .whatsapp-bubble.outgoing {
        background: #005c4b;
        color: #e9edef;
    }

    /* Incoming (Received) */
    .whatsapp-bubble.incoming {
        align-self: flex-end;
        background: #ffffff;
        color: #111b21;
        border-top-right-radius: 0;
    }

    [data-bs-theme="dark"] .whatsapp-bubble.incoming {
        background: #202c33;
        color: #e9edef;
    }

    .whatsapp-sender-tag {
        font-size: 0.78rem;
        font-weight: 800;
        margin-bottom: 3px;
        color: #ea580c;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .whatsapp-bubble-footer {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 4px;
        margin-top: 4px;
        font-size: 0.7rem;
        color: #667781;
    }

    [data-bs-theme="dark"] .whatsapp-bubble-footer {
        color: #8696a0;
    }

    .check-double-read {
        color: #53bdeb !important;
        font-size: 0.85rem;
    }

    .check-sent {
        color: #8696a0;
        font-size: 0.85rem;
    }

    /* --- INPUT BAR --- */
    .whatsapp-input-bar {
        background: #f0f2f5;
        padding: 10px 16px;
        display: flex;
        align-items: center;
        gap: 10px;
        border-top: 1px solid #e9edef;
        z-index: 5;
    }

    [data-bs-theme="dark"] .whatsapp-input-bar {
        background: #202c33;
        border-color: #222e35;
    }

    .whatsapp-text-input {
        flex: 1;
        background: #ffffff;
        border: none;
        border-radius: 12px;
        padding: 10px 16px;
        font-size: 0.95rem;
        outline: none;
        color: inherit;
        resize: none;
        max-height: 120px;
        min-height: 44px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.05);
    }

    [data-bs-theme="dark"] .whatsapp-text-input {
        background: #2a3942;
        color: #e9edef;
    }

    .whatsapp-action-btn {
        background: transparent;
        border: none;
        color: #54656f;
        font-size: 1.35rem;
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }

    [data-bs-theme="dark"] .whatsapp-action-btn {
        color: #8696a0;
    }

    .whatsapp-action-btn:hover {
        background: rgba(0,0,0,0.08);
        color: #128c7e;
    }

    [data-bs-theme="dark"] .whatsapp-action-btn:hover {
        background: rgba(255,255,255,0.08);
        color: #25d366;
    }

    .whatsapp-send-btn {
        background: #25d366;
        color: #fff !important;
        box-shadow: 0 3px 8px rgba(37,211,102,0.4);
    }

    .whatsapp-send-btn:hover {
        background: #128c7e !important;
        transform: scale(1.05);
    }

    /* Attachment preview card */
    .attachment-preview-box {
        display: flex;
        align-items: center;
        gap: 10px;
        background: #e9edef;
        padding: 8px 14px;
        border-radius: 10px;
        margin-bottom: 8px;
    }

    [data-bs-theme="dark"] .attachment-preview-box {
        background: #2a3942;
    }

    /* Media Styles */
    .chat-img-thumb {
        max-width: 260px;
        max-height: 260px;
        border-radius: 8px;
        cursor: pointer;
        transition: opacity 0.2s;
        margin-bottom: 4px;
    }
    .chat-img-thumb:hover { opacity: 0.9; }

    .chat-doc-card {
        display: flex;
        align-items: center;
        gap: 10px;
        background: rgba(0,0,0,0.05);
        padding: 10px 14px;
        border-radius: 8px;
        text-decoration: none;
        color: inherit;
        font-weight: 600;
        margin-bottom: 4px;
    }

    /* Empty state */
    .whatsapp-empty-state {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100%;
        color: #8696a0;
        text-align: center;
        padding: 20px;
    }

    /* Emoji Picker Modal/Pop */
    .emoji-popover {
        position: absolute;
        bottom: 70px;
        right: 20px;
        background: #fff;
        border-radius: 14px;
        padding: 12px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.18);
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 8px;
        font-size: 1.4rem;
        z-index: 100;
        max-height: 220px;
        overflow-y: auto;
    }

    [data-bs-theme="dark"] .emoji-popover {
        background: #202c33;
    }

    .emoji-btn {
        background: none;
        border: none;
        cursor: pointer;
        padding: 4px;
        border-radius: 6px;
        transition: transform 0.1s;
    }
    .emoji-btn:hover { transform: scale(1.25); background: rgba(0,0,0,0.05); }

    /* Mobile responsive */
    @media(max-width: 768px) {
        .whatsapp-container {
            height: calc(100vh - 100px);
            border-radius: 0;
            margin: -15px -15px 0;
        }
        .whatsapp-sidebar {
            width: 100%;
            position: absolute;
            left: 0; top: 0; bottom: 0; right: 0;
        }
        .whatsapp-sidebar.hidden-mobile {
            display: none;
        }
        .whatsapp-main.hidden-mobile {
            display: none;
        }
    }
</style>
@endpush

@section('content')
<div class="whatsapp-container" id="chatApp">

    <!-- ═══════════════════════════════════════════════
         LEFT SIDEBAR: CHAT LIST & CONTACTS
         ═══════════════════════════════════════════════ -->
    <div class="whatsapp-sidebar" id="sidebarPanel">
        <!-- Sidebar Header -->
        <div class="whatsapp-sidebar-header">
            <div class="d-flex align-items-center gap-2">
                <div class="whatsapp-avatar" style="background: linear-gradient(135deg, #ea580c, #f97316); width: 42px; height: 42px;">
                    <i class="bi bi-person-fill"></i>
                </div>
                <div>
                    <div class="fw-bold text-dark" style="font-size: 0.95rem;">{{ $currentUser->name }}</div>
                    <small class="text-success fw-semibold"><i class="bi bi-circle-fill" style="font-size: 0.55rem;"></i> متصل الآن</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button class="whatsapp-action-btn" title="تحديث المحادثات" onclick="refreshChatList()">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>

        <!-- Search Filter -->
        <div class="whatsapp-search-box position-relative">
            <i class="bi bi-search position-absolute text-muted" style="right: 26px; top: 18px;"></i>
            <input type="text" id="contactSearch" class="whatsapp-search-input" placeholder="بحث في المحادثات أو المسؤولين..." onkeyup="filterContacts()">
        </div>

        <!-- Chats List -->
        <div class="whatsapp-chat-list" id="chatsList">
            <!-- 1. General Admin Group Chat -->
            <div class="whatsapp-chat-item active" id="chatItem-group" onclick="selectChat('group', 'جروب إدارة المصنع (عام)', 'جميع المسؤولين والإداريين', 'bi-people-fill', 'linear-gradient(135deg, #10b981, #059669)')">
                <div class="whatsapp-avatar" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="whatsapp-chat-info">
                    <div class="whatsapp-chat-title">
                        <span class="whatsapp-chat-name">📢 جروب إدارة المصنع (عام)</span>
                        <span class="whatsapp-chat-time" id="time-group">{{ $groupLastMsg?->formatted_time ?? '' }}</span>
                    </div>
                    <div class="whatsapp-chat-preview">
                        <span class="text-truncate" id="preview-group">
                            @if($groupLastMsg)
                                <strong>{{ $groupLastMsg->sender->name }}:</strong> {{ $groupLastMsg->message ?: '📎 ملف مرفق' }}
                            @else
                                مرحباً بجميع المسؤولين، ابدأوا المحادثة هنا...
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2. Direct Contacts -->
            @foreach($users as $u)
                @php
                    $colors = [
                        '#3b82f6', '#8b5cf6', '#ec4899', '#f97316', '#06b6d4', '#10b981', '#6366f1'
                    ];
                    $userColor = $colors[$u->id % count($colors)];
                    $initials = mb_substr($u->name, 0, 2);
                @endphp
                <div class="whatsapp-chat-item contact-item" id="chatItem-{{ $u->id }}" data-name="{{ $u->name }} {{ $u->role_name }}" onclick="selectChat({{ $u->id }}, '{{ addslashes($u->name) }}', '{{ addslashes($u->role_name) }}', '{{ $initials }}', '{{ $userColor }}')">
                    <div class="whatsapp-avatar" style="background-color: {{ $userColor }};">
                        <span>{{ $initials }}</span>
                        <span class="online-indicator"></span>
                    </div>
                    <div class="whatsapp-chat-info">
                        <div class="whatsapp-chat-title">
                            <span class="whatsapp-chat-name">
                                {{ $u->name }}
                                <span class="badge bg-secondary bg-opacity-10 text-muted ms-1" style="font-size: 0.68rem;">{{ $u->role_name }}</span>
                            </span>
                            <span class="whatsapp-chat-time" id="time-{{ $u->id }}">{{ $u->last_message?->formatted_time ?? '' }}</span>
                        </div>
                        <div class="whatsapp-chat-preview d-flex justify-content-between align-items-center">
                            <span class="text-truncate" id="preview-{{ $u->id }}">
                                @if($u->last_message)
                                    @if($u->last_message->sender_id === $currentUser->id)
                                        <i class="bi bi-check2-all {{ $u->last_message->is_read ? 'check-double-read' : 'check-sent' }}"></i>
                                    @endif
                                    {{ $u->last_message->message ?: '📎 ملف مرفق' }}
                                @else
                                    <span class="text-muted">انقر لبدء المحادثة</span>
                                @endif
                            </span>
                            @if($u->unread_count > 0)
                                <span class="whatsapp-unread-badge ms-1" id="unread-{{ $u->id }}">{{ $u->unread_count }}</span>
                            @else
                                <span class="whatsapp-unread-badge ms-1 d-none" id="unread-{{ $u->id }}">0</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════
         MAIN CHAT VIEWPORT
         ═══════════════════════════════════════════════ -->
    <div class="whatsapp-main" id="mainPanel">
        <!-- Main Chat Header -->
        <div class="whatsapp-main-header">
            <div class="d-flex align-items-center gap-3">
                <button class="btn btn-sm btn-light rounded-circle d-md-none" onclick="backToSidebar()" title="رجوع للقائمة">
                    <i class="bi bi-arrow-right fs-5"></i>
                </button>
                <div class="whatsapp-avatar" id="headerAvatar" style="background: linear-gradient(135deg, #10b981, #059669); width: 44px; height: 44px;">
                    <i class="bi bi-people-fill" id="headerAvatarIcon"></i>
                    <span id="headerAvatarInitials" class="d-none"></span>
                </div>
                <div>
                    <h6 class="mb-0 fw-bold text-dark" id="headerTitle">📢 جروب إدارة المصنع (عام)</h6>
                    <small class="text-muted" id="headerSubtitle">جميع المسؤولين والإداريين &bull; متصل الآن</small>
                </div>
            </div>
            <div class="d-flex align-items-center gap-1">
                <button class="whatsapp-action-btn" title="تحديث المحادثة الحالية" onclick="fetchMessages(false)">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
            </div>
        </div>

        <!-- Messages Thread Container -->
        <div class="whatsapp-messages" id="messagesContainer">
            <div class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                <span class="ms-2">جارٍ تحميل الرسائل...</span>
            </div>
        </div>

        <!-- Attachment Preview Box (Hidden by default) -->
        <div id="attachmentPreviewContainer" class="px-3 pt-2 d-none">
            <div class="attachment-preview-box justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-paperclip fs-5 text-orange"></i>
                    <span id="attachmentFileName" class="fw-bold small text-truncate" style="max-width: 250px;"></span>
                </div>
                <button type="button" class="btn-close btn-sm" onclick="cancelAttachment()"></button>
            </div>
        </div>

        <!-- Emoji Popover -->
        <div id="emojiPopover" class="emoji-popover d-none">
            @php
                $emojis = ['👍', '👌', '❤️', '🔥', '👏', '🙏', '😊', '😂', '🎉', '✅', '❌', '⚠️', '📦', '🚚', '💰', '💵', '📋', '⚙️', '✨', '🏭', '🤝', '💯', '📞', '⏳'];
            @endphp
            @foreach($emojis as $em)
                <button type="button" class="emoji-btn" onclick="insertEmoji('{{ $em }}')">{{ $em }}</button>
            @endforeach
        </div>

        <!-- Input Bar -->
        <form id="chatForm" onsubmit="handleSendMessage(event)" class="whatsapp-input-bar">
            @csrf
            <button type="button" class="whatsapp-action-btn" onclick="toggleEmojiPicker()" title="إيموجي">
                <i class="bi bi-emoji-smile"></i>
            </button>
            
            <label for="attachmentInput" class="whatsapp-action-btn mb-0" style="cursor: pointer;" title="إرفاق صورة أو ملف">
                <i class="bi bi-paperclip"></i>
            </label>
            <input type="file" id="attachmentInput" name="attachment" class="d-none" onchange="handleFileSelected(event)">

            <textarea id="messageInput" name="message" class="whatsapp-text-input" placeholder="اكتب رسالة..." rows="1" onkeydown="handleInputKeydown(event)"></textarea>

            <button type="submit" id="sendBtn" class="whatsapp-action-btn whatsapp-send-btn" title="إرسال">
                <i class="bi bi-send-fill"></i>
            </button>
        </form>
    </div>
</div>

<!-- Image Lightbox Modal -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content bg-transparent border-0">
            <div class="modal-body text-center p-0">
                <img id="modalImagePreview" src="" class="img-fluid rounded shadow-lg" style="max-height: 85vh;">
            </div>
        </div>
    </div>
</div>

<!-- Subtle Audio Chime for incoming message -->
<audio id="notifSound" preload="auto">
    <source src="https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3" type="audio/mpeg">
</audio>
@endsection

@push('scripts')
<script>
    // ══════════════════════════════════════════════════════
    // 💬 REAL-TIME CHAT LOGIC WITH SMART POLLING
    // ══════════════════════════════════════════════════════
    let currentChatTarget = 'group'; // 'group' or numeric userId
    let lastMessageId = 0;
    let pollInterval = null;
    let isSending = false;
    const currentUserId = {{ $currentUser->id }};

    document.addEventListener('DOMContentLoaded', function() {
        // Initial fetch for the general group
        fetchMessages(true);

        // Start smart background polling (every 2.5 seconds)
        pollInterval = setInterval(function() {
            fetchMessages(false);
        }, 2500);
    });

    // Switch between chats
    function selectChat(targetId, title, subtitle, iconOrInitials, color) {
        currentChatTarget = targetId;
        lastMessageId = 0;

        // Update active class in sidebar
        document.querySelectorAll('.whatsapp-chat-item').forEach(item => item.classList.remove('active'));
        const activeItem = document.getElementById(`chatItem-${targetId}`);
        if (activeItem) activeItem.classList.add('active');

        // Hide unread badge for this chat
        const unreadEl = document.getElementById(`unread-${targetId}`);
        if (unreadEl) {
            unreadEl.classList.add('d-none');
            unreadEl.textContent = '0';
        }

        // Update Header Info
        document.getElementById('headerTitle').textContent = (targetId === 'group' ? '📢 ' : '💬 ') + title;
        document.getElementById('headerSubtitle').textContent = subtitle + ' • متصل الآن';

        const avatarEl = document.getElementById('headerAvatar');
        const iconEl = document.getElementById('headerAvatarIcon');
        const initialsEl = document.getElementById('headerAvatarInitials');

        avatarEl.style.background = color;
        if (targetId === 'group') {
            iconEl.classList.remove('d-none');
            initialsEl.classList.add('d-none');
        } else {
            iconEl.classList.add('d-none');
            initialsEl.classList.remove('d-none');
            initialsEl.textContent = iconOrInitials;
        }

        // Mobile responsive switch
        if (window.innerWidth <= 768) {
            document.getElementById('sidebarPanel').classList.add('hidden-mobile');
            document.getElementById('mainPanel').classList.remove('hidden-mobile');
        }

        // Clear and load messages
        const container = document.getElementById('messagesContainer');
        container.innerHTML = `
            <div class="text-center py-5 text-muted">
                <div class="spinner-border spinner-border-sm text-success" role="status"></div>
                <span class="ms-2">جارٍ تحميل الرسائل...</span>
            </div>
        `;

        fetchMessages(true);
    }

    function backToSidebar() {
        document.getElementById('sidebarPanel').classList.remove('hidden-mobile');
        document.getElementById('mainPanel').classList.add('hidden-mobile');
    }

    // Fetch messages from server
    function fetchMessages(isInitial = false) {
        const url = `{{ route('chat.messages') }}?user_id=${currentChatTarget}${lastMessageId > 0 && !isInitial ? '&after_id=' + lastMessageId : ''}`;

        fetch(url, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) return;

            const container = document.getElementById('messagesContainer');
            if (isInitial) {
                container.innerHTML = '';
            }

            if (data.messages && data.messages.length > 0) {
                let hadNewIncoming = false;

                data.messages.forEach(msg => {
                    if (msg.id > lastMessageId) {
                        lastMessageId = msg.id;
                    }

                    // Check if message is already rendered
                    if (!document.getElementById(`msg-${msg.id}`)) {
                        renderMessageBubble(msg, container);
                        if (msg.sender_id !== currentUserId && !isInitial) {
                            hadNewIncoming = true;
                        }
                    }
                });

                // Play soft chime if new incoming message received
                if (hadNewIncoming) {
                    try {
                        const audio = document.getElementById('notifSound');
                        if (audio) audio.play();
                    } catch(e) {}
                }

                // Scroll to bottom
                container.scrollTop = container.scrollHeight;
            } else if (isInitial) {
                container.innerHTML = `
                    <div class="whatsapp-empty-state">
                        <i class="bi bi-chat-heart fs-1 mb-2 text-success opacity-75"></i>
                        <h6 class="fw-bold">لا توجد رسائل سابقة</h6>
                        <p class="small text-muted">ابدأ بكتابة رسالتك الأولى وسيتلقاها الطرف الآخر فوراً!</p>
                    </div>
                `;
            }
        })
        .catch(err => {
            console.error('Chat fetch error:', err);
        });
    }

    // Render single message bubble
    function renderMessageBubble(msg, container) {
        const isMe = (msg.sender_id === currentUserId);
        const bubble = document.createElement('div');
        bubble.id = `msg-${msg.id}`;
        bubble.className = `whatsapp-bubble ${isMe ? 'outgoing' : 'incoming'}`;

        let contentHtml = '';

        // In Group Chat, show sender name for incoming messages
        if (!isMe && currentChatTarget === 'group') {
            contentHtml += `<div class="whatsapp-sender-tag"><i class="bi bi-person-circle"></i> ${escapeHtml(msg.sender.name)}</div>`;
        }

        // Render Image Attachment
        if (msg.attachment_type === 'image' && msg.attachment_url) {
            contentHtml += `
                <div>
                    <img src="${msg.attachment_url}" alt="Attachment" class="chat-img-thumb" onclick="openImageModal('${msg.attachment_url}')">
                </div>
            `;
        } else if (msg.attachment_type === 'audio' && msg.attachment_url) {
            contentHtml += `
                <div class="my-1">
                    <audio controls src="${msg.attachment_url}" style="max-width: 240px; height: 36px;"></audio>
                </div>
            `;
        } else if (msg.attachment_path && msg.attachment_url) {
            contentHtml += `
                <a href="${msg.attachment_url}" target="_blank" download class="chat-doc-card">
                    <i class="bi bi-file-earmark-arrow-down-fill fs-4 text-orange"></i>
                    <span class="text-truncate" style="max-width: 180px;">${escapeHtml(msg.attachment_name || 'تحميل الملف')}</span>
                </a>
            `;
        }

        // Text Message
        if (msg.message) {
            contentHtml += `<div>${formatMessageText(msg.message)}</div>`;
        }

        // Footer (Time & Checkmarks)
        const checkIcon = isMe 
            ? (msg.is_read ? '<i class="bi bi-check2-all check-double-read" title="تمت القراءة"></i>' : '<i class="bi bi-check2 check-sent" title="تم الإرسال"></i>')
            : '';

        contentHtml += `
            <div class="whatsapp-bubble-footer">
                <span>${msg.formatted_time}</span>
                ${checkIcon}
            </div>
        `;

        bubble.innerHTML = contentHtml;
        container.appendChild(bubble);
    }

    // Send Message Handler
    function handleSendMessage(event) {
        event.preventDefault();
        if (isSending) return;

        const input = document.getElementById('messageInput');
        const fileInput = document.getElementById('attachmentInput');
        const text = input.value.trim();
        const hasFile = fileInput.files && fileInput.files.length > 0;

        if (!text && !hasFile) return;

        isSending = true;
        const sendBtn = document.getElementById('sendBtn');
        sendBtn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status"></span>`;

        const formData = new FormData();
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('receiver_id', currentChatTarget);
        if (text) formData.append('message', text);
        if (hasFile) formData.append('attachment', fileInput.files[0]);

        fetch(`{{ route('chat.send') }}`, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(res => res.json())
        .then(data => {
            isSending = false;
            sendBtn.innerHTML = `<i class="bi bi-send-fill"></i>`;

            if (data.success && data.message) {
                // Clear input
                input.value = '';
                cancelAttachment();
                hideEmojiPicker();

                // Append sent message
                const container = document.getElementById('messagesContainer');
                // Remove empty state if present
                const emptyState = container.querySelector('.whatsapp-empty-state');
                if (emptyState) emptyState.remove();

                if (data.message.id > lastMessageId) {
                    lastMessageId = data.message.id;
                }
                renderMessageBubble(data.message, container);
                container.scrollTop = container.scrollHeight;

                // Update sidebar preview
                updateSidebarPreview(currentChatTarget, data.message.message || '📎 ملف مرفق', data.message.formatted_time);
            }
        })
        .catch(err => {
            isSending = false;
            sendBtn.innerHTML = `<i class="bi bi-send-fill"></i>`;
            console.error('Send error:', err);
            alert('حدث خطأ أثناء إرسال الرسالة، يرجى المحاولة مرة أخرى.');
        });
    }

    function updateSidebarPreview(targetId, text, time) {
        const previewEl = document.getElementById(`preview-${targetId}`);
        const timeEl = document.getElementById(`time-${targetId}`);
        if (previewEl) previewEl.textContent = text;
        if (timeEl) timeEl.textContent = time;
    }

    // Auto-expand and Enter to send
    function handleInputKeydown(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            handleSendMessage(event);
        }
    }

    // File Selected
    function handleFileSelected(event) {
        const file = event.target.files[0];
        if (!file) return;

        document.getElementById('attachmentFileName').textContent = file.name;
        document.getElementById('attachmentPreviewContainer').classList.remove('d-none');
    }

    function cancelAttachment() {
        const fileInput = document.getElementById('attachmentInput');
        fileInput.value = '';
        document.getElementById('attachmentPreviewContainer').classList.add('d-none');
    }

    // Emoji Picker
    function toggleEmojiPicker() {
        const pop = document.getElementById('emojiPopover');
        pop.classList.toggle('d-none');
    }

    function hideEmojiPicker() {
        document.getElementById('emojiPopover').classList.add('d-none');
    }

    function insertEmoji(emoji) {
        const input = document.getElementById('messageInput');
        input.value += emoji;
        input.focus();
    }

    // Search contacts
    function filterContacts() {
        const query = document.getElementById('contactSearch').value.toLowerCase().trim();
        const items = document.querySelectorAll('.contact-item');

        items.forEach(item => {
            const name = item.getAttribute('data-name').toLowerCase();
            if (name.includes(query)) {
                item.style.display = 'flex';
            } else {
                item.style.display = 'none';
            }
        });
    }

    // Refresh contact list
    function refreshChatList() {
        window.location.reload();
    }

    // Image Modal Lightbox
    function openImageModal(url) {
        document.getElementById('modalImagePreview').src = url;
        const modal = new bootstrap.Modal(document.getElementById('imageModal'));
        modal.show();
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function formatMessageText(text) {
        return escapeHtml(text).replace(/\n/g, '<br>');
    }
</script>
@endpush
