<?php

use App\Models\Category;
use App\Models\Answer;
use Livewire\Volt\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Edit Category')] class extends Component {
    public Category $category;
    public string $title = '';
    public string $description = '';
    public array $answers = [];
    public array $answerStats = [];
    public string $bulkAnswers = '';

    public function mount(Category $category): void
    {
        $this->category = $category;
        $this->title = $category->title;
        $this->description = $category->description ?? '';

        // Initialize 15 answer slots
        for ($i = 1; $i <= 15; $i++) {
            $this->answers[$i] = '';
            $this->answerStats[$i] = '';
        }

        // Fill in existing answers
        foreach ($category->answers as $answer) {
            $this->answers[$answer->position] = $answer->text;
            $this->answerStats[$answer->position] = $answer->stat ?? '';
        }
    }

    public function parseBulkAnswers(): void
    {
        $lines = array_filter(array_map('trim', explode("\n", $this->bulkAnswers)));
        $position = 1;

        foreach ($lines as $line) {
            if ($position > 15) break;
            // Remove common list prefixes like "1.", "1)", "- ", etc.
            $clean = preg_replace('/^[\d]+[\.\)\-]\s*/', '', $line);
            $clean = preg_replace('/^[\-\*\•]\s*/', '', $clean);

            // Parse stat from format "Answer: stat" - uses last colon so "Avengers: Endgame: $2.8B" works
            if (preg_match('/^(.+)\s*[:]\s*([^:]+)$/', trim($clean), $matches)) {
                $this->answers[$position] = trim($matches[1]);
                $this->answerStats[$position] = trim($matches[2]);
            } else {
                $this->answers[$position] = trim($clean);
                $this->answerStats[$position] = '';
            }
            $position++;
        }

        $this->bulkAnswers = '';
    }

    public function moveAnswerUp(int $position): void
    {
        if ($position <= 1) return;

        $temp = $this->answers[$position - 1];
        $this->answers[$position - 1] = $this->answers[$position];
        $this->answers[$position] = $temp;

        $tempStat = $this->answerStats[$position - 1];
        $this->answerStats[$position - 1] = $this->answerStats[$position];
        $this->answerStats[$position] = $tempStat;
    }

    public function moveAnswerDown(int $position): void
    {
        if ($position >= 15) return;

        $temp = $this->answers[$position + 1];
        $this->answers[$position + 1] = $this->answers[$position];
        $this->answers[$position] = $temp;

        $tempStat = $this->answerStats[$position + 1];
        $this->answerStats[$position + 1] = $this->answerStats[$position];
        $this->answerStats[$position] = $tempStat;
    }

    public function save(): void
    {
        $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'answers.1' => 'required|string|max:255',
            'answers.2' => 'required|string|max:255',
            'answers.3' => 'required|string|max:255',
            'answers.4' => 'required|string|max:255',
            'answers.5' => 'required|string|max:255',
            'answers.6' => 'required|string|max:255',
            'answers.7' => 'required|string|max:255',
            'answers.8' => 'required|string|max:255',
            'answers.9' => 'required|string|max:255',
            'answers.10' => 'required|string|max:255',
        ], [
            'answers.*.required' => 'Answers 1-10 are required for the Top 10.',
        ]);

        $this->category->update([
            'title' => $this->title,
            'description' => $this->description ?: null,
        ]);

        // Delete existing answers and recreate
        $this->category->answers()->delete();

        foreach ($this->answers as $position => $text) {
            if (!empty(trim($text))) {
                Answer::create([
                    'category_id' => $this->category->id,
                    'text' => trim($text),
                    'stat' => !empty(trim($this->answerStats[$position] ?? '')) ? trim($this->answerStats[$position]) : null,
                    'position' => $position,
                ]);
            }
        }

        session()->flash('message', 'Category updated successfully!');
        $this->redirect(route('categories.index'), navigate: true);
    }
}; ?>

<div>
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <a href="{{ route('categories.index') }}" class="text-slate-400 hover:text-white transition">
                &larr; Back to Categories
            </a>
            <h1 class="text-3xl font-bold mt-4">Edit Category</h1>
        </div>

        <form wire:submit="save" class="space-y-8">
            <!-- Category Info -->
            <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                <h2 class="text-xl font-semibold mb-4">Category Details</h2>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Title</label>
                        <input type="text"
                               wire:model="title"
                               placeholder="Top 10 Countries By Population"
                               class="w-full bg-slate-900 border border-slate-600 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @error('title') <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-slate-300 mb-2">Description / Source (optional)</label>
                        <textarea wire:model="description"
                                  rows="2"
                                  placeholder="Based on 2024 World Population Review data..."
                                  class="w-full bg-slate-900 border border-slate-600 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                    </div>
                </div>
            </div>

            <!-- Quick Import -->
            <div class="bg-slate-800 rounded-xl p-6 border border-slate-700">
                <h2 class="text-xl font-semibold mb-2">Quick Import</h2>
                <p class="text-slate-400 text-sm mb-4">Paste a list (one per line). Use "Answer: stat" format to include stats.</p>

                <div class="flex gap-3">
                    <textarea wire:model="bulkAnswers"
                              rows="4"
                              placeholder="1. China: 1.4 billion&#10;2. India: 1.4 billion&#10;3. United States: 334 million&#10;..."
                              class="flex-1 bg-slate-900 border border-slate-600 rounded-lg px-4 py-3 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
                    <button type="button"
                            wire:click="parseBulkAnswers"
                            class="px-6 py-3 bg-slate-700 hover:bg-slate-600 rounded-lg font-semibold transition self-end">
                        Import
                    </button>
                </div>
            </div>

            <!-- Top 10 Answers -->
            <div class="bg-slate-800 rounded-xl p-6 border border-green-700/50">
                <h2 class="text-xl font-semibold mb-2 text-green-400">Top 10 Answers</h2>
                <p class="text-slate-400 text-sm mb-4">These award positive points (1-10 points based on position)</p>

                <div class="space-y-3">
                    @for($i = 1; $i <= 10; $i++)
                        <div class="flex items-center gap-3">
                            <span class="w-8 text-right font-bold text-green-400">#{{ $i }}</span>
                            <input type="text"
                                   wire:model="answers.{{ $i }}"
                                   placeholder="Answer {{ $i }}"
                                   class="flex-1 bg-slate-900 border border-slate-600 rounded-lg px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-green-500">
                            <input type="text"
                                   wire:model="answerStats.{{ $i }}"
                                   placeholder="Stat (optional)"
                                   class="w-32 bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                            <span class="text-sm text-green-400 w-12">+{{ $i }}pt</span>
                            <button type="button" wire:click="moveAnswerUp({{ $i }})"
                                    class="p-1.5 text-slate-500 hover:text-white hover:bg-slate-700 rounded transition {{ $i === 1 ? 'opacity-30 cursor-not-allowed' : '' }}"
                                    {{ $i === 1 ? 'disabled' : '' }}>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            </button>
                            <button type="button" wire:click="moveAnswerDown({{ $i }})"
                                    class="p-1.5 text-slate-500 hover:text-white hover:bg-slate-700 rounded transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                        @error("answers.{$i}") <span class="text-red-400 text-sm">{{ $message }}</span> @enderror
                    @endfor
                </div>
            </div>

            <!-- Tension Answers -->
            <div class="bg-slate-800 rounded-xl p-6 border border-red-700/50">
                <h2 class="text-xl font-semibold mb-2 text-red-400">Tension Answers (Optional)</h2>
                <p class="text-slate-400 text-sm mb-4">These deduct 5 points each. Add 0-5 tension answers.</p>

                <div class="space-y-3">
                    @for($i = 11; $i <= 15; $i++)
                        <div class="flex items-center gap-3">
                            <span class="w-8 text-right font-bold text-red-400">#{{ $i }}</span>
                            <input type="text"
                                   wire:model="answers.{{ $i }}"
                                   placeholder="Tension {{ $i - 10 }} (optional)"
                                   class="flex-1 bg-slate-900 border border-slate-600 rounded-lg px-4 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-red-500">
                            <input type="text"
                                   wire:model="answerStats.{{ $i }}"
                                   placeholder="Stat (optional)"
                                   class="w-32 bg-slate-900 border border-slate-600 rounded-lg px-3 py-2 text-white placeholder-slate-500 focus:outline-none focus:ring-2 focus:ring-red-500 text-sm">
                            <span class="text-sm text-red-400 w-12">-5pt</span>
                            <button type="button" wire:click="moveAnswerUp({{ $i }})"
                                    class="p-1.5 text-slate-500 hover:text-white hover:bg-slate-700 rounded transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            </button>
                            <button type="button" wire:click="moveAnswerDown({{ $i }})"
                                    class="p-1.5 text-slate-500 hover:text-white hover:bg-slate-700 rounded transition {{ $i === 15 ? 'opacity-30 cursor-not-allowed' : '' }}"
                                    {{ $i === 15 ? 'disabled' : '' }}>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                    @endfor
                </div>
            </div>

            <!-- Submit -->
            <div class="flex justify-end gap-4">
                <a href="{{ route('categories.index') }}"
                   class="px-6 py-3 bg-slate-700 hover:bg-slate-600 rounded-lg font-semibold transition">
                    Cancel
                </a>
                <button type="submit"
                        class="px-8 py-3 bg-blue-600 hover:bg-blue-700 rounded-lg font-semibold transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>
</div>
