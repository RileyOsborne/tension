<?php

use App\Models\Category;
use Livewire\Volt\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;

new #[Layout('components.layouts.app')] #[Title('Categories')] class extends Component {
    use WithPagination;

    public string $search = '';

    public function with(): array
    {
        return [
            'categories' => Category::query()
                ->when($this->search, fn($q) => $q->where('title', 'like', "%{$this->search}%"))
                ->withCount('answers')
                ->latest()
                ->paginate(12),
        ];
    }

    public function deleteCategory(Category $category): void
    {
        $category->delete();
        session()->flash('message', 'Category deleted successfully.');
    }
}; ?>

<div>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Categories</h1>
            <a href="{{ route('categories.create') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-semibold transition">
                + New Category
            </a>
        </div>

        @if (session('message'))
            <div class="bg-green-600/20 border border-green-500 text-green-400 px-4 py-3 rounded-lg mb-6">
                {{ session('message') }}
            </div>
        @endif

        <!-- Search -->
        <div class="mb-6">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Search categories..."
                   class="w-full max-w-md bg-slate-800 border border-slate-700 rounded-lg px-4 py-3 text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        @if($categories->isEmpty())
            <div class="bg-slate-800 rounded-xl p-12 text-center">
                <p class="text-slate-400 text-lg mb-4">No categories yet.</p>
                <a href="{{ route('categories.create') }}" class="text-blue-400 hover:text-blue-300">
                    Create your first category
                </a>
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($categories as $category)
                    <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 hover:border-slate-600 transition">
                        <div class="flex justify-between items-start mb-4">
                            <h2 class="text-xl font-semibold">{{ $category->title }}</h2>
                            <span class="text-sm px-2 py-1 rounded {{ $category->answers_count >= 10 ? 'bg-green-600/20 text-green-400' : 'bg-yellow-600/20 text-yellow-400' }}">
                                {{ $category->answers_count }}/10+ answers
                            </span>
                        </div>

                        @if($category->description)
                            <p class="text-slate-400 text-sm mb-4 line-clamp-2">{{ $category->description }}</p>
                        @endif

                        <div class="flex justify-between items-center mt-4 pt-4 border-t border-slate-700">
                            <a href="{{ route('categories.edit', $category) }}"
                               class="text-blue-400 hover:text-blue-300 font-medium">
                                Edit
                            </a>
                            <button wire:click="deleteCategory('{{ $category->id }}')"
                                    wire:confirm="Are you sure you want to delete this category?"
                                    class="text-red-400 hover:text-red-300 font-medium">
                                Delete
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $categories->links() }}
            </div>
        @endif
    </div>
</div>
