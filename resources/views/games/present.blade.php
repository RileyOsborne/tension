<x-layouts.presentation title="Presentation">
    <div id="presentation-container" class="min-h-screen flex items-center justify-center">
        <!-- Rules Slide -->
        <div id="slide-rules" class="hidden w-full max-w-5xl mx-auto px-8">
            <div class="animate-fade-in">
                <h1 class="text-5xl font-bold mb-8 text-center">How to Play <span class="text-red-500">TENSION TRIVIA</span></h1>

                <!-- The Goal -->
                <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 mb-8">
                    <h2 class="text-2xl font-semibold mb-4">The Goal</h2>
                    <p class="text-slate-300 text-xl">
                        Players try to name items from a <strong>Top 10</strong> list. The twist? You want to name items
                        <strong class="text-green-400">closer to #10</strong> than #1, because higher positions score more points!
                    </p>
                </div>

                <!-- Scoring Rules -->
                <div class="bg-slate-800 rounded-xl p-6 border border-slate-700 mb-8">
                    <h2 class="text-2xl font-semibold mb-4">Scoring</h2>
                    <x-rules-display />
                </div>

                <!-- Example -->
                <div class="bg-slate-800 rounded-xl p-6 border border-blue-700/50">
                    <h2 class="text-2xl font-semibold mb-4 text-blue-400">Example Round</h2>
                    <p class="text-slate-300 text-lg mb-4">
                        <strong>Category:</strong> "Top 10 Countries by Aerospace Parts Development"
                    </p>
                    <div class="space-y-2 text-slate-400 text-lg">
                        <p>Player A guesses "United States" &rarr; #1 = <span class="text-green-400">+1 point</span></p>
                        <p>Player B guesses "France" &rarr; #5 = <span class="text-green-400">+5 points</span></p>
                        <p>Player C guesses "South Korea" &rarr; #10 = <span class="text-green-400">+10 points!</span></p>
                        <p>Player D guesses "Brazil" &rarr; #12 (Tension!) = <span class="text-red-400">-5 points</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Intro Slide -->
        <div id="slide-intro" class="hidden w-full max-w-4xl mx-auto px-8 text-center">
            <div class="animate-scale-in">
                <p id="round-number" class="text-2xl text-slate-400 mb-4">Round 1</p>
                <h1 id="category-title" class="text-6xl md:text-7xl font-black mb-6">Category Title</h1>
                <p id="category-description" class="text-2xl text-slate-400 max-w-2xl mx-auto"></p>
            </div>
        </div>

        <!-- Collecting Slide (players are giving answers) -->
        <div id="slide-collecting" class="hidden w-full max-w-5xl mx-auto px-8">
            <div class="animate-fade-in">
                <div class="text-center mb-8">
                    <p class="text-2xl text-slate-400 mb-4">Round <span id="collecting-round">1</span></p>
                    <h1 id="collecting-category" class="text-5xl md:text-6xl font-black mb-4">Category Title</h1>

                    <!-- Current Turn Display with Timer -->
                    <div id="current-turn-display" class="hidden my-6">
                        <!-- Countdown Timer (first player - 30 second thinking time) -->
                        <div id="countdown-timer" class="hidden">
                            <div class="inline-flex items-center gap-4 bg-yellow-900/30 border border-yellow-600 rounded-2xl px-8 py-4">
                                <span class="text-yellow-400 text-2xl">⏱️ Think!</span>
                                <span id="countdown-display" class="text-6xl font-mono font-black text-yellow-400">30</span>
                            </div>
                            <p class="text-slate-400 mt-4">
                                <span id="countdown-player-name" class="font-bold text-white">Player</span>
                                <span class="text-slate-400">answers first</span>
                            </p>
                        </div>

                        <!-- Countup Timer (subsequent players - social pressure) -->
                        <div id="countup-timer" class="hidden">
                            <p class="text-3xl mb-4">
                                <span id="countup-player-name" class="font-bold" style="color: white">Player</span>
                                <span class="text-slate-400">'s turn</span>
                            </p>
                            <div class="inline-flex items-center gap-3 bg-slate-800 border border-slate-600 rounded-xl px-6 py-3">
                                <span class="text-slate-400">Waiting:</span>
                                <span id="countup-display" class="text-4xl font-mono font-bold text-red-400">0s</span>
                            </div>
                        </div>

                        <!-- All Answered -->
                        <div id="all-answered" class="hidden">
                            <p class="text-3xl text-green-400 font-bold">All answers collected!</p>
                        </div>
                    </div>

                    <!-- Turn Order -->
                    <div id="turn-order" class="hidden my-6">
                        <p class="text-slate-400 mb-3">Answer Order</p>
                        <div id="turn-order-players" class="flex flex-wrap justify-center gap-3">
                            <!-- Populated by JS -->
                        </div>
                    </div>

                    <p id="collecting-prompt" class="text-2xl text-blue-400">Give your answers now!</p>
                </div>

                <!-- Collected Answers Grid -->
                <div id="collected-answers" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-8">
                    <!-- Populated by JS -->
                </div>
            </div>
        </div>

        <!-- Reveal Slide (single page, answers appear one by one) -->
        <div id="slide-reveal" class="hidden w-full max-w-6xl mx-auto px-8">
            <div class="animate-fade-in">
                <div class="text-center mb-8">
                    <p class="text-xl text-slate-400">Round <span id="reveal-round">1</span></p>
                    <h2 id="reveal-category" class="text-4xl font-bold">Category Title</h2>
                </div>

                <!-- Top 10 Grid -->
                <div id="reveal-top10" class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                    <!-- Populated by JS -->
                </div>

                <!-- Tension Zone -->
                <div id="tension-zone" class="hidden">
                    <h3 class="text-2xl font-bold text-red-500 text-center mb-4">TENSION ZONE</h3>
                    <div id="reveal-tension" class="grid grid-cols-5 gap-4">
                        <!-- Populated by JS -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Scores Slide -->
        <div id="slide-scores" class="hidden w-full max-w-4xl mx-auto px-8">
            <div class="animate-fade-in text-center">
                <h2 id="scores-title" class="text-4xl font-bold mb-8 text-slate-400">Round 1 Scores</h2>
                <div id="scores-container" class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <!-- Scores populated by JS -->
                </div>
            </div>
        </div>

        <!-- Game Over Slide -->
        <div id="slide-gameover" class="hidden w-full max-w-4xl mx-auto px-8 text-center">
            <div class="animate-scale-in">
                <h1 class="text-6xl font-black mb-12">Final Scores</h1>
                <div id="final-scores-container" class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <!-- Final scores populated by JS -->
                </div>
            </div>
        </div>

        <!-- Lobby Slide (players joining before game starts) -->
        <div id="slide-lobby" class="hidden w-full max-w-5xl mx-auto px-8">
            <div class="animate-fade-in">
                <!-- Title -->
                <div class="text-center mb-8">
                    <h1 class="text-6xl font-black mb-2">
                        <span class="text-white">TEN</span><span class="text-red-500">SION</span>
                    </h1>
                    <p class="text-2xl text-slate-400">Join the game!</p>
                </div>

                <div class="grid md:grid-cols-2 gap-8">
                    <!-- Join Info -->
                    <div class="text-center">
                        <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700">
                            @php
                                // Get the actual host from the request (works with both localhost and network IP)
                                $baseUrl = request()->getSchemeAndHttpHost();
                                $joinUrl = $baseUrl . '/join';
                                $fullJoinUrl = $game->join_code ? $baseUrl . '/join/' . $game->join_code : null;
                            @endphp

                            <p class="text-slate-400 mb-2">Go to</p>
                            <p class="text-3xl text-blue-400 font-bold mb-6" id="lobby-join-url">{{ $joinUrl }}</p>

                            <p class="text-slate-400 mb-2">Enter code</p>
                            <p class="text-6xl font-mono font-black tracking-[0.2em] text-white mb-6" id="lobby-join-code">{{ $game->join_code ?? '------' }}</p>

                            <p class="text-slate-500 text-sm mb-4">- or scan -</p>

                            <div class="flex justify-center">
                                <div class="bg-white p-4 rounded-xl">
                                    @if($fullJoinUrl)
                                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=180x180&data={{ urlencode($fullJoinUrl) }}"
                                             alt="QR Code"
                                             class="w-44 h-44"
                                             id="lobby-qr-code">
                                    @else
                                        <div class="w-44 h-44 flex items-center justify-center text-slate-400" id="lobby-qr-placeholder">
                                            QR Code
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Players List -->
                    <div>
                        <div class="bg-slate-800 rounded-2xl p-8 border border-slate-700 h-full">
                            @php
                                $lobbyActivePlayers = $game->players->filter(fn($p) => $p->isActive());
                                $lobbyConnectedCount = $lobbyActivePlayers->filter(fn($p) => $p->isConnected())->count();
                            @endphp
                            <h2 class="text-2xl font-bold mb-6 flex items-center gap-3">
                                <span>Players</span>
                                <span class="text-lg font-normal text-slate-400" id="lobby-player-count">({{ $lobbyConnectedCount }}/{{ $lobbyActivePlayers->count() }})</span>
                            </h2>

                            <div id="lobby-players" class="space-y-3">
                                @php
                                    $activePlayers = $game->players->filter(fn($p) => $p->isActive())->sortBy('position');
                                @endphp
                                @forelse($activePlayers as $player)
                                    <div class="flex items-center gap-4 bg-slate-900 rounded-xl px-5 py-4" data-player-id="{{ $player->id }}">
                                        <div class="w-5 h-5 rounded-full" style="background-color: {{ $player->color }}"></div>
                                        <span class="text-2xl font-bold">{{ $player->name }}</span>
                                        <span class="ml-auto text-sm player-status">
                                            <span class="text-green-400">{{ $player->isGmCreated() ? 'GM' : 'Connected' }}</span>
                                        </span>
                                    </div>
                                @empty
                                    <p class="text-slate-500 text-center py-8">Waiting for players to join...</p>
                                @endforelse
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-center text-slate-500 mt-8 text-xl">Game Master will start when everyone's ready</p>
            </div>
        </div>
    </div>

    <!-- Scoreboard Overlay (bottom) - hidden during reveal, dynamically populated -->
    <div id="scoreboard-overlay" class="hidden fixed bottom-0 left-0 right-0 bg-slate-900/90 border-t border-slate-700 py-4 px-8">
        <div id="scoreboard-players" class="flex justify-center gap-8">
            <!-- Dynamically populated by JavaScript -->
        </div>
    </div>

    @php
        $categories = [];
        foreach ($game->rounds as $round) {
            $categories[$round->round_number] = [
                'title' => $round->category->title,
                'description' => $round->category->description,
                'answers' => $round->category->answers->sortBy('position')->map(fn($a) => [
                    'position' => $a->position,
                    'text' => $a->display_text, // Use display_text to hide geographic identifiers
                    'stat' => $a->stat,
                    'points' => $a->points,
                    'is_tension' => $a->is_tension,
                ])->values()->toArray(),
            ];
        }

        // Get current round for initial state
        $currentRound = $game->rounds->where('round_number', $game->current_round)->first();

        // Build initial state - use database values for ephemeral state
        $initialState = [
            'gameId' => $game->id,
            'showRules' => (bool) $game->show_rules,
            'currentRound' => $game->current_round,
            'roundStatus' => $currentRound?->status,
            'currentSlide' => $currentRound?->current_slide ?? 0,
            'gameStatus' => $game->status,
            'joinCode' => $game->join_code,
            'playerCount' => $game->player_count,
            'revealedAnswers' => [],
            'collectedAnswers' => [],
            'turnOrder' => [],
            'timerRunning' => (bool) $game->timer_running,
            'timerStartedAt' => $game->timer_started_at?->timestamp,
            'thinkingTime' => $game->thinking_time,
            'currentTurnPlayerId' => null,
            'currentTurnPlayerName' => null,
            'currentTurnPlayerColor' => null,
            'currentTurnIndex' => null,
            'timerMode' => null, // 'countdown' or 'countup'
            'allAnswered' => false,
            'categoryTitle' => $currentRound?->category->title,
            'categoryDescription' => $currentRound?->category->description,
            'players' => $game->players
                ->filter(fn($p) => $p->isActive())
                ->map(fn($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'color' => $p->color,
                    'total_score' => $p->total_score,
                    'double_used' => $p->double_used,
                    'is_connected' => $p->isConnected(),
                    'is_gm_created' => $p->isGmCreated(),
                    'is_gm_controlled' => $p->isGmControlled(),
                ])->values()->toArray(),
        ];
    @endphp

    <script>
        const gameId = '{{ $game->id }}';
        const categories = @json($categories);
        const totalRounds = {{ $game->total_rounds }};
        const initialState = @json($initialState);
        const players = @json($game->players->keyBy('id'));

        // Initialize BroadcastChannel
        const channel = new BroadcastChannel('tension-game-' + gameId);

        // DOM elements
        const slides = {
            lobby: document.getElementById('slide-lobby'),
            rules: document.getElementById('slide-rules'),
            intro: document.getElementById('slide-intro'),
            collecting: document.getElementById('slide-collecting'),
            reveal: document.getElementById('slide-reveal'),
            scores: document.getElementById('slide-scores'),
            gameover: document.getElementById('slide-gameover'),
        };

        const scoreboardOverlay = document.getElementById('scoreboard-overlay');

        function hideAllSlides() {
            Object.values(slides).forEach(function(slide) {
                slide.classList.add('hidden');
            });
        }

        var currentSlideName = null;

        function showSlide(name) {
            // Only change if actually switching slides
            if (currentSlideName === name) return;

            hideAllSlides();
            if (slides[name]) {
                slides[name].classList.remove('hidden');
                // Only trigger animation when actually switching slides
                var animated = slides[name].querySelector('.animate-slide-in, .animate-fade-in, .animate-scale-in');
                if (animated) {
                    animated.style.animation = 'none';
                    animated.offsetHeight; // Trigger reflow
                    animated.style.animation = null;
                }
            }
            currentSlideName = name;
        }

        function updateScoreboard(playerList) {
            var container = document.getElementById('scoreboard-players');
            if (!container) return;

            // Sort players by score descending
            var sorted = playerList.slice().sort(function(a, b) { return b.total_score - a.total_score; });

            // Rebuild the scoreboard with only active players from state
            container.innerHTML = sorted.map(function(player) {
                return '<div class="text-center" data-player-id="' + player.id + '">' +
                    '<div class="text-lg font-bold" style="color: ' + player.color + '">' + player.name + '</div>' +
                    '<div class="text-3xl font-black player-score">' + player.total_score + '</div>' +
                '</div>';
            }).join('');
        }

        // Timer state
        var timerInterval = null;
        var currentTimerMode = null; // 'countdown' or 'countup'

        function renderTurnOrder(turnOrder, currentTurnPlayerId) {
            var container = document.getElementById('turn-order-players');
            if (!container || !turnOrder || turnOrder.length === 0) return;

            var html = turnOrder.map(function(player, index) {
                var isCurrent = player.id === currentTurnPlayerId;
                var classes = 'flex items-center gap-2 px-4 py-2 rounded-full ' +
                    (isCurrent ? 'bg-blue-600 border-2 border-blue-400 ring-2 ring-blue-400/50' : 'bg-slate-800 border border-slate-700');
                return '<div class="' + classes + '">' +
                    '<span class="font-bold ' + (isCurrent ? 'text-white' : 'text-slate-500') + '">' + (index + 1) + '.</span>' +
                    '<span style="color: ' + player.color + '">' + player.name + '</span>' +
                '</div>';
            }).join('');

            container.innerHTML = html;
        }

        function updateTurnDisplay(state) {
            var container = document.getElementById('current-turn-display');
            var countdownEl = document.getElementById('countdown-timer');
            var countupEl = document.getElementById('countup-timer');
            var allAnsweredEl = document.getElementById('all-answered');
            var promptEl = document.getElementById('collecting-prompt');

            if (!container) return;

            // Clear any existing timer interval
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }

            container.classList.remove('hidden');
            countdownEl.classList.add('hidden');
            countupEl.classList.add('hidden');
            allAnsweredEl.classList.add('hidden');
            if (promptEl) promptEl.classList.add('hidden');

            if (state.allAnswered) {
                // All players have answered
                allAnsweredEl.classList.remove('hidden');
                currentTimerMode = null;
                return;
            }

            if (state.timerMode === 'countdown' && state.timerRunning && state.timerStartedAt) {
                // First player - show countdown
                countdownEl.classList.remove('hidden');
                currentTimerMode = 'countdown';

                var nameEl = document.getElementById('countdown-player-name');
                var displayEl = document.getElementById('countdown-display');

                if (nameEl) {
                    nameEl.textContent = state.currentTurnPlayerName || 'Player';
                    nameEl.style.color = state.currentTurnPlayerColor || 'white';
                }

                // Calculate remaining time
                var now = Math.floor(Date.now() / 1000);
                var elapsed = now - state.timerStartedAt;
                var thinkingTime = state.thinkingTime || 30;
                var timerEndTime = state.timerStartedAt + thinkingTime;

                function updateCountdown() {
                    var nowSec = Math.floor(Date.now() / 1000);
                    var left = Math.max(0, timerEndTime - nowSec);
                    if (displayEl) {
                        displayEl.textContent = left;
                        if (left <= 5) {
                            displayEl.classList.remove('text-yellow-400');
                            displayEl.classList.add('text-red-400');
                        } else {
                            displayEl.classList.remove('text-red-400');
                            displayEl.classList.add('text-yellow-400');
                        }
                    }

                    if (left <= 0) {
                        clearInterval(timerInterval);
                        timerInterval = null;
                    }
                }

                updateCountdown();
                timerInterval = setInterval(updateCountdown, 1000);

            } else if (state.timerMode === 'countup' && state.timerRunning && state.timerStartedAt) {
                // Subsequent players - show countup (social pressure)
                countupEl.classList.remove('hidden');
                currentTimerMode = 'countup';

                var nameEl = document.getElementById('countup-player-name');
                var displayEl = document.getElementById('countup-display');

                if (nameEl) {
                    nameEl.textContent = state.currentTurnPlayerName || 'Player';
                    nameEl.style.color = state.currentTurnPlayerColor || 'white';
                }

                function updateCountup() {
                    var nowSec = Math.floor(Date.now() / 1000);
                    var elapsed = nowSec - state.timerStartedAt;
                    if (displayEl) {
                        displayEl.textContent = elapsed + 's';
                        // Change color as time increases
                        if (elapsed >= 20) {
                            displayEl.classList.remove('text-yellow-400');
                            displayEl.classList.add('text-red-400');
                        } else if (elapsed >= 10) {
                            displayEl.classList.remove('text-red-400');
                            displayEl.classList.add('text-yellow-400');
                        }
                    }
                }

                updateCountup();
                timerInterval = setInterval(updateCountup, 1000);

            } else if (state.currentTurnPlayerName) {
                // No timer but there's a current player
                countupEl.classList.remove('hidden');
                var nameEl = document.getElementById('countup-player-name');
                var displayEl = document.getElementById('countup-display');
                if (nameEl) {
                    nameEl.textContent = state.currentTurnPlayerName;
                    nameEl.style.color = state.currentTurnPlayerColor || 'white';
                }
                if (displayEl) displayEl.textContent = '0s';
            } else {
                // Fall back to prompt
                container.classList.add('hidden');
                if (promptEl) promptEl.classList.remove('hidden');
            }
        }

        function stopTimer() {
            if (timerInterval) {
                clearInterval(timerInterval);
                timerInterval = null;
            }
            currentTimerMode = null;
            var container = document.getElementById('current-turn-display');
            var promptEl = document.getElementById('collecting-prompt');
            if (container) container.classList.add('hidden');
            if (promptEl) promptEl.classList.remove('hidden');
        }

        function renderCollectedAnswers(collectedAnswers, allPlayers, turnOrder, currentTurnPlayerId) {
            var container = document.getElementById('collected-answers');
            if (!container) return;

            // Create a map of collected answers by player ID
            var answerMap = {};
            collectedAnswers.forEach(function(ca) {
                answerMap[ca.playerId] = ca;
            });

            // Use turn order if available, otherwise use allPlayers
            var orderedPlayers = turnOrder && turnOrder.length > 0 ? turnOrder : allPlayers;

            // Render all players, showing their answer if collected
            var html = orderedPlayers.map(function(player, index) {
                var collected = answerMap[player.id];
                var isCurrent = player.id === currentTurnPlayerId;
                var isGmControlled = player.is_gm_controlled && !player.is_gm_created;
                var gmBadge = isGmControlled ? '<span class="text-xs text-yellow-400 ml-1">(GM)</span>' : '';

                if (collected) {
                    return '<div class="bg-slate-800 rounded-xl p-4 text-center ring-2 ring-green-500/50">' +
                        '<div class="text-sm text-slate-500 mb-1">#' + (index + 1) + '</div>' +
                        '<div class="text-xl font-bold mb-2" style="color: ' + player.color + '">' + player.name + gmBadge + '</div>' +
                        '<div class="text-2xl font-bold text-white">' + collected.answerText + '</div>' +
                        '<div class="text-green-400 mt-1">✓</div>' +
                    '</div>';
                } else {
                    var borderClass = isCurrent ? 'ring-2 ring-blue-500 border-blue-500' : '';
                    return '<div class="bg-slate-800/50 rounded-xl p-4 text-center ' + (isCurrent ? '' : 'opacity-50') + ' ' + borderClass + '">' +
                        '<div class="text-sm text-slate-500 mb-1">#' + (index + 1) + '</div>' +
                        '<div class="text-xl font-bold mb-2" style="color: ' + player.color + '">' + player.name + gmBadge + '</div>' +
                        '<div class="text-2xl text-slate-500">' + (isCurrent ? '...' : '...') + '</div>' +
                    '</div>';
                }
            }).join('');

            container.innerHTML = html;
        }

        function renderRevealGrid(category, revealedAnswers, currentSlide) {
            // Build a map of revealed answers with their players
            var revealedMap = {};
            revealedAnswers.forEach(function(ra) {
                revealedMap[ra.position] = ra;
            });

            // Render top 10
            var top10Html = '';
            for (var i = 1; i <= 10; i++) {
                var answer = category.answers.find(function(a) { return a.position === i; });
                var revealed = revealedMap[i];
                var isRevealed = i <= currentSlide;

                if (answer) {
                    var playersHtml = '';
                    if (isRevealed && revealed && revealed.players && revealed.players.length > 0) {
                        playersHtml = '<div class="mt-2 flex flex-wrap gap-1 justify-center">';
                        revealed.players.forEach(function(p) {
                            playersHtml += '<span class="text-xs px-2 py-0.5 rounded-full" style="background-color: ' + p.color + '40; color: ' + p.color + '">' + p.name + (p.doubled ? ' 2x' : '') + '</span>';
                        });
                        playersHtml += '</div>';
                    }

                    top10Html += '<div class="bg-slate-800 rounded-xl p-4 text-center ' + (isRevealed ? '' : 'opacity-30') + '">';
                    top10Html += '<div class="text-green-400 font-bold text-lg">#' + i + '</div>';
                    if (isRevealed) {
                        top10Html += '<div class="text-xl font-bold mt-1">' + answer.text + '</div>';
                        if (answer.stat) {
                            top10Html += '<div class="text-sm text-slate-400">' + answer.stat + '</div>';
                        }
                        top10Html += '<div class="text-green-400 font-bold mt-1">+' + answer.points + '</div>';
                    } else {
                        top10Html += '<div class="text-3xl font-bold mt-2 text-slate-500">?</div>';
                    }
                    top10Html += playersHtml;
                    top10Html += '</div>';
                }
            }
            document.getElementById('reveal-top10').innerHTML = top10Html;

            // Render tension zone if there are tension answers
            var hasTension = category.answers.some(function(a) { return a.position > 10; });
            var tensionZone = document.getElementById('tension-zone');

            if (hasTension) {
                tensionZone.classList.remove('hidden');
                var tensionHtml = '';
                for (var i = 11; i <= 15; i++) {
                    var answer = category.answers.find(function(a) { return a.position === i; });
                    var revealed = revealedMap[i];
                    var isRevealed = i <= currentSlide;

                    if (answer) {
                        var playersHtml = '';
                        if (isRevealed && revealed && revealed.players && revealed.players.length > 0) {
                            playersHtml = '<div class="mt-2 flex flex-wrap gap-1 justify-center">';
                            revealed.players.forEach(function(p) {
                                playersHtml += '<span class="text-xs px-2 py-0.5 rounded-full" style="background-color: ' + p.color + '40; color: ' + p.color + '">' + p.name + (p.doubled ? ' 2x' : '') + '</span>';
                            });
                            playersHtml += '</div>';
                        }

                        tensionHtml += '<div class="bg-red-900/50 border border-red-500/50 rounded-xl p-4 text-center ' + (isRevealed ? '' : 'opacity-30') + '">';
                        tensionHtml += '<div class="text-red-400 font-bold text-lg">#' + i + '</div>';
                        if (isRevealed) {
                            tensionHtml += '<div class="text-xl font-bold mt-1">' + answer.text + '</div>';
                            if (answer.stat) {
                                tensionHtml += '<div class="text-sm text-slate-400">' + answer.stat + '</div>';
                            }
                            tensionHtml += '<div class="text-red-400 font-bold mt-1">' + answer.points + '</div>';
                        } else {
                            tensionHtml += '<div class="text-3xl font-bold mt-2 text-slate-500">?</div>';
                        }
                        tensionHtml += playersHtml;
                        tensionHtml += '</div>';
                    }
                }
                document.getElementById('reveal-tension').innerHTML = tensionHtml;
            } else {
                tensionZone.classList.add('hidden');
            }
        }

        function renderScores(playerList, title) {
            document.getElementById('scores-title').textContent = title;
            var container = document.getElementById('scores-container');
            var sorted = playerList.slice().sort(function(a, b) { return b.total_score - a.total_score; });
            container.innerHTML = sorted.map(function(p, i) {
                return '<div class="bg-slate-800 rounded-2xl p-6 ' + (i === 0 ? 'ring-4 ring-yellow-500' : '') + '">' +
                    '<div class="text-2xl font-bold" style="color: ' + p.color + '">' +
                        (i === 0 ? '🏆 ' : '') + p.name +
                    '</div>' +
                    '<div class="text-5xl font-black mt-2">' + p.total_score + '</div>' +
                '</div>';
            }).join('');
        }

        function renderFinalScores(playerList) {
            var container = document.getElementById('final-scores-container');
            var sorted = playerList.slice().sort(function(a, b) { return b.total_score - a.total_score; });
            container.innerHTML = sorted.map(function(p, i) {
                var place = i === 0 ? '1st' : i === 1 ? '2nd' : i === 2 ? '3rd' : (i+1)+'th';
                return '<div class="bg-slate-800 rounded-2xl p-8 ' + (i === 0 ? 'ring-4 ring-yellow-500' : '') + '">' +
                    '<div class="text-3xl mb-2">' + place + '</div>' +
                    '<div class="text-2xl font-bold" style="color: ' + p.color + '">' + p.name + '</div>' +
                    '<div class="text-6xl font-black mt-2">' + p.total_score + '</div>' +
                '</div>';
            }).join('');
        }

        function renderLobbyPlayers(playerList, playerCount) {
            var container = document.getElementById('lobby-players');
            var countEl = document.getElementById('lobby-player-count');
            if (!container) return;

            // Count connected players
            var connectedCount = playerList.filter(function(p) { return p.is_connected; }).length;

            if (countEl) {
                countEl.textContent = '(' + connectedCount + '/' + playerList.length + ')';
            }

            if (playerList.length === 0) {
                container.innerHTML = '<p class="text-slate-500 text-center py-8">Waiting for players to join...</p>';
                return;
            }

            container.innerHTML = playerList.map(function(p) {
                var statusText;
                if (p.is_gm_created) {
                    statusText = '<span class="text-green-400 text-sm">GM</span>';
                } else if (p.is_connected) {
                    statusText = '<span class="text-green-400 text-sm">Connected</span>';
                } else {
                    statusText = '<span class="text-yellow-400 text-sm">Disconnected (GM)</span>';
                }
                var opacityClass = p.is_connected || p.is_gm_created ? '' : 'opacity-60';
                return '<div class="flex items-center gap-4 bg-slate-900 rounded-xl px-5 py-4 ' + opacityClass + '">' +
                    '<div class="w-5 h-5 rounded-full" style="background-color: ' + p.color + '"></div>' +
                    '<span class="text-2xl font-bold">' + p.name + '</span>' +
                    '<span class="ml-auto">' + statusText + '</span>' +
                '</div>';
            }).join('');
        }

        function handleStateUpdate(state) {
            console.log('=== STATE UPDATE ===', state);

            // Handle game not started yet (draft or ready status) - show lobby
            if (state.gameStatus === 'draft' || state.gameStatus === 'ready') {
                renderLobbyPlayers(state.players, state.playerCount || 4);
                showSlide('lobby');
                scoreboardOverlay.classList.add('hidden');
                return;
            }

            // Handle game over
            if (state.gameStatus === 'completed') {
                showSlide('gameover');
                renderFinalScores(state.players);
                scoreboardOverlay.classList.add('hidden');
                return;
            }

            // Show rules
            if (state.showRules) {
                showSlide('rules');
                scoreboardOverlay.classList.add('hidden');
                return;
            }

            var category = categories[state.currentRound];
            if (!category) {
                console.log('No category found for round', state.currentRound, '- showing lobby');
                showSlide('lobby');
                scoreboardOverlay.classList.add('hidden');
                return;
            }

            // Handle different phases
            switch (state.roundStatus) {
                case 'intro':
                    document.getElementById('round-number').textContent = 'Round ' + state.currentRound;
                    document.getElementById('category-title').textContent = category.title;
                    document.getElementById('category-description').textContent = category.description || '';
                    showSlide('intro');
                    scoreboardOverlay.classList.add('hidden');
                    break;

                case 'collecting':
                    document.getElementById('collecting-round').textContent = state.currentRound;
                    document.getElementById('collecting-category').textContent = category.title;

                    // Show turn order if available
                    var turnOrderEl = document.getElementById('turn-order');
                    if (state.turnOrder && state.turnOrder.length > 0) {
                        turnOrderEl.classList.remove('hidden');
                        renderTurnOrder(state.turnOrder, state.currentTurnPlayerId);
                    } else {
                        turnOrderEl.classList.add('hidden');
                    }

                    // Handle turn-based timer (countdown for first, countup for others)
                    updateTurnDisplay(state);

                    renderCollectedAnswers(state.collectedAnswers || [], state.players, state.turnOrder, state.currentTurnPlayerId);
                    showSlide('collecting');
                    scoreboardOverlay.classList.add('hidden');
                    break;

                case 'revealing':
                case 'tension':
                    document.getElementById('reveal-round').textContent = state.currentRound;
                    document.getElementById('reveal-category').textContent = category.title;
                    renderRevealGrid(category, state.revealedAnswers || [], state.currentSlide);
                    showSlide('reveal');
                    scoreboardOverlay.classList.add('hidden'); // Hide scores during reveal
                    break;

                case 'scoring':
                    showSlide('scores');
                    updateScoreboard(state.players);
                    renderScores(state.players, 'Round ' + state.currentRound + ' Complete');
                    scoreboardOverlay.classList.remove('hidden');
                    break;

                default:
                    console.log('Unknown status:', state.roundStatus);
            }
        }

        var isGameActive = initialState.gameStatus === 'playing';
        var pollingInterval = null;

        // Listen for updates from control tab
        channel.onmessage = function(event) {
            console.log('[Present] RECEIVED MESSAGE FROM CHANNEL:', event.data);
            if (event.data && event.data.type === 'request-state') return;

            // Handle reset - reload the page to get fresh state
            if (event.data && event.data.type === 'reset') {
                console.log('[Present] GAME RESET - Reloading page');
                window.location.reload();
                return;
            }

            // Stop polling once game becomes active
            if (event.data && event.data.gameStatus === 'playing' && !isGameActive) {
                isGameActive = true;
                if (pollingInterval) {
                    console.log('[Present] Game started, stopping polling');
                    clearInterval(pollingInterval);
                    pollingInterval = null;
                }
            }

            handleStateUpdate(event.data);
        };

        console.log('[Present] BroadcastChannel initialized for game:', gameId);

        // Always start with lobby view - control panel will push actual state if game is active
        var lobbyState = {
            gameId: initialState.gameId,
            gameStatus: 'draft', // Force lobby view
            joinCode: initialState.joinCode,
            playerCount: initialState.playerCount,
            players: initialState.players
        };
        console.log('[Present] Starting with lobby state:', lobbyState);
        handleStateUpdate(lobbyState);

        // Request current state from control tab (if control panel is open, it will respond)
        channel.postMessage({ type: 'request-state' });

        // Only poll while waiting for game to start
        if (!isGameActive) {
            pollingInterval = setInterval(function() {
                channel.postMessage({ type: 'request-state' });
            }, 2000);
        }
    </script>
</x-layouts.presentation>
