<x-filament-panels::page class="flex flex-col flex-1 h-full">

    {{-- Script Tailwind CDN --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <div class="flex flex1 flex-col border relative top-0 right-0 border-border-[#161617] rounded-xl overflow-hidden m-0 p-0" id="chat-container">

        {{-- Image de fond --}}
        <div class="absolute inset-0 z-0">
            <img src="{{ asset('images/bg_msg.png') }}" class="object-cover w-full h-full opacity-20">
        </div>

        {{-- Header --}}
        <div class="z-10 flex items-center gap-3 p-5 bg-white dark:bg-[#161617]">
            @php
            $client = $this->record->client;
            @endphp

            <img src="{{ $client?->avatar
                    ? asset('storage/'.$client->avatar)
                    : asset('default-avatar.png') }}" class="object-cover w-12 h-12 bg-[#09090b] rounded-full" alt="">

            <div>
                <div class="flex flex-row items-center gap-1">
                    <div class="text-lg font-bold">
                        {{ $client?->first_name }} {{ $client?->last_name }}
                    </div>
                    <div>
                        @if(!$client?->verified)
                        <img src="{{ asset('images/badge-check.png') }}"
                            class="object-cover w-5 h-5 bg-[#09090b] rounded-full" alt="">
                        @endif
                    </div>

                </div>
                <div class="text-sm text-gray-500">
                    Support conversation
                </div>
            </div>
        </div>

        {{-- Messages --}}
        <div class="p-5 space-y-3 roundedxl overflow-y-auto h-[80vh] border-t border-b overflow-hidden border-white dark:border-[#161617] overrflow-x-scroll">

            @foreach($this->record->messages()->latest()->get()->reverse() as $msg)

            @if($msg->sender_id == auth()->id())

            <div class="flex justify-end">

                <div class="max-w-md min-w-28 text-white bg-[#BF296D] rounded-t-xl rounded-bl-xl {{ $msg->type->value === 'image' || $msg->type->value === 'video' ? 'p-1': 'px-4 py-2' }}">

                    @if($msg->type->value === 'text')

                    {{ $msg->body }}

                    @elseif($msg->type->value === 'image')

                    <img src="{{ asset('storage/'.$msg->attachment) }}" class="max-w-xs rounded-xl" alt="image" />

                    @elseif($msg->type->value === 'video')

                    <video controls class="max-w-xs rounded-xl">
                        <source src="{{ asset('storage/'.$msg->attachment) }}" type="{{ $msg->attachment_mime }}">
                    </video>

                    @endif

                    <div class="flex justify-end">
                        <span class="text-xs text-gray-200">
                            {{ $msg->created_at->format('H:i') }}
                        </span>
                    </div>
                </div>

            </div>

            @else

            <div class="flex justify-start">
                <div class="max-w-md px-4 py-2 bg-white shadow-sm dark:bg-[#161617] dark:text-white rounded-br-xl">
                    {{ $msg->body }}
                </div>
            </div>
            @endif

            @endforeach


        </div>

        {{-- Send --}}
        <form wire:submit.prevent="sendMessage" enctype="multipart/form-data"  class="z-10 flex items-center justify-between gap-3 p-5">

            <label for="attachment"
                class="flex items-center justify-center w-10 h-10 transition rounded-full cursor-pointer bg-pink-500/10 hover:bg-pink-800/30">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8"
                    stroke="currentColor" class="w-6 h-6 text-gray-500">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l9.193-9.193a3 3 0 014.243 4.243l-9.193 9.193a1.5 1.5 0 01-2.121-2.121l8.486-8.486" />
                </svg>
            </label>


            <input id="attachment" type="file" wire:model="attachment" accept="image/*,video/*" class="hidden" />

            @if($attachment)
                <div class="flex items-center gap-2 px-3 py-2 text-sm bg-gray-100 rounded-lg dark:bg-gray-800 min-w-60">
                    <span>
                        {{ $attachment->getMimeType() }} - {{ $attachment->getClientOriginalName() }}
                    </span>

                    <button type="button" wire:click="$set('attachment', null)" class="text-red-500">
                        ✕
                    </button>
                </div>
            @endif

            <textarea wire:model="message" class="flex-1 px-4 py-3 border rounded-xl bg-black/30" placeholder="Écrire un message..." rows="1"></textarea>

            <button type="submit" class="px-5 py-3 text-white bg-[#BF296D] rounded-xl">
                Envoyer
            </button>

        </form>

    </div>

    <script>
        // récuperer la hauter de l'écran
        const screenHeight = window.innerHeight;
        const container = document.getElementById('chat-container');

        container.classList.add(`h-[${screenHeight-200}px]`)
        // alert('La hauteur de l\'écran est : ' + screenHeight + 'px');
    </script>
</x-filament-panels::page>
