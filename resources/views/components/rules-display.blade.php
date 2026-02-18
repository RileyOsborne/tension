@props(['presentation' => false])

<div class="{{ $presentation ? 'text-center' : '' }}">
    @if($presentation)
        <h1 class="text-6xl font-black mb-8">
            <span class="text-red-500">TENSION TRIVIA</span>
        </h1>
    @endif

    <div class="grid md:grid-cols-3 gap-{{ $presentation ? '8' : '6' }} {{ $presentation ? 'text-left' : '' }}">
        <div class="{{ $presentation ? 'bg-slate-800/50 rounded-2xl p-6' : 'bg-green-900/30 rounded-lg p-4 border border-green-700' }}">
            <h3 class="{{ $presentation ? 'text-2xl' : 'text-lg' }} font-bold text-green-400 mb-{{ $presentation ? '4' : '2' }}">
                {{ $presentation ? 'Scoring' : 'Top 10 Answers' }}
            </h3>
            <p class="{{ $presentation ? 'text-xl' : '' }} text-slate-300">
                #1 = <strong>1 point</strong><br>
                #2 = <strong>2 points</strong><br>
                ...<br>
                #10 = <strong>10 points</strong>
            </p>
        </div>

        <div class="{{ $presentation ? 'bg-slate-800/50 rounded-2xl p-6' : 'bg-red-900/30 rounded-lg p-4 border border-red-700' }}">
            <h3 class="{{ $presentation ? 'text-2xl' : 'text-lg' }} font-bold text-red-400 mb-{{ $presentation ? '4' : '2' }}">
                {{ $presentation ? 'Tension!' : 'Tension Answers' }}
            </h3>
            <p class="{{ $presentation ? 'text-xl' : '' }} text-slate-300">
                Answers <strong>#11-15</strong><br>
                <span class="text-red-400 font-bold">-5 points each!</span>
            </p>
            <p class="{{ $presentation ? 'text-lg mt-2' : 'mt-2' }} text-slate-400">
                Not on list = <span class="{{ $presentation ? '' : 'text-yellow-400 font-bold' }}">-3 pts</span>
            </p>
        </div>

        <div class="{{ $presentation ? 'bg-slate-800/50 rounded-2xl p-6' : 'bg-slate-700/50 rounded-lg p-4 border border-slate-600' }}">
            <h3 class="{{ $presentation ? 'text-2xl' : 'text-lg' }} font-bold text-yellow-400 mb-{{ $presentation ? '4' : '2' }}">Double</h3>
            <p class="{{ $presentation ? 'text-xl' : '' }} text-slate-300">
                Each player can double <strong>ONE</strong> answer per game
            </p>
        </div>
    </div>

    <p class="{{ $presentation ? 'text-3xl mt-12' : 'text-xl mt-6 text-center' }} text-slate-400">
        Name items closer to <span class="text-green-400 font-bold">#10</span> for more points!
    </p>
</div>
