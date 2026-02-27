# Answer Matching

This document describes the fuzzy matching algorithm used to match player answers to the category's answer list.

## Overview

When a player submits an answer, the system attempts to match it against the category's answer list using progressively looser matching strategies.

**File**: `resources/views/livewire/pages/games/control.blade.php` (findMatchingAnswer method)

## Matching Pipeline

Matches are attempted in order, stopping at the first successful match:

```
Player Input
     │
     ▼
┌────────────────┐
│ 1. Exact Match │──► Found? Return answer
└───────┬────────┘
        │ No
        ▼
┌────────────────┐
│ 2. Display     │──► Found? Return answer
│    Text Match  │
└───────┬────────┘
        │ No
        ▼
┌────────────────┐
│ 3. Normalized  │──► Found? Return answer
│    Match       │
└───────┬────────┘
        │ No
        ▼
┌────────────────┐
│ 4. Containment │──► Found? Return answer
│    Match       │
└───────┬────────┘
        │ No
        ▼
┌────────────────┐
│ 5. Levenshtein │──► Found? Return answer
│    Distance    │
└───────┬────────┘
        │ No
        ▼
   Return NULL
   (Not on list)
```

## Matching Strategies

### 1. Exact Match (Case-Insensitive)

```php
if (strtolower($input) === strtolower($answer->text)) {
    return $answer;
}
```

- Compares full input against full answer text
- Case-insensitive

**Example**: "The Beatles" matches "The Beatles"

### 2. Display Text Match

```php
if (strtolower($input) === strtolower($answer->display_text)) {
    return $answer;
}
```

- Compares input against first part of answer (before comma)
- Used for geographic answers with location suffixes

**Example**: "Great Wall of China" matches "Great Wall of China, China"

### 3. Normalized Match

```php
$articles = ['the ', 'a ', 'an ', 'el ', 'la ', 'los ', 'las ', 'le ', 'les ', 'der ', 'die ', 'das '];

function normalizeForMatching($str) {
    $normalized = strtolower(trim($str));
    foreach ($articles as $article) {
        if (str_starts_with($normalized, $article)) {
            $normalized = substr($normalized, strlen($article));
        }
    }
    return preg_replace('/[^a-z0-9]/', '', $normalized);
}
```

**Normalization steps**:
1. Lowercase
2. Remove leading articles (English, Spanish, French, German)
3. Remove punctuation and spaces

**Example**: "El Fuego" → "fuego" matches "fuego"

### 4. Containment Match

```php
$minLength = 4;  // Prevent false positives with short strings

if (strlen($inputNormalized) >= $minLength && strlen($targetNormalized) >= $minLength) {
    if (str_contains($targetNormalized, $inputNormalized) ||
        str_contains($inputNormalized, $targetNormalized)) {
        return $answer;
    }
}
```

- Checks if one string contains the other
- Only for strings ≥4 characters (prevents "a" matching "amazing")

**Example**: "Beatles" matches "The Beatles"

### 5. Levenshtein Distance

```php
$distance = levenshtein($inputNormalized, $targetNormalized);
$maxDistance = strlen($targetNormalized) > 5 ? 2 : 1;

if ($distance <= $maxDistance) {
    return $answer;
}
```

- Allows for typos and minor spelling errors
- Shorter strings: max 1 character difference
- Longer strings (>5 chars): max 2 character difference

**Examples**:
- "Beatls" (5 chars) → max distance 1 → matches "Beatles"
- "Beetals" (7 chars) → max distance 2 → matches "Beatles"

## Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `FUZZY_MIN_LENGTH` | 4 | Min length for containment match |
| `LEVENSHTEIN_SHORT` | 1 | Max distance for strings ≤5 chars |
| `LEVENSHTEIN_LONG` | 2 | Max distance for strings >5 chars |

## Not on List

If no match is found after all strategies:
- `answer_id` is set to `NULL` in PlayerAnswer
- Points awarded: **-3** (configurable via `not_on_list_penalty`)

## GM Override

The GM can correct matches at any time:

1. During **Collecting**: Click edit on a submitted answer
2. During **Revealing/Scoring**: Click edit on any player's answer

The correction updates `PlayerAnswer.answer_id` and recalculates points.

## Edge Cases

### Duplicate Handling
If a player enters an answer that's already been revealed or submitted by another player, the system still matches it. The GM can decide whether to accept it.

### Case Sensitivity
All matching is case-insensitive. "BEATLES" = "Beatles" = "beatles"

### Unicode
Normalization removes non-alphanumeric characters, so "café" becomes "caf" which may not match "cafe". Consider this limitation for international content.

### Empty Input
Empty or whitespace-only inputs are rejected at the UI level before matching.

## Code Reference

The matching logic is in the control panel Livewire component:

```php
// resources/views/livewire/pages/games/control.blade.php

public function findMatchingAnswer(string $input, Category $category): ?Answer
{
    // ... matching logic
}

public function submitPlayerAnswer(string $playerId, string $answerText): void
{
    $answer = $this->findMatchingAnswer($answerText, $round->category);
    $points = $answer ? $answer->points : -3;  // Not on list penalty
    // ... create PlayerAnswer
}
```
