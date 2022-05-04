CREATE TABLE entry (
  id      TEXT, -- entry/@xml:id
  lemma   TEXT, -- *Ἀhεριγος
  label   TEXT, -- *Ἀhεριγ<sup>u̯</sup>ος
  html    TEXT, -- displayable entry
  form    TEXT, -- lemma without ponctuation
  monoton TEXT, -- lemma without diacritics
  latin   TEXT, -- latin script version of the lemma
  inverso TEXT  -- reverse form
);
CREATE INDEX entryLemma     ON entry (lemma ASC);
CREATE INDEX entryId        ON entry (id ASC);
CREATE INDEX entryForm      ON entry (form ASC);
CREATE INDEX entryMonoton   ON entry (monoton ASC);
CREATE INDEX entryLatin     ON entry (latin DESC);
CREATE INDEX entryInverso   ON entry (inverso ASC);

CREATE VIRTUAL TABLE search USING FTS3 (
  -- table of searchable items
  entry     INT,  -- entry rowid
  id        TEXT, -- entry/@xml:id
  lemma     TEXT, -- *Ἀhεριγος
  label     TEXT, -- *Ἀhεριγ<sup>u̯</sup>ος
  anchor    TEXT, -- relative anchor in entry
  type      TEXT, -- content type
  text      TEXT, -- exact text
  monoton   TEXT, -- desaccentuated text
);

CREATE TABLE inverso (
-- table to suggest lemma from a reverse query, use auto rowid
  inverso, -- reverse form
  id,      -- entry/@xml:id
  label    -- *Ἀhεριγ<sup>u̯</sup>ος
);
CREATE INDEX inversoInverso ON inverso (inverso ASC);
CREATE INDEX inversoId      ON inverso (id ASC);
INSERT INTO inverso SELECT inverso, id, label FROM entry ORDER BY inverso;
