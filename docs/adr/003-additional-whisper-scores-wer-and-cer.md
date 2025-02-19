# Donation pre-qualification via WER and CER

Date: 22-10-2024

## Status

Accepted

## Context

Word error rate (WER) and character error rate (CER) are common metrics used
when measuring the performance of automated speech recognition system.

## Decision

In addition to the `similar_text` score discussed in [ADR-002](002-whisper-score-via-similar-text.md)
WER and CER should be incorporated in the dataset. These should also allow automatic validation.

For more details on qualification of donations see [https://github.com/itk-dev/giv-din-stemme?tab=readme-ov-file#whisper](https://github.com/itk-dev/giv-din-stemme?tab=readme-ov-file#whisper)

## Consequences

Be aware that previously validated donations will have been validated based on `similar_text` scores.
If we simply wish to validate based on WER or CER we might have to invalidate previous donations.
