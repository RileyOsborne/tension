<?php

use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;

new #[Layout('components.layouts.app')] class extends Component {
    public Category $category;

    public function mount(Category $category): void
    {
        $this->category = $category->load(['topic', 'answers']);
    }

    public function getTitle(): string
    {
        return $this->category->title;
    }
}; ?>

<div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8 flex justify-between items-start">
            <div>
                <a href="{{ route('categories.index') }}" class="text-slate-400 hover:text-white transition">
                    &larr; Back to Categories
                </a>
                <h1 class="text-3xl font-bold mt-4">{{ $category->title }}</h1>
                <div class="flex items-center gap-2 mt-2">
                    @if($category->topic)
                        <span class="px-3 py-1 bg-slate-700 rounded-full text-sm text-slate-300">
                            {{ $category->topic->name }}
                        </span>
                    @endif
                    <span class="px-3 py-1 rounded-full text-sm {{ $category->played_at ? 'bg-slate-600 text-slate-400' : 'bg-green-600/20 text-green-400' }}">
                        {{ $category->played_at ? 'Played' : 'New' }}
                    </span>
                </div>
                @if($category->description)
                    <p class="text-slate-400 mt-3">{{ $category->description }}</p>
                @endif
            </div>
            <a href="{{ route('categories.edit', $category) }}"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded-lg font-medium transition">
                Edit
            </a>
        </div>

        <!-- Top 10 Answers -->
        <div class="bg-slate-800 rounded-xl p-6 border border-green-700/50 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-green-400">Top 10 Answers</h2>

            <div class="space-y-2">
                @foreach($category->answers->filter(fn($a) => $a->position <= 10) as $answer)
                    <div class="flex items-center gap-4 py-2 px-3 rounded-lg bg-slate-900/50">
                        <span class="w-8 text-right font-bold text-green-400">#{{ $answer->position }}</span>
                        <span class="flex-1 text-white font-medium">{{ $answer->text }}</span>
                        @if($answer->stat)
                            <span class="text-slate-400 text-sm">{{ $answer->stat }}</span>
                        @endif
                        <span class="text-sm text-green-400 w-12 text-right">+{{ $answer->position }}pt</span>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Tension Answers -->
        @php
            $tensionAnswers = $category->answers->filter(fn($a) => $a->position > 10);
        @endphp
        @if($tensionAnswers->isNotEmpty())
            <div class="bg-slate-800 rounded-xl p-6 border border-red-700/50">
                <h2 class="text-xl font-semibold mb-4 text-red-400">Tension Answers</h2>

                <div class="space-y-2">
                    @foreach($tensionAnswers as $answer)
                        <div class="flex items-center gap-4 py-2 px-3 rounded-lg bg-slate-900/50">
                            <span class="w-8 text-right font-bold text-red-400">#{{ $answer->position }}</span>
                            <span class="flex-1 text-white font-medium">{{ $answer->text }}</span>
                            @if($answer->stat)
                                <span class="text-slate-400 text-sm">{{ $answer->stat }}</span>
                            @endif
                            <span class="text-sm text-red-400 w-12 text-right">-5pt</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
