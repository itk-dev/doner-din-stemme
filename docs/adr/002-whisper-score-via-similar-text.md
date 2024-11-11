# Donation pre-qualification via Whisper and similar_text

Date: 22-10-2024

## Status

Accepted

## Context

Some donations may not be useful due to the audio recording, i.e. donations must be validated.

## Decision

To avoid editors having to manually having to listen to thousands of audio recordings,
we intend to pre-qualify donations by using a combination of [Whisper](https://github.com/openai/whisper)
and PHPs [similar_text](https://www.php.net/manual/en/function.similar-text.php) method.

For more details on qualification of donations see [https://github.com/itk-dev/giv-din-stemme?tab=readme-ov-file#whisper](https://github.com/itk-dev/giv-din-stemme?tab=readme-ov-file#whisper)

## Consequences

The scores we get from doing this pre-qualification should be considered with a grain of salt.

Firstly, donations are transcribed via Whisper, which could definitely be improved upon.
In fact, it is exactly this we are attempting to help by creating this dataset on which
LLMs can be trained.

Secondly, the `similar_text` method directly compares characters in a string.
This means that words sounding phonetically like each other does not get a better score than two completely different words.

With that being said, we still believe this is an effective way of filtering out test or nonsensical donations.
