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
                    <p class="text-2xl text-blue-400">Give your answers now!</p>
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

        <!-- Waiting Slide (game not yet started) -->
        <div id="slide-waiting" class="hidden w-full max-w-4xl mx-auto px-8 text-center">
            <div class="animate-fade-in">
                <h1 class="text-6xl font-black mb-8">
                    <span class="text-red-500">TENSION</span>
                </h1>
                <p class="text-3xl text-slate-400">Waiting for game to start...</p>
            </div>
        </div>
    </div>

    <!-- Scoreboard Overlay (bottom) - hidden during reveal -->
    <div id="scoreboard-overlay" class="hidden fixed bottom-0 left-0 right-0 bg-slate-900/90 border-t border-slate-700 py-4 px-8">
        <div id="scoreboard-players" class="flex justify-center gap-8">
            @foreach($game->players as $player)
                <div class="text-center" data-player-id="{{ $player->id }}">
                    <div class="text-lg font-bold" style="color: {{ $player->color }}">{{ $player->name }}</div>
                    <div class="text-2xl font-bold player-score">{{ $player->total_score }}</div>
                </div>
            @endforeach
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
                    'text' => $a->text,
                    'stat' => $a->stat,
                    'points' => $a->points,
                    'is_tension' => $a->is_tension,
                ])->values()->toArray(),
            ];
        }

        // Get current round for initial state
        $currentRound = $game->rounds->where('round_number', $game->current_round)->first();

        // Check if we should show rules (round 1, slide 0, intro status)
        $showRules = $game->current_round === 1
            && $currentRound
            && $currentRound->current_slide === 0
            && $currentRound->status === 'intro';

        // Build initial state
        $initialState = [
            'gameId' => $game->id,
            'showRules' => $showRules,
            'currentRound' => $game->current_round,
            'roundStatus' => $currentRound?->status,
            'currentSlide' => $currentRound?->current_slide ?? 0,
            'gameStatus' => $game->status,
            'revealedAnswers' => [],
            'players' => $game->players->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'color' => $p->color,
                'total_score' => $p->total_score,
                'double_used' => $p->double_used,
            ])->toArray(),
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
            rules: document.getElementById('slide-rules'),
            intro: document.getElementById('slide-intro'),
            collecting: document.getElementById('slide-collecting'),
            reveal: document.getElementById('slide-reveal'),
            scores: document.getElementById('slide-scores'),
            gameover: document.getElementById('slide-gameover'),
            waiting: document.getElementById('slide-waiting'),
        };

        const scoreboardOverlay = document.getElementById('scoreboard-overlay');

        function hideAllSlides() {
            Object.values(slides).forEach(function(slide) {
                slide.classList.add('hidden');
            });
        }

        function showSlide(name) {
            hideAllSlides();
            if (slides[name]) {
                slides[name].classList.remove('hidden');
                // Re-trigger animation
                var animated = slides[name].querySelector('.animate-slide-in, .animate-fade-in, .animate-scale-in');
                if (animated) {
                    animated.style.animation = 'none';
                    animated.offsetHeight; // Trigger reflow
                    animated.style.animation = null;
                }
            }
        }

        function updateScoreboard(playerList) {
            playerList.forEach(function(player) {
                var el = document.querySelector('[data-player-id="' + player.id + '"] .player-score');
                if (el) el.textContent = player.total_score;
            });
        }

        function renderCollectedAnswers(collectedAnswers, allPlayers) {
            var container = document.getElementById('collected-answers');
            if (!container) return;

            // Create a map of collected answers by player ID
            var answerMap = {};
            collectedAnswers.forEach(function(ca) {
                answerMap[ca.playerId] = ca;
            });

            // Render all players, showing their answer if collected
            var html = allPlayers.map(function(player) {
                var collected = answerMap[player.id];
                if (collected) {
                    return '<div class="bg-slate-800 rounded-xl p-4 text-center ring-2 ring-green-500/50">' +
                        '<div class="text-xl font-bold mb-2" style="color: ' + player.color + '">' + player.name + '</div>' +
                        '<div class="text-2xl font-bold text-white">' + collected.answerText + '</div>' +
                        '<div class="text-green-400 mt-1">✓</div>' +
                    '</div>';
                } else {
                    return '<div class="bg-slate-800/50 rounded-xl p-4 text-center opacity-50">' +
                        '<div class="text-xl font-bold mb-2" style="color: ' + player.color + '">' + player.name + '</div>' +
                        '<div class="text-2xl text-slate-500">...</div>' +
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

        function handleStateUpdate(state) {
            console.log('=== STATE UPDATE ===', state);

            // Handle game not started yet (ready status)
            if (state.gameStatus === 'ready') {
                showSlide('waiting');
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
                console.log('No category found for round', state.currentRound, '- showing waiting');
                showSlide('waiting');
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
                    renderCollectedAnswers(state.collectedAnswers || [], state.players);
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

        // Apply initial state
        console.log('[Present] Applying initial state:', initialState);
        handleStateUpdate(initialState);

        // Request current state from control tab
        channel.postMessage({ type: 'request-state' });

        // Only poll while waiting for game to start
        if (!isGameActive) {
            pollingInterval = setInterval(function() {
                channel.postMessage({ type: 'request-state' });
            }, 2000);
        }
    </script>
</x-layouts.presentation>
