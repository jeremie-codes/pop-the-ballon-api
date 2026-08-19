<x-filament-panels::page class="!p-0">
    <script src="https://cdn.tailwindcss.com"></script>

    <div id="chat-container"
        class="flex flex-col min-h-0 h-[calc(100dvh-8rem)] overflow-hidden border border-gray-200 dark:border-gray-800 rounded-xl relative">

        {{-- Background --}}
        <div class="absolute inset-0 z-0 pointer-events-none">
            <img src="{{ asset('images/bg_msg.png') }}" class="object-cover w-full h-full opacity-10" alt="">
        </div>


        {{-- ========================================================= --}}
        {{-- HEADER --}}
        {{-- ========================================================= --}}

        @php
        $client = $this->record->client;
        @endphp

        <div
            class="relative z-10 flex items-center gap-4 px-5 py-4 bg-white/95 dark:bg-[#161617]/95 backdrop-blur border-b border-gray-200 dark:border-gray-800 shrink-0">

            {{-- Avatar --}}
            <img src="{{ $client?->avatar
                    ? asset('storage/' . $client->avatar)
                    : asset('default-avatar.png') }}"
                class="object-cover w-12 h-12 bg-gray-100 rounded-full dark:bg-gray-800" alt="">

            {{-- User info --}}
            <div class="flex-1 min-w-0">

                <div class="flex items-center gap-2">

                    <h2 class="text-base font-semibold truncate">
                        {{ $client?->first_name }}
                        {{ $client?->last_name }}
                    </h2>

                    @if($client?->verified)
                    <img src="{{ asset('images/badge-check.png') }}" class="object-contain w-5 h-5 shrink-0"
                        alt="Compte vérifié">
                    @endif

                </div>

                <div class="text-sm text-gray-500 dark:text-gray-400">
                    Conversation support
                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- MESSAGES --}}
        {{-- ========================================================= --}}

        <div id="messages-container"
            class="relative z-10 flex-1 min-h-0 px-5 py-5 space-y-3 overflow-x-hidden overflow-y-auto">

            @php
            $messages = $this->record
            ->messages()
            ->with('sender')
            ->oldest()
            ->get();
            @endphp


            @forelse($messages as $msg)

            @php
            $isMine = $msg->sender_id === auth()->id();
            $messageType = $msg->type?->value;
            @endphp


            {{-- ================================================= --}}
            {{-- MESSAGE ENVOYÉ --}}
            {{-- ================================================= --}}

            @if($isMine)

            <div class="flex justify-end">

                <div class="max-w-[75%] sm:max-w-md overflow-hidden text-white bg-[#f45164] rounded-t-2xl rounded-bl-2xl shadow-sm">

                    {{-- IMAGE --}}
                    @if($messageType === 'image')

                    <img src="{{ asset('storage/' . $msg->attachment) }}"
                        class="block max-w-full max-h-[400px] object-contain rounded-xl" alt="Image">

                    {{-- VIDEO --}}
                    @elseif($messageType === 'video')

                    <video controls preload="metadata" class="block max-w-full max-h-[400px] rounded-xl">
                        <source src="{{ asset('storage/' . $msg->attachment) }}" type="{{ $msg->attachment_mime }}">
                    </video>

                    {{-- TEXTE --}}
                    @else

                    <div class="px-4 py-2">
                        <p class="break-words">
                            {{ $msg->body }}
                        </p>
                    </div>

                    @endif


                    {{-- Time --}}
                    <div class="flex justify-end px-3 pb-2">

                        <span class="text-[11px] text-white/70">
                            {{ $msg->created_at->format('H:i') }}
                        </span>

                    </div>

                </div>

            </div>


            {{-- ================================================= --}}
            {{-- MESSAGE CLIENT --}}
            {{-- ================================================= --}}

            @else

            <div class="flex justify-start">

                <div class="max-w-[75%] sm:max-w-md overflow-hidden bg-white dark:bg-[#161617] text-gray-900 dark:text-white rounded-t-2xl rounded-br-2xl shadow-sm border border-gray-100 dark:border-gray-800">

                    {{-- IMAGE --}}
                    @if($messageType === 'image')

                    <img src="{{ asset('storage/' . $msg->attachment) }}"
                        class="block max-w-full max-h-[400px] object-contain rounded-xl" alt="Image">

                    {{-- VIDEO --}}
                    @elseif($messageType === 'video')

                    <video controls preload="metadata" class="block max-w-full max-h-[400px] rounded-xl">
                        <source src="{{ asset('storage/' . $msg->attachment) }}" type="{{ $msg->attachment_mime }}">
                    </video>

                    {{-- TEXTE --}}
                    @else

                    <div class="px-4 py-2">
                        <p class="break-words">
                            {{ $msg->body }}
                        </p>
                    </div>

                    @endif


                    {{-- Time --}}
                    <div class="flex justify-end px-3 pb-2">

                        <span class="text-[11px] text-gray-400">
                            {{ $msg->created_at->format('H:i') }}
                        </span>

                    </div>

                </div>

            </div>

            @endif

            @empty

            <div class="flex items-center justify-center h-full">

                <div class="text-center text-gray-500">

                    <div class="flex justify-center mb-3">

                        <x-heroicon-o-chat-bubble-left-right class="w-10 h-10" />

                    </div>

                    <p class="text-sm">
                        Aucun message dans cette conversation.
                    </p>

                </div>

            </div>

            @endforelse

        </div>


        {{-- ========================================================= --}}
        {{-- COMPOSER --}}
        {{-- ========================================================= --}}

        <form wire:submit.prevent="sendMessage" enctype="multipart/form-data"
            class="relative z-10 shrink-0 flex items-end gap-3 px-4 py-4 bg-white/95 dark:bg-[#161617]/95 backdrop-blur border-t border-gray-200 dark:border-gray-800">

            {{-- Attachment --}}
            <div class="shrink-0">

                <label for="attachment"
                    class="flex items-center justify-center w-10 h-10 transition bg-gray-100 rounded-full cursor-pointer dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700">

                    <x-heroicon-o-paper-clip class="w-5 h-5 text-gray-500" />

                </label>

                <input id="attachment" type="file" wire:model="attachment" accept="image/*,video/*" class="hidden">

            </div>


            {{-- Attachment preview --}}
            @if($attachment)

            <div
                class="absolute flex items-center gap-3 px-3 py-2 mb-2 bg-white border border-gray-200 shadow-lg bottom-full left-4 right-4 rounded-xl dark:bg-gray-900 dark:border-gray-700">

                <div class="flex-1 min-w-0">

                    <p class="text-sm font-medium truncate">
                        {{ $attachment->getClientOriginalName() }}
                    </p>

                    <p class="text-xs text-gray-500">
                        {{ $attachment->getMimeType() }}
                    </p>

                </div>

                <button type="button" wire:click="$set('attachment', null)"
                    class="flex items-center justify-center w-8 h-8 text-red-500 rounded-full hover:bg-gray-100 dark:hover:bg-gray-800">
                    <x-heroicon-o-x-mark class="w-5 h-5" />
                </button>

            </div>

            @endif


            {{-- Message --}}
            <textarea wire:model="message" rows="1" placeholder="Écrire un message..." class="
                    flex-1
                    resize-none
                    px-4
                    py-2.5
                    border
                    border-gray-200
                    dark:border-gray-700
                    rounded-2xl
                    bg-gray-50
                    dark:bg-gray-900
                    focus:ring-2
                    focus:ring-[#f45164]
                    focus:border-transparent
                    outline-none
                "></textarea>


            {{-- Send --}}
            <button type="submit" wire:loading.attr="disabled" wire:target="sendMessage,attachment"
                class="shrink-0 flex items-center justify-center gap-2 px-5 py-2.5 text-white bg-[#f45164] hover:bg-[#e84457] rounded-2xl transition disabled:opacity-50">

                <x-heroicon-o-paper-airplane class="w-5 h-5" />

                <span class="hidden sm:inline">
                    Envoyer
                </span>

            </button>

        </form>

    </div>


    {{-- ============================================================= --}}
    {{-- AUTO-SCROLL --}}
    {{-- ============================================================= --}}

    <script>
        function scrollChatToBottom() {
            const container = document.getElementById('messages-container');

            if (!container) {
                return;
            }

            requestAnimationFrame(() => {
                container.scrollTop = container.scrollHeight;
            });
        }

        document.addEventListener('DOMContentLoaded', () => {
            scrollChatToBottom();
        });

        document.addEventListener('livewire:navigated', () => {
            scrollChatToBottom();
        });

        document.addEventListener('livewire:initialized', () => {
            scrollChatToBottom();
        });

        Livewire.hook('morph.updated', () => {
            scrollChatToBottom();
        });
    </script>

</x-filament-panels::page>
