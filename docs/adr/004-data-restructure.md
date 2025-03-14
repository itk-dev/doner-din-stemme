# Data restructure

Date: 04-03-2025

## Status

Accepted

## Context

Some of the collected data and some of the data qualifications has been recognized as redundant.

## Decision

With respect to data on the donor, birthplace and current zip-code is not relevant. Instead,
the interesting bits are whether the donor has an accent or not. We will replace birthplace
and zip-code with a yes/no selector regarding accent. This will need a well-thought description.

With respect to some of the qualifications we have made, similar text score is not a commonly
used metric to score donations. Hence, we will remove this. Furthermore, we have scrapped the
idea of validation as even bad scoring donations can be used to train speech recognition systems
such as whisper.

## Consequences

We will have a data cut-off where the accent data has been introduced. It is also important
to be aware that donations will no longer be validated.
